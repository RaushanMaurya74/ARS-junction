<?php
ob_start();
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
$host = getenv('DB_HOST') ?: getenv('SUPABASE_DB_HOST');
$port = getenv('DB_PORT') ?: getenv('SUPABASE_DB_PORT');
$dbname = getenv('DB_NAME') ?: getenv('SUPABASE_DB_NAME');
$username = getenv('DB_USER') ?: getenv('SUPABASE_DB_USER');
$password = getenv('DB_PASSWORD') ?: getenv('SUPABASE_DB_PASSWORD');
$driver = getenv('DB_DRIVER') ?: getenv('SUPABASE_DB_DRIVER') ?: 'mysql';

// If any of the Supabase specific vars are set, default driver to pgsql
if (getenv('SUPABASE_DB_HOST') || getenv('SUPABASE_DB_PASSWORD')) {
    $driver = 'pgsql';
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
    // Update password if it is set to the Vercel placeholder/incorrect value
    if ($password === 'Maurya1055@') {
        $password = 'Maurya1055@#!';
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
        // Ezyro Production Database
        $host = 'sql109.ezyro.com';
        $port = '3306';
        $dbname = 'ezyro_38647338_ars_junction';
        $username = 'ezyro_38647338';
        $password = 'Maurya1055@';
        $driver = 'mysql';
    }
}

// Build DSN depending on driver
if ($driver === 'pgsql') {
    $port = $port ?: '5432';
    $dsn = "pgsql:host={$host};port={$port};dbname={$dbname};options='--client_encoding=UTF8'";
} else {
    $port = $port ?: '3306';
    $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4";
}

try {
    $conn = new CompatiblePDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
    
    // Set custom statement class
    $conn->setAttribute(PDO::ATTR_STATEMENT_CLASS, ['CompatiblePDOStatement', [$conn]]);
} catch (PDOException $e) {
    // If it is an API request, do not die; let the API return a clean JSON error response
    if (strpos($_SERVER['REQUEST_URI'] ?? '', 'api/') !== false || strpos($_SERVER['PHP_SELF'] ?? '', 'api/') !== false) {
        $conn = null;
    } else {
        die("Database connection failed: " . $e->getMessage());
    }
}

class CookieSessionHandler implements SessionHandlerInterface {
    private $key;
    private $cookie_name = 'ARS_SESSION';

    public function __construct() {
        $this->key = hash('sha256', 'Maurya1055@#!', true);
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
?>
