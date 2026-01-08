<?php
// Usage: php scripts/mark_migration.php 20260108020000
if ($argc < 2) {
    echo "Usage: php scripts/mark_migration.php <versionTimestamp>\n";
    exit(1);
}
$version = $argv[1];
$versionName = 'DoctrineMigrations\\Version' . $version;
require __DIR__ . '/../vendor/autoload.php';
// Load .env so DATABASE_URL and other env vars are available when booting the Kernel
if (class_exists(\Symfony\Component\Dotenv\Dotenv::class) && file_exists(__DIR__ . '/../.env')) {
    (new \Symfony\Component\Dotenv\Dotenv())->load(__DIR__ . '/../.env');
}

use App\Kernel;

$kernel = new Kernel('dev', true);
$kernel->boot();
$container = $kernel->getContainer();
$doctrine = $container->get('doctrine');
$conn = $doctrine->getConnection();

// Check if already present
$stmt = $conn->executeQuery('SELECT COUNT(*) AS c FROM doctrine_migration_versions WHERE version = ?', [$versionName]);
$row = $stmt->fetchAssociative();
if ($row && intval($row['c']) > 0) {
    echo "Migration $versionName already present in doctrine_migration_versions.\n";
    exit(0);
}

$now = (new DateTime())->format('Y-m-d H:i:s');
$conn->insert('doctrine_migration_versions', [
    'version' => $versionName,
    'executed_at' => $now,
]);

echo "Inserted migration $versionName into doctrine_migration_versions.\n";
exit(0);
