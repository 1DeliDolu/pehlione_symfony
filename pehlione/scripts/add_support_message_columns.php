<?php
// Usage: php scripts/add_support_message_columns.php
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

$table = 'support_message';
$added = [];

if (!columnExists($conn, $table, 'type')) {
    $conn->executeStatement("ALTER TABLE `$table` ADD `type` VARCHAR(20) NOT NULL DEFAULT 'customer'");
    $added[] = 'type';
}

if (!columnExists($conn, $table, 'updated_at')) {
    $conn->executeStatement("ALTER TABLE `$table` ADD `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP");
    $added[] = 'updated_at';
}

if (!columnExists($conn, $table, 'customer_name')) {
    $conn->executeStatement("ALTER TABLE `$table` ADD `customer_name` VARCHAR(180) DEFAULT NULL");
    $added[] = 'customer_name';
}

if (!columnExists($conn, $table, 'customer_email')) {
    $conn->executeStatement("ALTER TABLE `$table` ADD `customer_email` VARCHAR(180) DEFAULT NULL");
    $added[] = 'customer_email';
}

if (!columnExists($conn, $table, 'from_department_id')) {
    $conn->executeStatement("ALTER TABLE `$table` ADD `from_department_id` INT DEFAULT NULL");
    $added[] = 'from_department_id';
}

// Add FK for from_department_id if not present
$fkRows = $conn->fetchAllAssociative("SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'support_message' AND CONSTRAINT_NAME = 'FK_SUPPORT_MESSAGE_FROM_DEPARTMENT'");
if (count($fkRows) === 0) {
    try {
        $conn->executeStatement("ALTER TABLE `support_message` ADD CONSTRAINT FK_SUPPORT_MESSAGE_FROM_DEPARTMENT FOREIGN KEY (from_department_id) REFERENCES support_department (id)");
        $added[] = 'fk_from_department_id';
    } catch (\Exception $e) {
        echo "Warning adding FK: " . $e->getMessage() . "\n";
    }
}

if (!columnExists($conn, $table, 'created_by_id')) {
    $conn->executeStatement("ALTER TABLE `$table` ADD `created_by_id` INT DEFAULT NULL");
    $added[] = 'created_by_id';
}

// Add FK for created_by_id if not present
$fkRows = $conn->fetchAllAssociative("SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'support_message' AND CONSTRAINT_NAME = 'FK_SUPPORT_MESSAGE_CREATED_BY'");
if (count($fkRows) === 0) {
    try {
        $conn->executeStatement("ALTER TABLE `support_message` ADD CONSTRAINT FK_SUPPORT_MESSAGE_CREATED_BY FOREIGN KEY (created_by_id) REFERENCES user (id)");
        $added[] = 'fk_created_by_id';
    } catch (\Exception $e) {
        echo "Warning adding FK: " . $e->getMessage() . "\n";
    }
}

if (!empty($added)) {
    echo "Added columns: " . implode(', ', $added) . "\n";
} else {
    echo "No columns needed to be added.\n";
}
exit(0);
