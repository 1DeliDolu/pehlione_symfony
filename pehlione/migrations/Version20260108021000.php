<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260108021000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add support ticket SLA, priority, assignment, tags, and department SLA settings';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE support_department ADD sla_first_response_minutes INT DEFAULT 1440 NOT NULL, ADD sla_resolution_minutes INT DEFAULT 4320 NOT NULL');
        $this->addSql('ALTER TABLE support_message ADD priority VARCHAR(20) DEFAULT \'normal\' NOT NULL, ADD assigned_to_id INT DEFAULT NULL, ADD assigned_at DATETIME DEFAULT NULL, ADD first_response_due_at DATETIME DEFAULT NULL, ADD first_response_at DATETIME DEFAULT NULL, ADD resolution_due_at DATETIME DEFAULT NULL, ADD closed_at DATETIME DEFAULT NULL, ADD last_customer_message_at DATETIME DEFAULT NULL, ADD last_staff_message_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE support_message ADD CONSTRAINT FK_SUPPORT_MESSAGE_ASSIGNED_TO FOREIGN KEY (assigned_to_id) REFERENCES user (id)');
        $this->addSql('CREATE INDEX IDX_SUPPORT_MESSAGE_ASSIGNED_TO ON support_message (assigned_to_id)');
        $this->addSql('CREATE INDEX idx_support_status_updated ON support_message (status, updated_at)');
        $this->addSql('CREATE INDEX idx_support_priority_updated ON support_message (priority, updated_at)');
        $this->addSql('CREATE INDEX idx_support_dept_status_updated ON support_message (department_id, status, updated_at)');
        $this->addSql('CREATE INDEX idx_support_assigned_updated ON support_message (assigned_to_id, updated_at)');
        $this->addSql('CREATE INDEX idx_support_type_updated ON support_message (type, updated_at)');

        $this->addSql('CREATE TABLE support_tag (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(80) NOT NULL, slug VARCHAR(80) NOT NULL, UNIQUE INDEX uniq_support_tag_slug (slug), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE support_message_tag (support_message_id INT NOT NULL, support_tag_id INT NOT NULL, INDEX IDX_SUPPORT_MESSAGE_TAG_MESSAGE (support_message_id), INDEX IDX_SUPPORT_MESSAGE_TAG_TAG (support_tag_id), PRIMARY KEY(support_message_id, support_tag_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE support_message_tag ADD CONSTRAINT FK_SUPPORT_MESSAGE_TAG_MESSAGE FOREIGN KEY (support_message_id) REFERENCES support_message (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE support_message_tag ADD CONSTRAINT FK_SUPPORT_MESSAGE_TAG_TAG FOREIGN KEY (support_tag_id) REFERENCES support_tag (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE support_message_tag DROP FOREIGN KEY FK_SUPPORT_MESSAGE_TAG_MESSAGE');
        $this->addSql('ALTER TABLE support_message_tag DROP FOREIGN KEY FK_SUPPORT_MESSAGE_TAG_TAG');
        $this->addSql('DROP TABLE support_message_tag');
        $this->addSql('DROP TABLE support_tag');

        $this->addSql('DROP INDEX idx_support_status_updated ON support_message');
        $this->addSql('DROP INDEX idx_support_priority_updated ON support_message');
        $this->addSql('DROP INDEX idx_support_dept_status_updated ON support_message');
        $this->addSql('DROP INDEX idx_support_assigned_updated ON support_message');
        $this->addSql('DROP INDEX idx_support_type_updated ON support_message');
        $this->addSql('DROP INDEX IDX_SUPPORT_MESSAGE_ASSIGNED_TO ON support_message');
        $this->addSql('ALTER TABLE support_message DROP FOREIGN KEY FK_SUPPORT_MESSAGE_ASSIGNED_TO');
        $this->addSql('ALTER TABLE support_message DROP priority, DROP assigned_to_id, DROP assigned_at, DROP first_response_due_at, DROP first_response_at, DROP resolution_due_at, DROP closed_at, DROP last_customer_message_at, DROP last_staff_message_at');

        $this->addSql('ALTER TABLE support_department DROP sla_first_response_minutes, DROP sla_resolution_minutes');
    }
}
