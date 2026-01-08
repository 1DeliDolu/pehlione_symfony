<?php
// Usage: php scripts/fix_from_email_column.php
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

// Modify from_email to have a default empty string
try {
    $conn->executeStatement("ALTER TABLE `support_message` MODIFY `from_email` VARCHAR(180) DEFAULT ''");
    echo "Modified from_email to have default empty string.\n";
} catch (\Exception $e) {
    echo "Error modifying from_email: " . $e->getMessage() . "\n";
}

// Also ensure from_name and other legacy columns have defaults (TEXT/LONGTEXT cannot have defaults)
try {
    $conn->executeStatement("ALTER TABLE `support_message` MODIFY `from_name` VARCHAR(180) DEFAULT NULL");
    $conn->executeStatement("ALTER TABLE `support_message` MODIFY `subject` VARCHAR(140) DEFAULT ''");
    echo "Updated other legacy columns with defaults.\n";
} catch (\Exception $e) {
    echo "Error updating other columns: " . $e->getMessage() . "\n";
}

exit(0);
