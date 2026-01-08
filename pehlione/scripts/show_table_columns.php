<?php
// Usage: php scripts/show_table_columns.php <table>
if ($argc < 2) {
    echo "Usage: php scripts/show_table_columns.php <table>\n";
    exit(1);
}
$table = $argv[1];
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
$rows = $conn->fetchAllAssociative("SHOW COLUMNS FROM `$table`");
if (!$rows) {
    echo "No columns or table not found: $table\n";
    exit(1);
}
foreach ($rows as $r) {
    echo $r['Field'] . "\t" . $r['Type'] . "\t" . $r['Null'] . "\t" . ($r['Key'] ?? '') . "\t" . ($r['Default'] ?? '') . "\t" . ($r['Extra'] ?? '') . "\n";
}
exit(0);
