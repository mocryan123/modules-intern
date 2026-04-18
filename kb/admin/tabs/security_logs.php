<?php
/*
 * KBF admin tab: Security Logs.
 */

if (!defined('ABSPATH')) exit;

/**
 * @function  kbf_admin_security_logs_tab
 * @purpose   Renders the admin security logs tab with recent security events and event metadata details.
 * @used-by   [admin/ui.php tab router, user/partials/admin_embed.php tab renderer]
 * @calls     [current_user_can, kbf_admin_date_where, $wpdb->prepare, $wpdb->get_results, get_userdata, json_decode, json_last_error, wp_json_encode, ob_start, ob_get_clean, esc_html]
 * @params    [none]
 * @returns   [string buffered HTML markup for the security logs tab, or access denied alert markup]
 * @status    ACTIVE
 *            ACTIVE = confirmed it is called somewhere
 *            NEEDS REVIEW = could not confirm caller,
 *                           may be unused/dead code
 */
function kbf_admin_security_logs_tab() {
    if (!current_user_can('manage_options')) {
        return '<div class="kbf-alert kbf-alert-error">Access denied.</div>';
    }

    global $wpdb;
    $table = $wpdb->prefix . 'kbf_security_logs';
    $params = [];
    $where = "WHERE 1=1";
    $where .= kbf_admin_date_where('created_at', $params);
    $sql = "SELECT * FROM {$table} {$where} ORDER BY created_at DESC LIMIT 200";
    $rows = $params ? $wpdb->get_results($wpdb->prepare($sql, $params)) : $wpdb->get_results($sql);
    /**
     * @function  format_user_label
     * @purpose   Resolves and caches a human-readable user label for each security log entry.
     * @used-by   [kbf_admin_security_logs_tab row rendering for User column]
     * @calls     [array_key_exists, get_userdata]
     * @params    [mixed $user_id - user ID from security log row]
     * @returns   [string display label for guest, resolved user, or fallback user ID]
     * @status    ACTIVE
     *            ACTIVE = confirmed it is called somewhere
     *            NEEDS REVIEW = could not confirm caller,
     *                           may be unused/dead code
     */
    $format_user_label = function($user_id) {
        if (!$user_id) return 'Guest';
        static $user_cache = [];
        $user_id = (int)$user_id;
        if (!array_key_exists($user_id, $user_cache)) {
            $user_cache[$user_id] = get_userdata($user_id);
        }
        $user = $user_cache[$user_id];
        return $user ? $user->display_name . ' (#' . $user_id . ')' : 'User #' . $user_id;
    };
    /**
     * @function  format_meta
     * @purpose   Normalizes security log metadata into displayable JSON text or raw fallback text.
     * @used-by   [kbf_admin_security_logs_tab row rendering for Meta column]
     * @calls     [json_decode, json_last_error, wp_json_encode]
     * @params    [mixed $meta - metadata payload stored in the log row]
     * @returns   [string normalized metadata text for display]
     * @status    ACTIVE
     *            ACTIVE = confirmed it is called somewhere
     *            NEEDS REVIEW = could not confirm caller,
     *                           may be unused/dead code
     */
    $format_meta = function($meta) {
        if (empty($meta)) return '';
        $decoded = json_decode($meta, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            return wp_json_encode($decoded);
        }
        return (string)$meta;
    };
    /**
     * @function  fallback
     * @purpose   Returns a fallback placeholder when the provided log field value is empty.
     * @used-by   [kbf_admin_security_logs_tab row rendering for IP and Endpoint columns]
     * @calls     [none]
     * @params    [mixed $value - primary value to display, mixed $default - fallback display text]
     * @returns   [mixed original value when present, otherwise fallback value]
     * @status    ACTIVE
     *            ACTIVE = confirmed it is called somewhere
     *            NEEDS REVIEW = could not confirm caller,
     *                           may be unused/dead code
     */
    $fallback = function($value, $default = '-') {
        return $value ? $value : $default;
    };

    ob_start();
    ?>
    <div class="kbf-section">
        <h3 class="kbf-section-title">Security Logs</h3>
        <p style="color:var(--kbf-slate);font-size:13.5px;margin-bottom:18px;">
            Recent rate limits, webhook mismatches, and other security events.
        </p>

        <div class="kbf-card" style="padding:0;overflow:hidden;">
            <div style="display:flex;align-items:center;justify-content:space-between;padding:14px 16px;border-bottom:1px solid var(--kbf-border);background:#f8fafc;">
                <span style="font-size:14px;color:var(--kbf-navy);" class="kbf-strong">Latest 200 Events</span>
                <span style="font-size:12px;color:var(--kbf-slate);">Newest first</span>
            </div>

            <?php if (empty($rows)): ?>
                <div style="padding:18px;color:var(--kbf-slate);font-size:13.5px;">
                    No security logs yet.
                </div>
            <?php else: ?>
                <div style="overflow:auto;">
                    <table style="width:100%;border-collapse:collapse;font-size:12.5px;">
                        <thead>
                            <tr style="text-align:left;background:#f3f7ff;color:#1a3a66;">
                                <th style="padding:10px 12px;border-bottom:1px solid var(--kbf-border);">Time</th>
                                <th style="padding:10px 12px;border-bottom:1px solid var(--kbf-border);">Event</th>
                                <th style="padding:10px 12px;border-bottom:1px solid var(--kbf-border);">User</th>
                                <th style="padding:10px 12px;border-bottom:1px solid var(--kbf-border);">IP</th>
                                <th style="padding:10px 12px;border-bottom:1px solid var(--kbf-border);">Endpoint</th>
                                <th style="padding:10px 12px;border-bottom:1px solid var(--kbf-border);">Meta</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($rows as $r): ?>
                            <?php
                                $user_label = $format_user_label($r->user_id);
                                $meta = $format_meta($r->meta);
                            ?>
                            <tr>
                                <td style="padding:10px 12px;border-bottom:1px solid var(--kbf-border);white-space:nowrap;">
                                    <?php echo esc_html($r->created_at); ?>
                                </td>
                                <td style="padding:10px 12px;border-bottom:1px solid var(--kbf-border);">
                                    <span style="display:inline-flex;align-items:center;gap:6px;background:#eef2ff;border:1px solid #dbe4ff;color:#1a3a66;border-radius:999px;padding:3px 8px;font-weight:600;">
                                        <?php echo esc_html($r->event_type); ?>
                                    </span>
                                </td>
                                <td style="padding:10px 12px;border-bottom:1px solid var(--kbf-border);">
                                    <?php echo esc_html($user_label); ?>
                                </td>
                                <td style="padding:10px 12px;border-bottom:1px solid var(--kbf-border);">
                                    <?php echo esc_html($fallback($r->ip)); ?>
                                </td>
                                <td style="padding:10px 12px;border-bottom:1px solid var(--kbf-border);">
                                    <?php echo esc_html($fallback($r->endpoint)); ?>
                                </td>
                                <td style="padding:10px 12px;border-bottom:1px solid var(--kbf-border);max-width:320px;">
                                    <?php if (!empty($meta)): ?>
                                        <details>
                                            <summary style="cursor:pointer;color:var(--kbf-blue);font-size:12px;">View</summary>
                                            <pre style="white-space:pre-wrap;font-size:11.5px;background:#f8fafc;border:1px solid var(--kbf-border);padding:8px;border-radius:8px;margin-top:6px;"><?php echo esc_html($meta); ?></pre>
                                        </details>
                                    <?php else: ?>
                                        <span style="color:var(--kbf-slate);">-</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
        <div class="kbf-table-desc">Logs recent security-related events for auditing and review.</div>
    </div>
    <?php
    return ob_get_clean();
}

