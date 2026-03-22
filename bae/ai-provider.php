<?php
/**
 * BAE AI Provider — 4× Gemini rotation, then 20× Groq fallback
 *
 * Waterfall strategy:
 *   1. Try each Gemini key (BAE_GEMINI_KEY_1 … BAE_GEMINI_KEY_4) in order.
 *      Stop and return on the first success.
 *   2. Only if ALL Gemini keys hit quota/rate-limit, rotate through
 *      up to 20 Groq keys until one succeeds.
 *   3. Hard errors (401 bad key, 400 bad request) short-circuit immediately
 *      and jump to the next tier — don't waste retries.
 *
 * .env keys:
 *   BAE_GEMINI_KEY_1   ← your primary key (replaces old BAE_GEMINI_API_KEY)
 *   BAE_GEMINI_KEY_2
 *   BAE_GEMINI_KEY_3
 *   BAE_GEMINI_KEY_4
 *   BAE_GROQ_KEY_1 … BAE_GROQ_KEY_20
 *
 * Backwards-compat: BAE_GEMINI_API_KEY still works as KEY_1 if set alone.
 */

// ---------------------------------------------------------------------------
// 1. GEMINI KEY POOL (up to 4 keys, in order)
// ---------------------------------------------------------------------------
function bae_gemini_key_pool(): array {
    static $pool = null;
    if ( $pool !== null ) return $pool;
    $pool = [];
    for ( $i = 1; $i <= 4; $i++ ) {
        $key = getenv( "BAE_GEMINI_KEY_{$i}" );
        if ( $key && strlen( trim( $key ) ) > 10 ) $pool[] = trim( $key );
    }
    // Legacy single-key support
    if ( empty( $pool ) ) {
        $key = getenv( 'BAE_GEMINI_API_KEY' );
        if ( $key && strlen( trim( $key ) ) > 10 ) $pool[] = trim( $key );
    }
    return $pool;
}

function bae_gemini_endpoint( string $key ): string {
    return 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key=' . $key;
}

// ---------------------------------------------------------------------------
// 2. GROQ KEY POOL (up to 20 keys, shuffled)
// ---------------------------------------------------------------------------
function bae_groq_key_pool(): array {
    static $pool = null;
    if ( $pool !== null ) return $pool;
    $pool = [];
    for ( $i = 1; $i <= 20; $i++ ) {
        $key = getenv( "BAE_GROQ_KEY_{$i}" );
        if ( $key && strlen( trim( $key ) ) > 10 ) $pool[] = trim( $key );
    }
    shuffle( $pool );
    return $pool;
}

// ---------------------------------------------------------------------------
// 3. ERROR CLASSIFIERS
// ---------------------------------------------------------------------------
function bae_is_quota_error( int $code, array $decoded ): bool {
    if ( $code === 429 ) return true;
    $status  = $decoded['error']['status']  ?? '';
    $message = strtolower( $decoded['error']['message'] ?? '' );
    if ( $status === 'RESOURCE_EXHAUSTED' )                  return true;
    if ( strpos( $message, 'quota' ) !== false )             return true;
    if ( strpos( $message, 'rate limit' ) !== false )        return true;
    if ( strpos( $message, 'too many requests' ) !== false ) return true;
    return false;
}

function bae_is_hard_error( int $code, array $decoded ): bool {
    if ( in_array( $code, [ 400, 401, 403 ], true ) ) return true;
    $status = $decoded['error']['status'] ?? '';
    return in_array( $status, [ 'INVALID_ARGUMENT', 'PERMISSION_DENIED', 'UNAUTHENTICATED' ], true );
}

// ---------------------------------------------------------------------------
// 4. HTML POST-PROCESSOR (shared by Gemini + Groq paths)
// ---------------------------------------------------------------------------
function bae_clean_html_response( string $text ): string {
    $text = preg_replace( '/^```html\s*/i', '', trim( $text ) );
    $text = preg_replace( '/^```\s*/i',     '', trim( $text ) );
    $text = preg_replace( '/```\s*$/',      '', trim( $text ) );
    $text = preg_replace( '/<html[^>]*>/i',            '', $text );
    $text = preg_replace( '/<\/html>/i',               '', $text );
    $text = preg_replace( '/<head[^>]*>.*?<\/head>/is','', $text );
    $text = preg_replace( '/<body[^>]*>/i',            '', $text );
    $text = preg_replace( '/<\/body>/i',               '', $text );
    $text = preg_replace( '/<script\b[^>]*>.*?<\/script>/is', '', $text );
    $text = preg_replace( '/<link\b[^>]*rel=[\'"]?stylesheet[\'"]?[^>]*>/is', '', $text );
    $text = preg_replace_callback(
        '/<style([^>]*)>(.*?)<\/style>/is',
        function ( $m ) {
            $css = preg_replace_callback(
                '/([^@{}][^{}]*)\{/m',
                function ( $sel ) {
                    $s = trim( $sel[1] );
                    return empty( $s ) ? $sel[0] : '.bae-ai-asset ' . $s . '{';
                },
                $m[2]
            );
            return '<style' . $m[1] . '>' . $css . '</style>';
        },
        $text
    );
    return trim( $text );
}

// Alias for any direct calls to bae_sanitize_asset_html() in main.php
if ( ! function_exists( 'bae_sanitize_asset_html' ) ) {
    function bae_sanitize_asset_html( string $html ): string {
        return bae_clean_html_response( $html );
    }
}

// ---------------------------------------------------------------------------
// 5. LOW-LEVEL REQUEST FUNCTIONS
// ---------------------------------------------------------------------------

/**
 * One Gemini JSON call. $quota_hit set true if caller should try next key.
 * Returns text string on success, ['error'=>…] on failure.
 */
function bae_gemini_json_single( string $key, string $prompt, bool &$quota_hit ) {
    $quota_hit = false;
    $response  = wp_remote_post( bae_gemini_endpoint( $key ), [
        'timeout' => 45,
        'headers' => [ 'Content-Type' => 'application/json' ],
        'body'    => json_encode([
            'contents'         => [[ 'parts' => [[ 'text' => $prompt ]] ]],
            'generationConfig' => [
                'temperature'      => 0.9,
                'maxOutputTokens'  => 2048,
                'responseMimeType' => 'application/json',
            ],
        ]),
    ]);

    if ( is_wp_error( $response ) ) {
        $quota_hit = true; // network errors are transient
        return [ 'error' => 'Gemini network error: ' . $response->get_error_message() ];
    }

    $code    = wp_remote_retrieve_response_code( $response );
    $decoded = json_decode( wp_remote_retrieve_body( $response ), true ) ?? [];

    if ( $code === 200 ) {
        $text = $decoded['candidates'][0]['content']['parts'][0]['text'] ?? null;
        if ( ! $text ) return [ 'error' => 'Gemini returned empty response.' ];
        $text = preg_replace( '/^```json\s*/i', '', trim( $text ) );
        $text = preg_replace( '/^```\s*/i',     '', trim( $text ) );
        $text = preg_replace( '/```\s*$/',      '', trim( $text ) );
        $text = trim( $text );
        if ( preg_match( '/(\[[\s\S]*\]|\{[\s\S]*\})/s', $text, $m ) ) $text = $m[1];
        return $text; // success
    }

    if ( bae_is_quota_error( $code, $decoded ) ) {
        $quota_hit = true;
        return [ 'error' => "Gemini quota (HTTP {$code})." ];
    }

    $msg = $decoded['error']['message'] ?? "HTTP {$code}";
    return [ 'error' => "Gemini error: {$msg}" ];
}

/**
 * One Gemini HTML call. $quota_hit set true if caller should try next key.
 */
function bae_gemini_html_single( string $key, string $prompt, bool &$quota_hit ) {
    $quota_hit = false;
    $response  = wp_remote_post( bae_gemini_endpoint( $key ), [
        'timeout' => 60,
        'headers' => [ 'Content-Type' => 'application/json' ],
        'body'    => json_encode([
            'contents'         => [[ 'parts' => [[ 'text' => $prompt ]] ]],
            'generationConfig' => [
                'temperature'     => 0.9,
                'maxOutputTokens' => 8192,
            ],
        ]),
    ]);

    if ( is_wp_error( $response ) ) {
        $quota_hit = true;
        return [ 'error' => 'Gemini network error: ' . $response->get_error_message() ];
    }

    $code    = wp_remote_retrieve_response_code( $response );
    $decoded = json_decode( wp_remote_retrieve_body( $response ), true ) ?? [];

    if ( $code === 200 ) {
        $candidate = $decoded['candidates'][0] ?? null;
        $finish    = $candidate['finishReason'] ?? '';
        $text      = $candidate['content']['parts'][0]['text'] ?? null;
        if ( ! $text ) return [ 'error' => 'Gemini returned empty response.' ];
        if ( $finish === 'MAX_TOKENS' ) return [ 'error' => 'Generated asset was too large and got cut off. Try a simpler request.' ];
        return bae_clean_html_response( $text ); // success
    }

    if ( bae_is_quota_error( $code, $decoded ) ) {
        $quota_hit = true;
        return [ 'error' => "Gemini quota (HTTP {$code})." ];
    }

    $msg = $decoded['error']['message'] ?? "HTTP {$code}";
    return [ 'error' => "Gemini error: {$msg}" ];
}

/**
 * One Groq call (OpenAI-compatible).
 */
function bae_groq_single( string $key, string $prompt, bool $json_mode, int $max_tokens ) {
    $body = [
        'model'       => 'llama-3.3-70b-versatile',
        'max_tokens'  => $max_tokens,
        'temperature' => 0.9,
        'messages'    => [
            [
                'role'    => 'system',
                'content' => $json_mode
                    ? 'You are a helpful assistant. Respond ONLY with valid JSON — no markdown, no code fences, no extra text.'
                    : 'You are a helpful assistant that generates clean HTML/CSS. Never wrap output in markdown fences.',
            ],
            [ 'role' => 'user', 'content' => $prompt ],
        ],
    ];
    if ( $json_mode ) $body['response_format'] = [ 'type' => 'json_object' ];

    $response = wp_remote_post( 'https://api.groq.com/openai/v1/chat/completions', [
        'timeout' => 60,
        'headers' => [
            'Content-Type'  => 'application/json',
            'Authorization' => 'Bearer ' . $key,
        ],
        'body' => json_encode( $body ),
    ]);

    if ( is_wp_error( $response ) ) {
        return [ 'error' => 'Groq network error: ' . $response->get_error_message() ];
    }

    $code = wp_remote_retrieve_response_code( $response );
    if ( $code !== 200 ) {
        $decoded = json_decode( wp_remote_retrieve_body( $response ), true ) ?? [];
        $msg     = $decoded['error']['message'] ?? "HTTP {$code}";
        return [ 'error' => "Groq error: {$msg}" ];
    }

    $data = json_decode( wp_remote_retrieve_body( $response ), true );
    $text = $data['choices'][0]['message']['content'] ?? null;
    if ( ! $text ) return [ 'error' => 'Groq returned empty response.' ];

    $text = preg_replace( '/^```json\s*/i', '', trim( $text ) );
    $text = preg_replace( '/^```\s*/i',     '', trim( $text ) );
    $text = preg_replace( '/```\s*$/',      '', trim( $text ) );
    return trim( $text ); // success
}

// ---------------------------------------------------------------------------
// 6. ORCHESTRATORS — waterfall: Gemini 1→4, then Groq 1→20
// ---------------------------------------------------------------------------

function bae_ai_json_request( string $prompt ) {
    $gemini_keys  = bae_gemini_key_pool();
    $total_gemini = count( $gemini_keys );

    foreach ( $gemini_keys as $i => $key ) {
        $quota_hit = false;
        $result    = bae_gemini_json_single( $key, $prompt, $quota_hit );

        if ( ! is_array( $result ) ) return $result; // success

        $key_num = $i + 1;
        if ( $quota_hit ) {
            $next = ( $key_num < $total_gemini ) ? "Trying Gemini key #{$key_num}." : 'All Gemini keys exhausted. Falling back to Groq.';
            error_log( "[BAE] Gemini key #{$key_num} quota hit (JSON). {$next}" );
            continue; // try next Gemini key
        }

        // Hard error — skip remaining Gemini keys
        error_log( "[BAE] Gemini key #{$key_num} hard error (JSON): {$result['error']}. Jumping to Groq." );
        break;
    }

    // Groq tier
    $groq_keys = bae_groq_key_pool();
    if ( empty( $groq_keys ) ) {
        return [ 'error' => 'All Gemini keys exhausted and no Groq keys configured.' ];
    }

    foreach ( $groq_keys as $i => $key ) {
        $result = bae_groq_single( $key, $prompt, true, 2048 );
        if ( ! is_array( $result ) ) return $result; // success
        error_log( '[BAE] Groq key #' . ( $i + 1 ) . ' failed (JSON): ' . $result['error'] );
    }

    return [ 'error' => 'All AI providers exhausted. Please try again later.' ];
}

function bae_ai_html_request( string $prompt ) {
    $gemini_keys  = bae_gemini_key_pool();
    $total_gemini = count( $gemini_keys );

    foreach ( $gemini_keys as $i => $key ) {
        $quota_hit = false;
        $result    = bae_gemini_html_single( $key, $prompt, $quota_hit );

        if ( ! is_array( $result ) ) return $result; // success

        $key_num = $i + 1;
        if ( $quota_hit ) {
            $next = ( $key_num < $total_gemini ) ? "Trying Gemini key #{$key_num}." : 'All Gemini keys exhausted. Falling back to Groq.';
            error_log( "[BAE] Gemini key #{$key_num} quota hit (HTML). {$next}" );
            continue;
        }

        error_log( "[BAE] Gemini key #{$key_num} hard error (HTML): {$result['error']}. Jumping to Groq." );
        break;
    }

    // Groq tier
    $groq_keys = bae_groq_key_pool();
    if ( empty( $groq_keys ) ) {
        return [ 'error' => 'All Gemini keys exhausted and no Groq keys configured.' ];
    }

    foreach ( $groq_keys as $i => $key ) {
        $result = bae_groq_single( $key, $prompt, false, 8192 );
        if ( ! is_array( $result ) ) {
            return bae_clean_html_response( $result ); // success
        }
        error_log( '[BAE] Groq key #' . ( $i + 1 ) . ' failed (HTML): ' . $result['error'] );
    }

    return [ 'error' => 'All AI providers exhausted. Please try again later.' ];
}

// ---------------------------------------------------------------------------
// 7. PUBLIC API — drop-in replacements, identical signatures
// ---------------------------------------------------------------------------

function bae_gemini_json_request( string $prompt ) {
    return bae_ai_json_request( $prompt );
}

function bae_gemini_request( string $prompt ) {
    return bae_ai_html_request( $prompt );
}
