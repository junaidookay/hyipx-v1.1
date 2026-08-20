<?php
$host = getenv('DB_HOST') ?: 'db';
$port = getenv('DB_PORT') ?: '3306';
$db   = getenv('DB_DATABASE') ?: 'laravel';
$user = getenv('DB_USERNAME') ?: 'hyipx';
$pass = getenv('DB_PASSWORD') ?: 'hyipx_pass';

if (empty($host) || empty($db)) {
    echo "No DB credentials, skipping import\n";
    return;
}

echo "Connecting to database at $host:$port ($db)...\n";

try {
    $mysqli = new mysqli($host, $user, $pass, $db, (int)$port);
    if ($mysqli->connect_error) {
        echo "DB connection error: " . $mysqli->connect_error . "\n";
        return;
    }

    $result = $mysqli->query("SELECT COUNT(*) as cnt FROM information_schema.tables WHERE table_schema = '$db'");
    $row = $result->fetch_assoc();
    if ($row['cnt'] > 5) {
        echo "Database already has " . $row['cnt'] . " tables, skipping import\n";
    } else {
        $sqlFile = dirname(__FILE__) . '/install/database.sql';
        if (!file_exists($sqlFile)) {
            echo "database.sql not found at $sqlFile\n";
            $mysqli->close();
            return;
        }

        echo "Importing database...\n";
        $sql = file_get_contents($sqlFile);
        if ($mysqli->multi_query($sql)) {
            do {
                if ($result = $mysqli->store_result()) {
                    $result->free();
                }
            } while ($mysqli->more_results() && $mysqli->next_result());
        }
        echo "Database imported successfully\n";
    }

    $domain = $_SERVER['HTTP_HOST'] ?? getenv('APP_URL') ?? 'localhost';
    $domain = str_replace(['https://', 'http://'], '', $domain);
    $domain = rtrim($domain, '/');

    $mysqli->query("UPDATE general_settings SET purchase_code='LOCAL_ACTIVE', license_active=1, verified_domain='$domain', force_ssl=0, maintenance_mode=0 WHERE id=1");
    echo "License activated for $domain\n";

    $mysqli->close();

} catch (Exception $e) {
    echo "Import error: " . $e->getMessage() . "\n";
}
