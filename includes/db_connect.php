<?php
ob_start();

// Configure error displaying and reporting
error_reporting(E_ALL);
ini_set('display_errors', '0'); // Do not display raw PHP errors to users
ini_set('log_errors', '1');     // Log errors server-side

// Custom Exception Handler
set_exception_handler(function ($exception) {
    // Log the full exception server-side
    error_log("Uncaught Exception: " . $exception->getMessage() . " in " . $exception->getFile() . " on line " . $exception->getLine() . "\n" . $exception->getTraceAsString());

    // Clean any buffers
    while (ob_get_level() > 0) {
        ob_end_clean();
    }

    // Determine response type (JSON or HTML)
    $requestUri = $_SERVER['REQUEST_URI'] ?? '';
    $isApi = (strpos($requestUri, 'api/') !== false)
          && (strpos($requestUri, 'restaurant_router') === false)
          && (strpos($requestUri, 'admin_router') === false)
          && (strpos($requestUri, 'delivery_router') === false);

    if ($isApi) {
        http_response_code(500);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => false,
            'message' => 'An internal server error occurred. Please try again later.'
        ]);
    } else {
        http_response_code(500);
        // Display a clean, generic user-friendly HTML error page
        ?>
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Error - ARS Junction</title>
            <style>
                body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f3f4f6; color: #1f2937; display: flex; align-items: center; justify-content: center; height: 100vh; margin: 0; }
                .container { text-align: center; background: white; padding: 2.5rem; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); max-width: 450px; }
                h1 { color: #dc2626; font-size: 2rem; margin-top: 0; }
                p { color: #4b5563; font-size: 1rem; line-height: 1.5; margin-bottom: 1.5rem; }
                .btn { display: inline-block; background-color: #e64a19; color: white; padding: 0.75rem 1.5rem; text-decoration: none; border-radius: 6px; font-weight: 500; transition: background 0.2s; }
                .btn:hover { background-color: #d84315; }
            </style>
        </head>
        <body>
            <div class="container">
                <h1>Something Went Wrong</h1>
                <p>An unexpected internal error occurred on our server. We have logged the details and are working to resolve the issue. Please try again later.</p>
                <a href="/index.php" class="btn">Go to Homepage</a>
            </div>
        </body>
        </html>
        <?php
    }
    exit;
});

// Custom Error Handler (converts PHP errors to ErrorExceptions so they are caught by the exception handler)
set_error_handler(function ($severity, $message, $file, $line) {
    if (!(error_reporting() & $severity)) {
        // This error code is not included in error_reporting
        return;
    }
    throw new ErrorException($message, 0, $severity, $file, $line);
});

// Shutdown function to handle fatal errors nicely
register_shutdown_function(function () {
    $error = error_get_last();
    if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        // Log the fatal error
        error_log("Fatal Error: " . $error['message'] . " in " . $error['file'] . " on line " . $error['line']);

        // Clean buffers
        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        $requestUri = $_SERVER['REQUEST_URI'] ?? '';
        $isApi = (strpos($requestUri, 'api/') !== false)
              && (strpos($requestUri, 'restaurant_router') === false)
              && (strpos($requestUri, 'admin_router') === false)
              && (strpos($requestUri, 'delivery_router') === false);

        if ($isApi) {
            http_response_code(500);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => false,
                'message' => 'A fatal internal server error occurred. Please try again later.'
            ]);
        } else {
            http_response_code(500);
            ?>
            <!DOCTYPE html>
            <html lang="en">
            <head>
                <meta charset="UTF-8">
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                <title>Error - ARS Junction</title>
                <style>
                    body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f3f4f6; color: #1f2937; display: flex; align-items: center; justify-content: center; height: 100vh; margin: 0; }
                    .container { text-align: center; background: white; padding: 2.5rem; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); max-width: 450px; }
                    h1 { color: #dc2626; font-size: 2rem; margin-top: 0; }
                    p { color: #4b5563; font-size: 1rem; line-height: 1.5; margin-bottom: 1.5rem; }
                    .btn { display: inline-block; background-color: #e64a19; color: white; padding: 0.75rem 1.5rem; text-decoration: none; border-radius: 6px; font-weight: 500; transition: background 0.2s; }
                    .btn:hover { background-color: #d84315; }
                </style>
            </head>
            <body>
                <div class="container">
                    <h1>Something Went Wrong</h1>
                    <p>An unexpected internal error occurred on our server. We have logged the details and are working to resolve the issue. Please try again later.</p>
                    <a href="/index.php" class="btn">Go to Homepage</a>
                </div>
            </body>
            </html>
            <?php
        }
    }
});
date_default_timezone_set('Asia/Kolkata');
// Load .env file if it exists
if (file_exists(__DIR__ . '/../.env')) {
    $lines = file(__DIR__ . '/../.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if (empty($line) || strpos($line, '#') === 0) continue;
        if (strpos($line, '=') !== false) {
            list($name, $value) = explode('=', $line, 2);
            $name = trim($name);
            $value = trim($value);
            // Remove quotes if present
            if (preg_match('/^"(.*)"$/', $value, $matches) || preg_match('/^\'(.*)\'$/', $value, $matches)) {
                $value = $matches[1];
            }
            // Populate environment variables
            putenv("{$name}={$value}");
            $_ENV[$name] = $value;
            $_SERVER[$name] = $value;
        }
    }
}

// Database connection for ARS JUNCTION.
// Priority:
// 1. Environment variables (set on Vercel/production)
// 2. Localhost / Ezyro Host detection (as fallback)

// Helper class for compatibility between MySQL and PostgreSQL (Supabase)
if (!class_exists('CompatiblePDOStatement')) {
    class CompatiblePDOStatement extends PDOStatement {
        protected $pdo;

        protected function __construct($pdo) {
            $this->pdo = $pdo;
        }

        public function execute($params = null): bool {
            $query = $this->queryString;
            // Parse table name from INSERT INTO <table>
            if (preg_match('/insert\s+into\s+([a-zA-Z0-9_]+)/i', $query, $matches)) {
                $this->pdo->setLastTable($matches[1]);
            }
            return parent::execute($params);
        }
    }
}

if (!class_exists('CompatiblePDO')) {
    class CompatiblePDO extends PDO {
        private $lastTable = null;

        public function setLastTable($table) {
            $this->lastTable = $table;
        }

        public function lastInsertId($name = null): string|false {
            // If driver is not pgsql, call parent
            if ($this->getAttribute(PDO::ATTR_DRIVER_NAME) !== 'pgsql') {
                return parent::lastInsertId($name);
            }

            // If a sequence name is explicitly passed, use it
            if ($name !== null) {
                return parent::lastInsertId($name);
            }

            // If we know the last table, try to guess the sequence name in PostgreSQL
            if ($this->lastTable !== null) {
                try {
                    // Find primary key column name
                    $stmt = parent::prepare("
                        SELECT kcu.column_name
                        FROM information_schema.table_constraints tc
                        JOIN information_schema.key_column_usage kcu
                          ON tc.constraint_name = kcu.constraint_name
                         AND tc.table_schema = kcu.table_schema
                        WHERE tc.constraint_type = 'PRIMARY KEY'
                          AND tc.table_name = ?
                        LIMIT 1
                    ");
                    $stmt->execute([$this->lastTable]);
                    $pk = $stmt->fetchColumn();

                    if ($pk) {
                        // Get serial sequence name using pg_get_serial_sequence
                        $seqStmt = parent::prepare("SELECT pg_get_serial_sequence(?, ?)");
                        $seqStmt->execute([$this->lastTable, $pk]);
                        $seq = $seqStmt->fetchColumn();
                        if ($seq) {
                            return parent::lastInsertId($seq);
                        }
                    }
                } catch (Exception $e) {
                    // Fallback to manual guess if database query fails
                    try {
                        $guessSeq = $this->lastTable . '_' . ($this->lastTable === 'order_items' ? 'order_item_id' : ($this->lastTable === 'contact_messages' ? 'message_id' : ($this->lastTable === 'delivery_pincodes' ? 'pincode_id' : rtrim($this->lastTable, 's') . '_id'))) . '_seq';
                        return parent::lastInsertId($guessSeq);
                    } catch (Exception $ex) {
                        // ignore
                    }
                }
            }

            // General fallback
            return parent::lastInsertId($name);
        }
    }
}

// 1. Check for Environment Variables
if (getenv('SUPABASE_DB_HOST') || getenv('SUPABASE_DB_PASSWORD')) {
    $host = getenv('SUPABASE_DB_HOST') ?: getenv('DB_HOST');
    $port = getenv('SUPABASE_DB_PORT') ?: getenv('DB_PORT') ?: '5432';
    $dbname = getenv('SUPABASE_DB_NAME') ?: getenv('DB_NAME') ?: 'postgres';
    $username = getenv('SUPABASE_DB_USER') ?: getenv('DB_USER');
    $password = getenv('SUPABASE_DB_PASSWORD') ?: getenv('DB_PASSWORD');
    $driver = getenv('SUPABASE_DB_DRIVER') ?: 'pgsql';
} else {
    $host = getenv('DB_HOST');
    $port = getenv('DB_PORT');
    $dbname = getenv('DB_NAME');
    $username = getenv('DB_USER');
    $password = getenv('DB_PASSWORD');
    $driver = getenv('DB_DRIVER') ?: 'mysql';
}

$host = trim((string)$host);
$port = trim((string)$port);
$dbname = trim((string)$dbname);
$username = trim((string)$username);
$password = trim((string)$password);
$driver = trim((string)$driver);


// Fallback to mysql if pgsql driver is not loaded in current PHP installation
if ($driver === 'pgsql' && !in_array('pgsql', PDO::getAvailableDrivers())) {
    $driver = 'mysql';
    $host = ''; // Triggers fallback to localhost detection
}

// Supabase Connection Pooler Hotfix:
// Direct database connections (db.<ref>.supabase.co:5432) resolve to IPv6-only, which Vercel fails to route.
// We dynamically rewrite to the IPv4-compatible Session Mode pooler host for ap-northeast-1 region.
// We also ensure the username includes the project ref suffix, which the pooler requires to route the request.
if ($driver === 'pgsql' && !empty($host) && preg_match('/^db\.([a-z0-9]+)\.supabase\.co$/i', $host, $matches)) {
    $ref = $matches[1];
    $host = 'aws-0-ap-northeast-1.pooler.supabase.com';
    $port = '5432';
    if (!empty($username) && strpos($username, '.') === false) {
        $username = $username . '.' . $ref;
    }

}

// 2. If environment variables are not set, fall back to old detection logic
if (empty($host)) {
    $is_local = false;
    $server_name = $_SERVER['SERVER_NAME'] ?? '';
    $http_host = $_SERVER['HTTP_HOST'] ?? '';
    $server_addr = $_SERVER['SERVER_ADDR'] ?? '';

    if (
        empty($server_name) || 
        $server_name === 'localhost' || 
        $server_name === '127.0.0.1' || 
        $server_name === '[::1]' ||
        strpos($http_host, 'localhost') !== false ||
        strpos($http_host, '127.0.0.1') !== false ||
        (filter_var($server_name, FILTER_VALIDATE_IP) && filter_var($server_name, FILTER_VALIDATE_IP, FILTER_FLAG_NO_RES_RANGE) === false) ||
        (filter_var($server_addr, FILTER_VALIDATE_IP) && filter_var($server_addr, FILTER_VALIDATE_IP, FILTER_FLAG_NO_RES_RANGE) === false)
    ) {
        $is_local = true;
    }

    if ($is_local) {
        // Local Database (XAMPP)
        $host = '127.0.0.1';
        $port = '3306';
        $dbname = 'ars_junction';
        $username = 'root';
        $password = '';
        $driver = 'mysql';
    } else {
        // Ezyro Production Database (loaded from environment variables)
        $host = getenv('DB_HOST') ?: 'sql109.ezyro.com';
        $port = getenv('DB_PORT') ?: '3306';
        $dbname = getenv('DB_NAME') ?: 'ezyro_38647338_ars_junction';
        $username = getenv('DB_USER') ?: 'ezyro_38647338';
        $password = getenv('DB_PASSWORD') ?: '';
        $driver = getenv('DB_DRIVER') ?: 'mysql';
    }
}

// Prepare candidate hosts for Supabase / PostgreSQL IPv4 pooler resolution on Vercel
$candidate_hosts = [];

if ($driver === 'pgsql') {
    if (!empty($host) && preg_match('/^db\.([a-z0-9]+)\.supabase\.co$/i', $host, $matches)) {
        $ref = $matches[1];
        if (!empty($username) && strpos($username, '.') === false) {
            $username = $username . '.' . $ref;
        }
        // IPv4-compatible pooler hosts across AWS regions for Vercel routing
        $candidate_hosts = [
            "aws-0-ap-south-1.pooler.supabase.com",
            "aws-0-ap-southeast-1.pooler.supabase.com",
            "aws-0-us-east-1.pooler.supabase.com",
            "aws-0-ap-northeast-1.pooler.supabase.com",
            "aws-0-eu-central-1.pooler.supabase.com",
            $host
        ];
    } else {
        $candidate_hosts = [$host];
    }
} else {
    $candidate_hosts = [$host];
}

$conn = null;
$last_exception = null;

foreach ($candidate_hosts as $current_host) {
    if (empty($current_host)) continue;
    
    if ($driver === 'pgsql') {
        $port_to_use = $port ?: '5432';
        $dsn = "pgsql:host={$current_host};port={$port_to_use};dbname={$dbname};options='--client_encoding=UTF8'";
    } else {
        $port_to_use = $port ?: '3306';
        $dsn = "mysql:host={$current_host};port={$port_to_use};dbname={$dbname};charset=utf8mb4";
    }

    try {
        $conn = new CompatiblePDO($dsn, $username, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_TIMEOUT => 4,
        ]);
        $conn->setAttribute(PDO::ATTR_STATEMENT_CLASS, ['CompatiblePDOStatement', [$conn]]);
        break; // Successfully connected
    } catch (PDOException $e) {
        $last_exception = $e;
    }
}

if (!$conn) {
    // Local MySQL Fallback if configured remote DB fails to connect
    try {
        $conn = new CompatiblePDO("mysql:host=127.0.0.1;port=3306;dbname=ars_junction;charset=utf8mb4", "root", "", [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_TIMEOUT => 3,
        ]);
        $conn->setAttribute(PDO::ATTR_STATEMENT_CLASS, ['CompatiblePDOStatement', [$conn]]);
    } catch (PDOException $e) {
        if ($last_exception) {
            error_log("Database connection warning: " . $last_exception->getMessage());
        }
        $conn = null;
    }
}

class CookieSessionHandler implements SessionHandlerInterface {
    private $key;
    private $cookie_name = 'ARS_SESSION';

    public function __construct() {
        $secret = getenv('SESSION_SECRET') ?: 'default_safe_session_secret_1055';
        $this->key = hash('sha256', $secret, true);
    }

    public function open($savePath, $sessionName): bool {
        return true;
    }

    public function close(): bool {
        return true;
    }

    public function read($id): string {
        if (isset($_COOKIE[$this->cookie_name])) {
            $data = $_COOKIE[$this->cookie_name];
            $decoded = base64_decode($data);
            if ($decoded !== false) {
                $iv_length = openssl_cipher_iv_length('aes-256-cbc');
                $iv = substr($decoded, 0, $iv_length);
                $encrypted = substr($decoded, $iv_length);
                $decrypted = openssl_decrypt($encrypted, 'aes-256-cbc', $this->key, 0, $iv);
                if ($decrypted !== false) {
                    return $decrypted;
                }
            }
        }
        return '';
    }

    public function write($id, $data): bool {
        if (headers_sent()) {
            return false;
        }
        $iv_length = openssl_cipher_iv_length('aes-256-cbc');
        $iv = openssl_random_pseudo_bytes($iv_length);
        $encrypted = openssl_encrypt($data, 'aes-256-cbc', $this->key, 0, $iv);
        if ($encrypted !== false) {
            $cookie_val = base64_encode($iv . $encrypted);
            $secure = isset($_SERVER['HTTPS']) || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
            setcookie($this->cookie_name, $cookie_val, [
                'expires' => 0,
                'path' => '/',
                'secure' => $secure,
                'httponly' => true,
                'samesite' => 'Lax'
            ]);
            return true;
        }
        return false;
    }

    public function destroy($id): bool {
        if (headers_sent()) {
            return false;
        }
        $secure = isset($_SERVER['HTTPS']) || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
        setcookie($this->cookie_name, '', [
            'expires' => time() - 3600,
            'path' => '/',
            'secure' => $secure,
            'httponly' => true,
            'samesite' => 'Lax'
        ]);
        return true;
    }

    public function gc($maxlifetime): int|false {
        return true;
    }
}

if (session_status() === PHP_SESSION_NONE) {
    $handler = new CookieSessionHandler();
    session_set_save_handler($handler, true);
    session_start();
}

// Load RateLimiter module
require_once __DIR__ . '/RateLimiter.php';

// Load Validator module
require_once __DIR__ . '/Validator.php';
?>

