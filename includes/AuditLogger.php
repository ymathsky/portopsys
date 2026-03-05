<?php
/**
 * AuditLogger — centralised, silent-fail audit trail.
 *
 * Usage:
 *   AuditLogger::log('login',  'auth',     'User admin logged in');
 *   AuditLogger::log('create', 'vessel',   'Added vessel MV Alabat', 12);
 *   AuditLogger::log('update', 'schedule', 'Trip status → departed', 5,
 *                    ['trip_status'=>'on_time'], ['trip_status'=>'departed']);
 */
class AuditLogger {

    /**
     * Write one audit record.
     *
     * @param string     $action      Verb:  login | logout | login_failed | create | update |
     *                                       delete | status_change | pin_change | password_change |
     *                                       call_token | complete | no_show | cancel | token_generated
     * @param string     $module      Module: auth | user | vessel | schedule | service |
     *                                        announcement | token | counter
     * @param string     $description Human-readable sentence.
     * @param int|null   $recordId    Primary key of the affected row (optional).
     * @param array|null $oldValues   Snapshot before change (optional).
     * @param array|null $newValues   Snapshot after  change (optional).
     */
    public static function log(
        string  $action,
        string  $module,
        string  $description,
        ?int    $recordId  = null,
        ?array  $oldValues = null,
        ?array  $newValues = null
    ): void {
        try {
            $db = getDB();

            // Who is acting?
            $userId   = $_SESSION['user_id']   ?? null;
            $username = $_SESSION['username']  ?? null;

            // For unauthenticated events (failed login) we may receive username in description
            $ip  = self::clientIp();
            $ua  = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 500);

            $stmt = $db->prepare("
                INSERT INTO audit_logs
                    (user_id, username, action, module, description,
                     record_id, old_values, new_values,
                     ip_address, user_agent, created_at)
                VALUES
                    (?, ?, ?, ?, ?,
                     ?, ?, ?,
                     ?, ?, NOW())
            ");

            $stmt->execute([
                $userId,
                $username,
                $action,
                $module,
                $description,
                $recordId,
                $oldValues !== null ? json_encode($oldValues, JSON_UNESCAPED_UNICODE) : null,
                $newValues !== null ? json_encode($newValues, JSON_UNESCAPED_UNICODE) : null,
                $ip,
                $ua,
            ]);

        } catch (Throwable $e) {
            // Never crash the application because of audit logging
            error_log('[AuditLogger] ' . $e->getMessage());
        }
    }

    /**
     * Convenience: log with old/new JSON already encoded as arrays.
     * Strip large / sensitive keys before storage.
     */
    public static function logChange(
        string  $action,
        string  $module,
        string  $description,
        ?int    $recordId,
        array   $before,
        array   $after
    ): void {
        $strip = ['password_hash', 'status_pin_hash', 'password'];
        foreach ($strip as $k) { unset($before[$k], $after[$k]); }
        self::log($action, $module, $description, $recordId, $before, $after);
    }

    // ── helpers ──────────────────────────────────────────────────────────

    private static function clientIp(): string {
        foreach (['HTTP_CF_CONNECTING_IP','HTTP_X_FORWARDED_FOR','HTTP_CLIENT_IP','REMOTE_ADDR'] as $key) {
            if (!empty($_SERVER[$key])) {
                $ip = trim(explode(',', $_SERVER[$key])[0]);
                if (filter_var($ip, FILTER_VALIDATE_IP)) return $ip;
            }
        }
        return '0.0.0.0';
    }
}
