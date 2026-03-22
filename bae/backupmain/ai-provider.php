<?php
/**
 * BAE AI Provider — Gemini with Groq Fallback
 *
 * Strategy:
 *   1. Try Gemini (gemini-2.5-flash).
 *   2. If Gemini returns a quota/rate-limit error (429 / RESOURCE_EXHAUSTED),
 *      rotate through up to 20 Groq API keys until one succeeds.
 *   3. All other Gemini errors (bad request, auth, etc.) are returned as-is
 *      without hitting Groq, so you don't burn keys on non-quota failures.
 *
 * ENV vars expected in .env or server environment:
 *   BAE_GEMINI_API_KEY          (existing)
 *   BAE_GROQ_KEY_1 … BAE_GROQ_KEY_20
 *
 * Drop-in: replaces bae_gemini_json_request() and bae_gemini_request()
 * with wrapper equivalents that are 100% signature-compatible.
 */

// ---------------------------------------------------------------------------
// 1. CONSTANTS — Gemini (already defined upstream, guard here just in case)
// ---------------------------------------------------------------------------

if ( ! defined( 'BAE_GEMINI_API_KEY' ) ) {
    $bae_api_key = getenv( 'BAE_GEMINI_API_KEY' ) ?: 'MISSING_GEMINI_KEY';
    define( 'BAE_GEMINI_API_KEY', $bae_api_key );
    define( 'BAE_GEMINI_ENDPOINT', 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key=' . BAE_GEMINI_API_KEY );
}

// ---------------------------------------------------------------------------
// 2. GROQ KEY POOL — loaded once, shuffled for even distribution
// ---------------------------------------------------------------------------

function bae_groq_key_pool(): array {
    static $pool = null;
    if ( $pool !== null ) return $pool;

    $pool = [];
    for ( $i = 1; $i <= 20; $i++ ) {
        $key = getenv( "BAE_GROQ_KEY_{$i}" );
        if ( $key && strlen( trim( $key ) ) > 10 ) {
            $pool[] = trim( $key );
        }
    }
    // Shuffle so concurrent requests don't all hammer key #1
    shuffle( $pool );
    return $pool;
}

// ---------------------------------------------------------------------------
// 3. QUOTA-ERROR DETECTOR
//    Returns true when Gemini's response means "you've hit the rate limit".
// ---------------------------------------------------------------------------

function bae_is_quota_error( int $http_code, array $decoded ): bool {
    if ( $http_code === 429 ) return true;

    // Gemini sometimes returns 200 with a quota error embedded
    $status  = $decoded['error']['status']  ?? '';
    $message = strtolower( $decoded['error']['message'] ?? '' );

    if ( $status === 'RESOURCE_EXHAUSTED' )           return true;
    if ( strpos( $message, 'quota' ) !== false )       return true;
    if ( strpos( $message, 'rate limit' ) !== false )  return true;
    if ( strpos( $message, 'too many requests' ) !== false ) return true;

    return false;
}

// ---------------------------------------------------------------------------
// 4. GROQ REQUEST HELPERS
// ---------------------------------------------------------------------------

/**
 * Call Groq's OpenAI-compatible /chat/completions endpoint.
 *
 * @param string $api_key   One Groq API key.
 * @param string $prompt    The user prompt.
 * @param bool   $json_mode Force JSON output (for palette/data endpoints).
 * @param int    $max_tokens
 * @return string|array  Raw text on success, ['error'=>…] on failure.
 */
function bae_groq_call( string $api_key, string $prompt, bool $json_mode = false, int $max_tokens = 8192 ) {
    $messages = [
        [
            'role'    => 'system',
            'content' => $json_mode
                ? 'You are a helpful assistant. Respond ONLY with valid JSON — no markdown, no code fences, no extra text.'
                : 'You are a helpful assistant that generates clean HTML/CSS. Never wrap output in markdown fences.',
        ],
        [
            'role'    => 'user',
            'content' => $prompt,
        ],
    ];

    $body = [ 
        'model'      => 'llama-3.3-70b-versatile',
        'messages'   => $messages,
        'max_tokens' => $max_tokens,
        'temperature'=> 0.9,
    ];

    if ( $json_mode ) {
        $body['response_format'] = [ 'type' => 'json_object' ];
    }

    $response = wp_remote_post( 'https://api.groq.com/openai/v1/chat/completions', [
        'timeout' => 60,
        'headers' => [
            'Content-Type'  => 'application/json',
            'Authorization' => 'Bearer ' . $api_key,
        ],
        'body' => json_encode( $body ),
    ]);

    if ( is_wp_error( $response ) ) {
        return [ 'error' => 'Groq request failed: ' . $response->get_error_message() ];
    }

    $code = wp_remote_retrieve_response_code( $response );
    if ( $code !== 200 ) {
        $decoded = json_decode( wp_remote_retrieve_body( $response ), true );
        $msg     = $decoded['error']['message'] ?? "HTTP {$code}";
        return [ 'error' => "Groq error: {$msg}" ];
    }

    $data = json_decode( wp_remote_retrieve_body( $response ), true );
    $text = $data['choices'][0]['message']['content'] ?? null;

    if ( ! $text ) {
        return [ 'error' => 'Groq returned empty response.' ];
    }

    // Strip markdown fences just in case (model sometimes ignores the instruction)
    $text = preg_replace( '/^```json\s*/i', '', trim( $text ) );
    $text = preg_replace( '/^```\s*/i',     '', trim( $text ) );
    $text = preg_replace( '/```\s*$/',      '', trim( $text ) );

    return trim( $text );
}

/**
 * Try each Groq key in the pool until one succeeds or all fail.
 *
 * @return string|array  Raw text on success, ['error'=>…] if all keys fail.
 */
function bae_groq_with_fallback( string $prompt, bool $json_mode = false, int $max_tokens = 8192 ) {
    $pool = bae_groq_key_pool();

    if ( empty( $pool ) ) {
        return [ 'error' => 'No Groq API keys configured (BAE_GROQ_KEY_1 … BAE_GROQ_KEY_20).' ];
    }

    $last_error = 'All Groq keys exhausted.';
    foreach ( $pool as $key ) {
        $result = bae_groq_call( $key, $prompt, $json_mode, $max_tokens );

        if ( is_array( $result ) && isset( $result['error'] ) ) {
            // If this key is also rate-limited, try the next one
            $last_error = $result['error'];
            continue;
        }

        // Success
        return $result;
    }

    return [ 'error' => $last_error ];
}

// ---------------------------------------------------------------------------
// 5. SANITIZE HELPER (copied from main.php so it's available regardless)
// ---------------------------------------------------------------------------

if ( ! function_exists( 'bae_sanitize_asset_html' ) ) {
    function bae_sanitize_asset_html( string $html ): string {
        if ( empty( $html ) ) return $html;

        $html = preg_replace( '/<html[^>]*>/i',            '', $html );
        $html = preg_replace( '/<\/html>/i',               '', $html );
        $html = preg_replace( '/<head[^>]*>.*?<\/head>/is','', $html );
        $html = preg_replace( '/<body[^>]*>/i',            '', $html );
        $html = preg_replace( '/<\/body>/i',               '', $html );
        $html = preg_replace( '/<script\b[^>]*>.*?<\/script>/is', '', $html );
        $html = preg_replace( '/<link\b[^>]*rel=[\'"]?stylesheet[\'"]?[^>]*>/is', '', $html );

        $html = preg_replace_callback(
            '/<style([^>]*)>(.*?)<\/style>/is',
            function ( $m ) {
                $css = preg_replace_callback(
                    '/([^@{}][^{}]*)\{/m',
                    function ( $sel ) {
                        $s = trim( $sel[1] );
                        if ( empty( $s ) ) return $sel[0];
                        return '.bae-ai-asset ' . $s . '{';
                    },
                    $m[2]
                );
                return '<style' . $m[1] . '>' . $css . '</style>';
            },
            $html
        );

        return trim( $html );
    }
}

// ---------------------------------------------------------------------------
// 6. PUBLIC API — DROP-IN REPLACEMENTS FOR THE TWO GEMINI FUNCTIONS
// ---------------------------------------------------------------------------

/**
 * JSON request: Gemini first, Groq on quota exhaustion.
 * Identical signature to bae_gemini_json_request().
 */
function bae_gemini_json_request( string $prompt ) {

    // --- Gemini attempt ---
    $body = json_encode([
        'contents' => [[
            'parts' => [[ 'text' => $prompt ]]
        ]],
        'generationConfig' => [
            'temperature'      => 0.9,
            'maxOutputTokens'  => 2048,
            'responseMimeType' => 'application/json',
        ]
    ]);

    $response = wp_remote_post( BAE_GEMINI_ENDPOINT, [
        'timeout' => 45,
        'headers' => [ 'Content-Type' => 'application/json' ],
        'body'    => $body,
    ]);

    $gemini_quota = false;

    if ( ! is_wp_error( $response ) ) {
        $code    = wp_remote_retrieve_response_code( $response );
        $decoded = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( $code === 200 ) {
            $text = $decoded['candidates'][0]['content']['parts'][0]['text'] ?? null;

            if ( $text ) {
                // Clean up fences
                $text = preg_replace( '/^```json\s*/i', '', trim( $text ) );
                $text = preg_replace( '/^```\s*/i',     '', trim( $text ) );
                $text = preg_replace( '/```\s*$/',      '', trim( $text ) );
                $text = trim( $text );

                if ( preg_match( '/(\[[\s\S]*\]|\{[\s\S]*\})/s', $text, $m ) ) {
                    $text = $m[1];
                }

                return $text; // ✅ Gemini success
            }

            return [ 'error' => 'Gemini returned empty response.' ];
        }

        // Check if it's a quota error worth falling back from
        if ( bae_is_quota_error( $code, $decoded ?? [] ) ) {
            $gemini_quota = true;
            error_log( '[BAE] Gemini quota hit (JSON). Falling back to Groq.' );
        } else {
            $msg = $decoded['error']['message'] ?? "HTTP {$code}";
            return [ 'error' => "Gemini error: {$msg}" ];
        }
    } else {
        // Network failure — treat like quota and try Groq
        $gemini_quota = true;
        error_log( '[BAE] Gemini network error (JSON): ' . $response->get_error_message() . '. Falling back to Groq.' );
    }

    // --- Groq fallback (only reached on quota / network error) ---
    if ( $gemini_quota ) {
        return bae_groq_with_fallback( $prompt, true, 2048 );
    }

    return [ 'error' => 'AI request failed.' ];
}

/**
 * HTML/text request: Gemini first, Groq on quota exhaustion.
 * Identical signature to bae_gemini_request().
 */
function bae_gemini_request( string $prompt ) {

    // --- Gemini attempt ---
    $body = json_encode([
        'contents' => [[
            'parts' => [[ 'text' => $prompt ]]
        ]],
        'generationConfig' => [
            'temperature'     => 0.9,
            'maxOutputTokens' => 8192,
        ]
    ]);

    $response = wp_remote_post( BAE_GEMINI_ENDPOINT, [
        'timeout' => 60,
        'headers' => [ 'Content-Type' => 'application/json' ],
        'body'    => $body,
    ]);

    $gemini_quota = false;

    if ( ! is_wp_error( $response ) ) {
        $code    = wp_remote_retrieve_response_code( $response );
        $decoded = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( $code === 200 ) {
            $candidate = $decoded['candidates'][0] ?? null;
            $finish    = $candidate['finishReason'] ?? '';
            $text      = $candidate['content']['parts'][0]['text'] ?? null;

            if ( ! $text ) {
                return [ 'error' => 'Gemini returned empty response.' ];
            }

            if ( $finish === 'MAX_TOKENS' ) {
                return [ 'error' => 'The generated asset was too large and got cut off. Try a simpler or shorter request.' ];
            }

            // Strip fences and wrappers
            $text = preg_replace( '/^```html\s*/i', '', trim( $text ) );
            $text = preg_replace( '/^```\s*/i',     '', trim( $text ) );
            $text = preg_replace( '/```\s*$/',      '', trim( $text ) );
            $text = preg_replace( '/<html[^>]*>/i',            '', $text );
            $text = preg_replace( '/<\/html>/i',               '', $text );
            $text = preg_replace( '/<head[^>]*>.*?<\/head>/is','', $text );
            $text = preg_replace( '/<body[^>]*>/i',            '', $text );
            $text = preg_replace( '/<\/body>/i',               '', $text );
            $text = preg_replace( '/<script\b[^>]*>.*?<\/script>/is', '', $text );

            $text = preg_replace_callback(
                '/<style([^>]*)>(.*?)<\/style>/is',
                function ( $matches ) {
                    $css = preg_replace_callback(
                        '/([^@{}][^{}]*)\{/m',
                        function ( $sel ) {
                            $s = trim( $sel[1] );
                            if ( empty( $s ) ) return $sel[0];
                            return '.bae-ai-asset ' . $s . '{';
                        },
                        $matches[2]
                    );
                    return '<style' . $matches[1] . '>' . $css . '</style>';
                },
                $text
            );

            return trim( $text ); // ✅ Gemini success
        }

        if ( bae_is_quota_error( $code, $decoded ?? [] ) ) {
            $gemini_quota = true;
            error_log( '[BAE] Gemini quota hit (HTML). Falling back to Groq.' );
        } else {
            $msg = $decoded['error']['message'] ?? "HTTP {$code}";
            return [ 'error' => "Gemini error: {$msg}" ];
        }
    } else {
        $gemini_quota = true;
        error_log( '[BAE] Gemini network error (HTML): ' . $response->get_error_message() . '. Falling back to Groq.' );
    }

    // --- Groq fallback ---
    if ( $gemini_quota ) {
        $result = bae_groq_with_fallback( $prompt, false, 8192 );

        if ( is_array( $result ) && isset( $result['error'] ) ) {
            return $result;
        }

        // Apply the same HTML sanitization Gemini path would have done
        return bae_sanitize_asset_html( $result );
    }

    return [ 'error' => 'AI request failed.' ];
}




