<?php
/**
 * ARS Junction - RateLimiter Module
 * Implements configurable rate limiting for Authentication (per-IP + per-account with exponential backoff),
 * Public endpoints, and Authenticated user actions.
 */

require_once __DIR__ . '/rate_limit_config.php';

class RateLimiter {
    private static $initialized = false;

    /**
     * Resolve the client IP address securely.
     */
    public static function getClientIp(): string {
        $ipKeys = ['HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'REMOTE_ADDR'];
        foreach ($ipKeys as $key) {
            if (!empty($_SERVER[$key])) {
                $ips = explode(',', $_SERVER[$key]);
                foreach ($ips as $ip) {
                    $ip = trim($ip);
                    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                        return $ip;
                    }
                    if (filter_var($ip, FILTER_VALIDATE_IP)) {
                        return $ip;
                    }
                }
            }
        }
        return '127.0.0.1';
    }

    /**
     * Auto-create rate limiting database tables if they do not exist.
     */
    private static function ensureTablesExist(PDO $pdo): void {
        if (self::$initialized) return;
        try {
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS rate_limits (
                    rate_key VARCHAR(191) PRIMARY KEY,
                    hits INT NOT NULL DEFAULT 1,
                    reset_at BIGINT NOT NULL
                );
                CREATE TABLE IF NOT EXISTS auth_attempts (
                    attempt_key VARCHAR(191) PRIMARY KEY,
                    attempt_count INT NOT NULL DEFAULT 0,
                    last_attempt BIGINT NOT NULL DEFAULT 0,
                    lockout_until BIGINT NOT NULL DEFAULT 0
                );
            ");
            self::$initialized = true;
        } catch (Exception $e) {
            // Log error silently, fallback gracefully
        }
    }

    /**
     * Check rate limit for authentication actions (login, signup, password reset).
     * Enforces per-IP and per-account rate limits with exponential backoff.
     */
    public static function checkAuth(string $accountIdentifier = '', string $type = 'auth'): array {
        global $conn;
        if (!$conn) {
            return ['allowed' => true, 'retry_after' => 0];
        }

        self::ensureTablesExist($conn);
        $now = time();
        $ip = self::getClientIp();

        $keysToCheck = [];
        $keysToCheck[] = ['key' => "auth:ip:{$type}:{$ip}", 'max' => RL_AUTH_IP_MAX];

        $cleanAccount = strtolower(trim($accountIdentifier));
        if (!empty($cleanAccount)) {
            $keysToCheck[] = ['key' => "auth:acc:{$type}:{$cleanAccount}", 'max' => RL_AUTH_ACCOUNT_MAX];
        }

        foreach ($keysToCheck as $check) {
            try {
                $stmt = $conn->prepare("SELECT attempt_count, lockout_until FROM auth_attempts WHERE attempt_key = ?");
                $stmt->execute([$check['key']]);
                $record = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($record && !empty($record['lockout_until'])) {
                    $lockoutUntil = (int)$record['lockout_until'];
                    if ($now < $lockoutUntil) {
                        $retryAfter = $lockoutUntil - $now;
                        $attempts = (int)$record['attempt_count'];
                        return [
                            'allowed' => false,
                            'retry_after' => $retryAfter,
                            'reason' => "Too many failed attempts. Exponential backoff active. Please try again in {$retryAfter} seconds.",
                            'attempts' => $attempts
                        ];
                    }
                }
            } catch (Exception $e) {
                // Fail-open on DB exception
            }
        }

        return ['allowed' => true, 'retry_after' => 0];
    }

    /**
     * Record a failed authentication attempt and update exponential backoff lockouts.
     */
    public static function recordAuthFailure(string $accountIdentifier = '', string $type = 'auth'): void {
        global $conn;
        if (!$conn) return;

        self::ensureTablesExist($conn);
        $now = time();
        $ip = self::getClientIp();

        $keysToRecord = [];
        $keysToRecord[] = ['key' => "auth:ip:{$type}:{$ip}", 'threshold' => RL_AUTH_IP_MAX];

        $cleanAccount = strtolower(trim($accountIdentifier));
        if (!empty($cleanAccount)) {
            $keysToRecord[] = ['key' => "auth:acc:{$type}:{$cleanAccount}", 'threshold' => RL_AUTH_ACCOUNT_MAX];
        }

        foreach ($keysToRecord as $item) {
            $key = $item['key'];
            $threshold = $item['threshold'];

            try {
                $stmt = $conn->prepare("SELECT attempt_count, last_attempt FROM auth_attempts WHERE attempt_key = ?");
                $stmt->execute([$key]);
                $record = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($record) {
                    // Reset count if window has expired without recent lockout
                    if (($now - (int)$record['last_attempt']) > RL_AUTH_WINDOW) {
                        $count = 1;
                    } else {
                        $count = (int)$record['attempt_count'] + 1;
                    }
                } else {
                    $count = 1;
                }

                // Calculate exponential backoff delay if threshold reached/exceeded
                $lockoutUntil = 0;
                if ($count >= $threshold) {
                    $exponent = $count - $threshold;
                    $delay = RL_AUTH_BACKOFF_BASE * pow(2, $exponent);
                    if ($delay > RL_AUTH_BACKOFF_MAX) {
                        $delay = RL_AUTH_BACKOFF_MAX;
                    }
                    $lockoutUntil = $now + (int)$delay;
                }

                $upsert = $conn->prepare("
                    INSERT INTO auth_attempts (attempt_key, attempt_count, last_attempt, lockout_until)
                    VALUES (?, ?, ?, ?)
                    ON CONFLICT(attempt_key) DO UPDATE SET
                        attempt_count = EXCLUDED.attempt_count,
                        last_attempt = EXCLUDED.last_attempt,
                        lockout_until = EXCLUDED.lockout_until
                ");
                $upsert->execute([$key, $count, $now, $lockoutUntil]);
            } catch (Exception $e) {
                // Fallback for MySQL syntax ON DUPLICATE KEY UPDATE if PostgreSQL syntax is rejected
                try {
                    $mysqlUpsert = $conn->prepare("
                        INSERT INTO auth_attempts (attempt_key, attempt_count, last_attempt, lockout_until)
                        VALUES (?, ?, ?, ?)
                        ON DUPLICATE KEY UPDATE
                            attempt_count = VALUES(attempt_count),
                            last_attempt = VALUES(last_attempt),
                            lockout_until = VALUES(lockout_until)
                    ");
                    $mysqlUpsert->execute([$key, $count, $now, $lockoutUntil]);
                } catch (Exception $ex) {
                    // Ignore fail-open
                }
            }
        }
    }

    /**
     * Clear failed authentication attempt records on successful login.
     */
    public static function clearAuthSuccess(string $accountIdentifier = '', string $type = 'auth'): void {
        global $conn;
        if (!$conn) return;

        self::ensureTablesExist($conn);
        $ip = self::getClientIp();
        $keysToDelete = ["auth:ip:{$type}:{$ip}"];

        $cleanAccount = strtolower(trim($accountIdentifier));
        if (!empty($cleanAccount)) {
            $keysToDelete[] = "auth:acc:{$type}:{$cleanAccount}";
        }

        foreach ($keysToDelete as $key) {
            try {
                $stmt = $conn->prepare("DELETE FROM auth_attempts WHERE attempt_key = ?");
                $stmt->execute([$key]);
            } catch (Exception $e) {
                // Ignore
            }
        }
    }

    /**
     * General sliding window rate limiting algorithm.
     */
    public static function checkSlidingWindow(string $key, int $maxRequests, int $windowSeconds): array {
        global $conn;
        if (!$conn) {
            return ['allowed' => true, 'retry_after' => 0];
        }

        self::ensureTablesExist($conn);
        $now = time();

        try {
            $stmt = $conn->prepare("SELECT hits, reset_at FROM rate_limits WHERE rate_key = ?");
            $stmt->execute([$key]);
            $record = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($record) {
                $resetAt = (int)$record['reset_at'];
                $hits = (int)$record['hits'];

                if ($now > $resetAt) {
                    // Window expired: reset count and new reset_at
                    $newResetAt = $now + $windowSeconds;
                    $update = $conn->prepare("UPDATE rate_limits SET hits = 1, reset_at = ? WHERE rate_key = ?");
                    $update->execute([$newResetAt, $key]);
                    return ['allowed' => true, 'retry_after' => 0];
                } else {
                    if ($hits >= $maxRequests) {
                        $retryAfter = $resetAt - $now;
                        return [
                            'allowed' => false,
                            'retry_after' => $retryAfter,
                            'reason' => "Rate limit exceeded. Maximum {$maxRequests} requests per {$windowSeconds} seconds. Please try again in {$retryAfter} seconds."
                        ];
                    } else {
                        $update = $conn->prepare("UPDATE rate_limits SET hits = hits + 1 WHERE rate_key = ?");
                        $update->execute([$key]);
                        return ['allowed' => true, 'retry_after' => 0];
                    }
                }
            } else {
                // First request in window
                $resetAt = $now + $windowSeconds;
                try {
                    $insert = $conn->prepare("
                        INSERT INTO rate_limits (rate_key, hits, reset_at)
                        VALUES (?, 1, ?)
                        ON CONFLICT(rate_key) DO UPDATE SET hits = 1, reset_at = EXCLUDED.reset_at
                    ");
                    $insert->execute([$key, $resetAt]);
                } catch (Exception $ex) {
                    $mysqlInsert = $conn->prepare("
                        INSERT INTO rate_limits (rate_key, hits, reset_at)
                        VALUES (?, 1, ?)
                        ON DUPLICATE KEY UPDATE hits = 1, reset_at = VALUES(reset_at)
                    ");
                    $mysqlInsert->execute([$key, $resetAt]);
                }
                return ['allowed' => true, 'retry_after' => 0];
            }
        } catch (Exception $e) {
            return ['allowed' => true, 'retry_after' => 0];
        }
    }

    /**
     * Check rate limit for public endpoints.
     */
    public static function checkPublic(): array {
        $ip = self::getClientIp();
        return self::checkSlidingWindow("pub:ip:{$ip}", RL_PUBLIC_MAX, RL_PUBLIC_WINDOW);
    }

    /**
     * Check rate limit for authenticated user actions.
     */
    public static function checkAuthenticated($userId = null): array {
        if ($userId) {
            $key = "auth_usr:{$userId}";
        } else {
            $ip = self::getClientIp();
            $key = "auth_usr:ip:{$ip}";
        }
        return self::checkSlidingWindow($key, RL_AUTHED_MAX, RL_AUTHED_WINDOW);
    }

    /**
     * Enforce rate limit result: sets standard HTTP 429 status, Retry-After header,
     * and outputs clean error message (JSON for API, HTML/Session for Web pages).
     */
    public static function enforceOrBlock(array $result, ?bool $isJson = null): void {
        if (!empty($result['allowed'])) {
            return;
        }

        $retryAfter = $result['retry_after'] ?? 60;
        $reason = $result['reason'] ?? 'Too many requests. Please try again later.';

        http_response_code(429);
        header("Retry-After: {$retryAfter}");

        if ($isJson === null) {
            $requestUri = $_SERVER['REQUEST_URI'] ?? '';
            $contentType = $_SERVER['HTTP_ACCEPT'] ?? '';
            $isJson = (strpos($requestUri, 'api/') !== false) || (strpos($contentType, 'application/json') !== false);
        }

        if ($isJson) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'error' => $reason,
                'retry_after' => $retryAfter
            ]);
            exit;
        } else {
            if (session_status() === PHP_SESSION_NONE) {
                @session_start();
            }
            $_SESSION['rate_limit_error'] = $reason;
            ?>
            <!DOCTYPE html>
            <html lang="en">
            <head>
                <meta charset="UTF-8">
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                <title>429 Too Many Requests - ARS Junction</title>
                <style>
                    body { font-family: system-ui, -apple-system, sans-serif; background: #0f172a; color: #f8fafc; display: flex; align-items: center; justify-content: center; height: 100vh; margin: 0; }
                    .card { background: #1e293b; padding: 2rem; border-radius: 12px; box-shadow: 0 10px 25px rgba(0,0,0,0.5); text-align: center; max-width: 450px; border: 1px solid #334155; }
                    h1 { color: #f43f5e; font-size: 2rem; margin-top: 0; }
                    p { color: #94a3b8; font-size: 1.1rem; line-height: 1.6; }
                    .badge { display: inline-block; background: #334155; color: #38bdf8; padding: 0.5rem 1rem; border-radius: 9999px; font-weight: bold; margin-top: 1rem; }
                    a { display: inline-block; margin-top: 1.5rem; color: #38bdf8; text-decoration: none; font-weight: 600; }
                    a:hover { text-decoration: underline; }
                </style>
            </head>
            <body>
                <div class="card">
                    <h1>429 - Too Many Requests</h1>
                    <p><?php echo htmlspecialchars($reason); ?></p>
                    <div class="badge">Retry in <?php echo (int)$retryAfter; ?> seconds</div>
                    <div><a href="javascript:location.reload()">Reload Page</a></div>
                </div>
            </body>
            </html>
            <?php
            exit;
        }
    }
}
