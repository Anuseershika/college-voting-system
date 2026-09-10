<?php
/**
 * Database Connection Configuration
 * Uses PHP Data Objects (PDO) for secure and efficient database operations.
 * Supports local development (XAMPP) and cloud production (Render, TiDB, Aiven, etc.)
 */

// Parse DATABASE_URL / MYSQL_URL if provided (common in cloud environments)
$db_url = getenv('DATABASE_URL') ?: getenv('MYSQL_URL');
if (!empty($db_url)) {
    $parsed = parse_url($db_url);
    $host = $parsed['host'] ?? 'localhost';
    $port = $parsed['port'] ?? 3306;
    $user = $parsed['user'] ?? 'root';
    $pass = $parsed['pass'] ?? '';
    $db   = ltrim($parsed['path'] ?? '/college_voting', '/');
} else {
    $host = getenv('DB_HOST') ?: 'localhost';
    $port = getenv('DB_PORT') ?: 3306;
    $db   = getenv('DB_NAME') ?: 'college_voting';
    $user = getenv('DB_USER') ?: 'root';
    $pass = getenv('DB_PASS') !== false ? getenv('DB_PASS') : '';
}

$charset = 'utf8mb4';
$dsn = "mysql:host=$host;port=$port;dbname=$db;charset=$charset";

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

// Configure SSL for remote cloud databases (e.g., TiDB Cloud, Aiven, AWS RDS)
$is_remote = !in_array(strtolower($host), ['localhost', '127.0.0.1', '::1']);
$ssl_requested = getenv('DB_SSL');

if ($ssl_requested === 'true' || ($ssl_requested !== 'false' && $is_remote)) {
    if (defined('PDO::MYSQL_ATTR_SSL_CA') && file_exists('/etc/ssl/certs/ca-certificates.crt')) {
        $options[PDO::MYSQL_ATTR_SSL_CA] = '/etc/ssl/certs/ca-certificates.crt';
    } elseif (defined('PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT')) {
        $options[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = false;
    }
}

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
    
    // Auto-initialize tables and seed data if not yet imported
    try {
        $pdo->query("SELECT 1 FROM admin LIMIT 1");
    } catch (\PDOException $check_e) {
        $schema_file = __DIR__ . '/../database/schema.sql';
        if (file_exists($schema_file)) {
            try {
                $sql = file_get_contents($schema_file);
                // Remove CREATE DATABASE and USE statements for cloud DB compatibility
                $sql = preg_replace('/CREATE\s+DATABASE[^;]+;/is', '', $sql);
                $sql = preg_replace('/USE\s+[^;]+;/is', '', $sql);
                $pdo->exec($sql);
            } catch (\PDOException $import_err) {
                error_log("Schema auto-import notice: " . $import_err->getMessage());
            }
        }
    }
} catch (\PDOException $e) {
    $error_msg = $e->getMessage();
    if (php_sapi_name() !== 'cli') {
        http_response_code(500);
        ?>
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Database Configuration Required - College Voting</title>
            <style>
                body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #f8fafc; color: #1e293b; display: flex; justify-content: center; align-items: center; min-height: 100vh; margin: 0; padding: 20px; box-sizing: border-box; }
                .card { background: white; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.08); max-width: 620px; width: 100%; padding: 32px; border-top: 4px solid #3b82f6; }
                h1 { font-size: 1.35rem; color: #0f172a; margin-top: 0; }
                p { line-height: 1.6; color: #475569; font-size: 0.95rem; }
                pre { background: #f1f5f9; padding: 12px 16px; border-radius: 8px; font-size: 0.85rem; color: #e11d48; overflow-x: auto; white-space: pre-wrap; word-break: break-word; }
                .env-table { width: 100%; border-collapse: collapse; margin: 16px 0; font-size: 0.88rem; }
                .env-table th, .env-table td { padding: 8px 12px; text-align: left; border-bottom: 1px solid #e2e8f0; }
                .env-table th { background: #f8fafc; color: #64748b; font-weight: 600; }
                .env-table code { background: #e2e8f0; padding: 2px 6px; border-radius: 4px; font-size: 0.82rem; }
                .badge { display: inline-block; background: #e0f2fe; color: #0369a1; padding: 4px 10px; border-radius: 9999px; font-size: 0.75rem; font-weight: 600; margin-bottom: 12px; }
            </style>
        </head>
        <body>
            <div class="card">
                <span class="badge">Deployment Setup Notice</span>
                <h1>Database Connection Setup Required</h1>
                <p>The application could not connect to MySQL. If you are deploying on <strong>Render</strong>, please add your database credentials under your Web Service's <strong>Environment Variables</strong> tab:</p>
                <table class="env-table">
                    <tr><th>Variable Name</th><th>Example Value</th></tr>
                    <tr><td><code>DB_HOST</code></td><td>gateway01.us-east-1.prod.aws.tidbcloud.com</td></tr>
                    <tr><td><code>DB_PORT</code></td><td>4000</td></tr>
                    <tr><td><code>DB_NAME</code></td><td>college_voting (or test)</td></tr>
                    <tr><td><code>DB_USER</code></td><td>your_username.root</td></tr>
                    <tr><td><code>DB_PASS</code></td><td>your_password</td></tr>
                </table>
                <p><strong>Raw connection error:</strong></p>
                <pre><?php echo htmlspecialchars($error_msg); ?></pre>
            </div>
        </body>
        </html>
        <?php
        exit;
    } else {
        die("Database connection failed: " . $error_msg . "\n");
    }
}
?>
