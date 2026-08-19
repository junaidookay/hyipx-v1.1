<?php
$host = getenv('DB_HOST') ?: getenv('MYSQLHOST') ?: '127.0.0.1';
$port = getenv('DB_PORT') ?: getenv('MYSQLPORT') ?: '3306';
$db   = getenv('DB_DATABASE') ?: getenv('MYSQLDATABASE') ?: 'laravel';
$user = getenv('DB_USERNAME') ?: getenv('MYSQLUSER') ?: 'root';
$pass = getenv('DB_PASSWORD') ?: getenv('MYSQLPASSWORD') ?: '';

if (empty($host) || empty($db)) {
    echo "No DB credentials, skipping import\n";
    return;
}

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

    $domain = $_SERVER['HTTP_HOST'] ?? getenv('RAILWAY_PUBLIC_DOMAIN') ?? 'localhost';
    $mysqli->query("UPDATE general_settings SET purchase_code='RAILWAY_ACTIVE', license_active=1, verified_domain='$domain' WHERE id=1");
    echo "License activated for $domain\n";

    $mysqli->close();

} catch (Exception $e) {
    echo "Import error: " . $e->getMessage() . "\n";
}
