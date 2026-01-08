<?php
// Usage: php scripts/fix_legacy_columns.php
require __DIR__ . '/../vendor/autoload.php';
if (class_exists(\Symfony\Component\Dotenv\Dotenv::class) && file_exists(__DIR__ . '/../.env')) {
    (new \Symfony\Component\Dotenv\Dotenv())->load(__DIR__ . '/../.env');
}
use App\Kernel;
$kernel = new Kernel('dev', true);
$kernel->boot();
$container = $kernel->getContainer();
$doctrine = $container->get('doctrine');
$conn = $doctrine->getConnection();

// Legacy columns that should be nullable
$columnsToFix = [
    'from_user_id' => 'INT DEFAULT NULL',
    'handled_by_id' => 'INT DEFAULT NULL',
    'read_at' => 'DATETIME DEFAULT NULL',
];

$table = 'support_message';
$modified = [];

foreach ($columnsToFix as $column => $newDef) {
    try {
        $conn->executeStatement("ALTER TABLE `$table` MODIFY `$column` $newDef");
        $modified[] = $column;
    } catch (\Exception $e) {
        echo "Warning modifying $column: " . $e->getMessage() . "\n";
    }
}

if (!empty($modified)) {
    echo "Modified columns: " . implode(', ', $modified) . "\n";
} else {
    echo "No modifications needed.\n";
}
exit(0);
