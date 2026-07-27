<?php
require_once 'includes/db_connect.php';
require_once 'includes/functions.php';

header('Content-Type: text/plain');

$host = get_site_setting('smtp_host', 'smtp.gmail.com');
$port = (int)get_site_setting('smtp_port', '465');
$username = get_site_setting('smtp_username', 'officialarsjunction@gmail.com');
$password = get_site_setting('smtp_password', '');
$encryption = get_site_setting('smtp_encryption', 'ssl');

echo "SMTP Diagnostics:\n";
echo "Host: {$host}\n";
echo "Port: {$port}\n";
echo "Username: {$username}\n";
echo "Password: " . (empty($password) ? "NOT SET" : "SET (Length: " . strlen($password) . ")") . "\n";
echo "Encryption: {$encryption}\n\n";

if (empty($password)) {
    echo "ERROR: SMTP password is empty. Please set it in the Admin Settings first.\n";
    exit;
}

$socket_host = ($encryption === 'ssl') ? "ssl://{$host}" : $host;
echo "Connecting to {$socket_host} on port {$port}...\n";

$socket = @fsockopen($socket_host, $port, $errno, $errstr, 15);
if (!$socket) {
    echo "CONNECTION FAILED: {$errstr} ({$errno})\n";
    echo "Troubleshooting Tips:\n";
    echo "1. Verify that 'openssl' extension is enabled in your php.ini.\n";
    echo "2. Check if your hosting provider/firewall blocks outbound connection to port {$port}.\n";
    exit;
}

echo "Connected successfully!\n\n";

$readResponse = function($socket, $expectedCode) {
    $response = "";
    while ($str = fgets($socket, 515)) {
        $response .= $str;
        if (substr($str, 3, 1) === " ") {
            break;
        }
    }
    echo "S: " . trim($response) . "\n";
    $code = (int)substr($response, 0, 3);
    return $code === $expectedCode;
};

if (!$readResponse($socket, 220)) { echo "Error reading greeting.\n"; fclose($socket); exit; }

echo "C: EHLO localhost\n";
fwrite($socket, "EHLO localhost\r\n");
if (!$readResponse($socket, 250)) { echo "Error in EHLO.\n"; fclose($socket); exit; }

if ($encryption === 'tls') {
    echo "C: STARTTLS\n";
    fwrite($socket, "STARTTLS\r\n");
    if (!$readResponse($socket, 220)) { echo "Error in STARTTLS.\n"; fclose($socket); exit; }
    
    echo "Starting TLS encryption handshake...\n";
    if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
        echo "TLS Handshake failed.\n";
        fclose($socket);
        exit;
    }
    echo "TLS handshake succeeded!\n\n";
    
    echo "C: EHLO localhost\n";
    fwrite($socket, "EHLO localhost\r\n");
    if (!$readResponse($socket, 250)) { echo "Error in post-TLS EHLO.\n"; fclose($socket); exit; }
}

echo "C: AUTH LOGIN\n";
fwrite($socket, "AUTH LOGIN\r\n");
if (!$readResponse($socket, 334)) { echo "Error starting AUTH.\n"; fclose($socket); exit; }

echo "C: [Username base64]\n";
fwrite($socket, base64_encode($username) . "\r\n");
if (!$readResponse($socket, 334)) { echo "Error entering username.\n"; fclose($socket); exit; }

echo "C: [Password base64]\n";
fwrite($socket, base64_encode($password) . "\r\n");
if (!$readResponse($socket, 235)) { 
    echo "AUTHENTICATION FAILED. Please double-check your App Password.\n"; 
    fclose($socket); 
    exit; 
}

echo "AUTHENTICATED SUCCESSFULLY!\n\n";

echo "C: MAIL FROM:<{$username}>\n";
fwrite($socket, "MAIL FROM:<{$username}>\r\n");
if (!$readResponse($socket, 250)) { fclose($socket); exit; }

echo "C: RCPT TO:<{$username}>\n";
fwrite($socket, "RCPT TO:<{$username}>\r\n");
if (!$readResponse($socket, 250)) { fclose($socket); exit; }

echo "C: DATA\n";
fwrite($socket, "DATA\r\n");
if (!$readResponse($socket, 354)) { fclose($socket); exit; }

$headers = "MIME-Version: 1.0\r\n";
$headers .= "Content-type:text/html;charset=UTF-8\r\n";
$headers .= "From: SMTP Diagnostic <{$username}>\r\n";
$headers .= "To: {$username}\r\n";
$headers .= "Subject: SMTP Test Mail\r\n";

$body = "<h1>SMTP Test Success!</h1><p>Your SMTP mail configuration is fully functional.</p>";

fwrite($socket, $headers . "\r\n" . $body . "\r\n.\r\n");
if (!$readResponse($socket, 250)) { echo "Failed to send DATA.\n"; fclose($socket); exit; }

echo "C: QUIT\n";
fwrite($socket, "QUIT\r\n");
$readResponse($socket, 221);

fclose($socket);
echo "\nTEST EMAIL SENT SUCCESSFULLY to {$username}!\n";
?>
