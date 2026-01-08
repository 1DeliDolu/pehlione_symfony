<?php
// Usage: php scripts/create_tag_tables.php
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

$created = [];

// Create support_tag table
try {
    $conn->executeStatement("CREATE TABLE IF NOT EXISTS support_tag (
        id INT AUTO_INCREMENT NOT NULL,
        name VARCHAR(80) NOT NULL,
        slug VARCHAR(80) NOT NULL,
        UNIQUE INDEX uniq_support_tag_slug (slug),
        PRIMARY KEY(id)
    ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB");
    $created[] = 'support_tag';
} catch (\Exception $e) {
    echo "Error creating support_tag: " . $e->getMessage() . "\n";
}

// Create support_message_tag table
try {
    $conn->executeStatement("CREATE TABLE IF NOT EXISTS support_message_tag (
        support_message_id INT NOT NULL,
        support_tag_id INT NOT NULL,
        INDEX IDX_SUPPORT_MESSAGE_TAG_MESSAGE (support_message_id),
        INDEX IDX_SUPPORT_MESSAGE_TAG_TAG (support_tag_id),
        PRIMARY KEY(support_message_id, support_tag_id)
    ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB");
    $created[] = 'support_message_tag';
} catch (\Exception $e) {
    echo "Error creating support_message_tag: " . $e->getMessage() . "\n";
}

// Add FKs for support_message_tag
try {
    $fkRows = $conn->fetchAllAssociative("SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'support_message_tag' AND CONSTRAINT_NAME = 'FK_SUPPORT_MESSAGE_TAG_MESSAGE'");
    if (count($fkRows) === 0) {
        $conn->executeStatement("ALTER TABLE support_message_tag ADD CONSTRAINT FK_SUPPORT_MESSAGE_TAG_MESSAGE FOREIGN KEY (support_message_id) REFERENCES support_message (id) ON DELETE CASCADE");
    }
} catch (\Exception $e) {
    echo "Error adding FK_SUPPORT_MESSAGE_TAG_MESSAGE: " . $e->getMessage() . "\n";
}

try {
    $fkRows = $conn->fetchAllAssociative("SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'support_message_tag' AND CONSTRAINT_NAME = 'FK_SUPPORT_MESSAGE_TAG_TAG'");
    if (count($fkRows) === 0) {
        $conn->executeStatement("ALTER TABLE support_message_tag ADD CONSTRAINT FK_SUPPORT_MESSAGE_TAG_TAG FOREIGN KEY (support_tag_id) REFERENCES support_tag (id) ON DELETE CASCADE");
    }
} catch (\Exception $e) {
    echo "Error adding FK_SUPPORT_MESSAGE_TAG_TAG: " . $e->getMessage() . "\n";
}

if (!empty($created)) {
    echo "Created tables: " . implode(', ', $created) . "\n";
} else {
    echo "Tables already exist.\n";
}
exit(0);
