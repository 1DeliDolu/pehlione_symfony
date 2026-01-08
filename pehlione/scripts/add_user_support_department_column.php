<?php
// Usage: php scripts/add_user_support_department_column.php
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

function columnExists($conn, $table, $column)
{
    $rows = $conn->fetchAllAssociative("SHOW COLUMNS FROM `$table` LIKE ?", [$column]);
    return count($rows) > 0;
}

function fkExists($conn, $table, $fkName)
{
    $rows = $conn->fetchAllAssociative("SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND CONSTRAINT_NAME = ?", [$table, $fkName]);
    return count($rows) > 0;
}

$table = 'user';
$added = [];

if (!columnExists($conn, $table, 'support_department_id')) {
    $conn->executeStatement("ALTER TABLE `$table` ADD `support_department_id` INT DEFAULT NULL");
    $added[] = 'support_department_id';
}

// Add index if missing
$rows = $conn->fetchAllAssociative("SHOW INDEX FROM `$table` WHERE Column_name = 'support_department_id'");
if (count($rows) === 0) {
    $conn->executeStatement("CREATE INDEX IDX_USER_SUPPORT_DEPARTMENT ON `user` (support_department_id)");
    $added[] = 'index_IDX_USER_SUPPORT_DEPARTMENT';
}

// Add FK if support_department table exists and FK not present
$hasDept = $conn->fetchOne("SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'support_department'");
if ($hasDept && !fkExists($conn, 'user', 'FK_USER_SUPPORT_DEPARTMENT')) {
    try {
        $conn->executeStatement("ALTER TABLE `user` ADD CONSTRAINT FK_USER_SUPPORT_DEPARTMENT FOREIGN KEY (support_department_id) REFERENCES support_department (id)");
        $added[] = 'fk_FK_USER_SUPPORT_DEPARTMENT';
    } catch (\Exception $e) {
        echo "Warning adding FK: " . $e->getMessage() . "\n";
    }
}

if (!empty($added)) {
    echo "Added: " . implode(', ', $added) . "\n";
} else {
    echo "No changes required.\n";
}
exit(0);
