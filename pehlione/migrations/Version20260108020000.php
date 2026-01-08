<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260108020000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add support departments, threads, replies, and user department linkage';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE IF NOT EXISTS support_department (id INT AUTO_INCREMENT NOT NULL, dept_code VARCHAR(50) NOT NULL, name VARCHAR(420) NOT NULL, recipient_email VARCHAR(150) NOT NULL, required_role VARCHAR(60) DEFAULT NULL, is_active TINYINT(1) NOT NULL, created_at DATETIME NOT NULL, UNIQUE INDEX UNIQ_SUPPORT_DEPARTMENT_DEPT_CODE (dept_code), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE IF NOT EXISTS support_message (id INT AUTO_INCREMENT NOT NULL, department_id INT NOT NULL, from_department_id INT DEFAULT NULL, created_by_id INT DEFAULT NULL, type VARCHAR(20) NOT NULL, status VARCHAR(20) NOT NULL, subject VARCHAR(180) NOT NULL, message LONGTEXT NOT NULL, customer_name VARCHAR(180) DEFAULT NULL, customer_email VARCHAR(180) DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, INDEX IDX_SUPPORT_MESSAGE_DEPARTMENT (department_id), INDEX IDX_SUPPORT_MESSAGE_FROM_DEPARTMENT (from_department_id), INDEX IDX_SUPPORT_MESSAGE_CREATED_BY (created_by_id), INDEX idx_support_type_status (type, status), INDEX idx_support_created_at (created_at), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE IF NOT EXISTS support_reply (id INT AUTO_INCREMENT NOT NULL, support_message_id INT NOT NULL, author_id INT DEFAULT NULL, body LONGTEXT NOT NULL, internal TINYINT(1) DEFAULT 0 NOT NULL, created_at DATETIME NOT NULL, INDEX IDX_SUPPORT_REPLY_MESSAGE (support_message_id), INDEX IDX_SUPPORT_REPLY_AUTHOR (author_id), INDEX idx_support_reply_created_at (created_at), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('ALTER TABLE support_message ADD CONSTRAINT FK_SUPPORT_MESSAGE_DEPARTMENT FOREIGN KEY (department_id) REFERENCES support_department (id)');
        $this->addSql('ALTER TABLE support_message ADD CONSTRAINT FK_SUPPORT_MESSAGE_FROM_DEPARTMENT FOREIGN KEY (from_department_id) REFERENCES support_department (id)');
        $this->addSql('ALTER TABLE support_message ADD CONSTRAINT FK_SUPPORT_MESSAGE_CREATED_BY FOREIGN KEY (created_by_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE support_reply ADD CONSTRAINT FK_SUPPORT_REPLY_MESSAGE FOREIGN KEY (support_message_id) REFERENCES support_message (id)');
        $this->addSql('ALTER TABLE support_reply ADD CONSTRAINT FK_SUPPORT_REPLY_AUTHOR FOREIGN KEY (author_id) REFERENCES user (id)');

        $this->addSql('ALTER TABLE user ADD support_department_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE user ADD CONSTRAINT FK_USER_SUPPORT_DEPARTMENT FOREIGN KEY (support_department_id) REFERENCES support_department (id)');
        $this->addSql('CREATE INDEX IDX_USER_SUPPORT_DEPARTMENT ON user (support_department_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE support_reply DROP FOREIGN KEY FK_SUPPORT_REPLY_MESSAGE');
        $this->addSql('ALTER TABLE support_reply DROP FOREIGN KEY FK_SUPPORT_REPLY_AUTHOR');
        $this->addSql('ALTER TABLE support_message DROP FOREIGN KEY FK_SUPPORT_MESSAGE_DEPARTMENT');
        $this->addSql('ALTER TABLE support_message DROP FOREIGN KEY FK_SUPPORT_MESSAGE_FROM_DEPARTMENT');
        $this->addSql('ALTER TABLE support_message DROP FOREIGN KEY FK_SUPPORT_MESSAGE_CREATED_BY');
        $this->addSql('ALTER TABLE user DROP FOREIGN KEY FK_USER_SUPPORT_DEPARTMENT');
        $this->addSql('DROP TABLE support_reply');
        $this->addSql('DROP TABLE support_message');
        $this->addSql('DROP TABLE support_department');
        $this->addSql('DROP INDEX IDX_USER_SUPPORT_DEPARTMENT ON user');
        $this->addSql('ALTER TABLE user DROP support_department_id');
    }
}
