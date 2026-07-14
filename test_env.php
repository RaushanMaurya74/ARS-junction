<?php
header('Content-Type: application/json');
$vars = [
    'DB_HOST' => getenv('DB_HOST'),
    'SUPABASE_DB_HOST' => getenv('SUPABASE_DB_HOST'),
    'DB_USER' => getenv('DB_USER'),
    'SUPABASE_DB_USER' => getenv('SUPABASE_DB_USER'),
    'DB_NAME' => getenv('DB_NAME'),
    'SUPABASE_DB_NAME' => getenv('SUPABASE_DB_NAME'),
    'DB_PORT' => getenv('DB_PORT'),
    'SUPABASE_DB_PORT' => getenv('SUPABASE_DB_PORT'),
    'DB_DRIVER' => getenv('DB_DRIVER'),
    'SUPABASE_DB_DRIVER' => getenv('SUPABASE_DB_DRIVER'),
    'DB_PASSWORD_LEN' => strlen(getenv('DB_PASSWORD') ?: ''),
    'SUPABASE_DB_PASSWORD_LEN' => strlen(getenv('SUPABASE_DB_PASSWORD') ?: ''),
    'DB_PASSWORD_START' => substr(getenv('DB_PASSWORD') ?: '', 0, 1),
    'SUPABASE_DB_PASSWORD_START' => substr(getenv('SUPABASE_DB_PASSWORD') ?: '', 0, 1),
    '_ENV_SUPABASE_DB_PASSWORD_LEN' => strlen($_ENV['SUPABASE_DB_PASSWORD'] ?? ''),
    '_SERVER_SUPABASE_DB_PASSWORD_LEN' => strlen($_SERVER['SUPABASE_DB_PASSWORD'] ?? '')
];
echo json_encode($vars, JSON_PRETTY_PRINT);
?>
