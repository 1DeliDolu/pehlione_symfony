<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260108004632 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE shop_order (id INT AUTO_INCREMENT NOT NULL, order_number VARCHAR(32) NOT NULL, status VARCHAR(20) NOT NULL, currency VARCHAR(3) NOT NULL, subtotal_amount INT NOT NULL, total_amount INT NOT NULL, created_at DATETIME NOT NULL, shipping_first_name VARCHAR(100) NOT NULL, shipping_last_name VARCHAR(100) NOT NULL, shipping_phone VARCHAR(30) DEFAULT NULL, shipping_line1 VARCHAR(255) NOT NULL, shipping_line2 VARCHAR(255) DEFAULT NULL, shipping_city VARCHAR(120) NOT NULL, shipping_postal_code VARCHAR(20) NOT NULL, shipping_region VARCHAR(120) DEFAULT NULL, shipping_country_code VARCHAR(2) NOT NULL, user_id INT NOT NULL, INDEX IDX_323FC9CAA76ED395 (user_id), UNIQUE INDEX uniq_shop_order_number (order_number), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE shop_order ADD CONSTRAINT FK_323FC9CAA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE order_item DROP FOREIGN KEY `FK_52EA1F094584665A`');
        $this->addSql('ALTER TABLE order_item DROP FOREIGN KEY `FK_52EA1F09E238517C`');
        $this->addSql('ALTER TABLE order_item ADD CONSTRAINT FK_52EA1F094584665A FOREIGN KEY (product_id) REFERENCES product (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE order_item ADD CONSTRAINT FK_52EA1F09E238517C FOREIGN KEY (order_ref_id) REFERENCES shop_order (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE shop_order DROP FOREIGN KEY FK_323FC9CAA76ED395');
        $this->addSql('DROP TABLE shop_order');
        $this->addSql('ALTER TABLE order_item DROP FOREIGN KEY FK_52EA1F09E238517C');
        $this->addSql('ALTER TABLE order_item DROP FOREIGN KEY FK_52EA1F094584665A');
        $this->addSql('ALTER TABLE order_item ADD CONSTRAINT `FK_52EA1F09E238517C` FOREIGN KEY (order_ref_id) REFERENCES `order` (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE order_item ADD CONSTRAINT `FK_52EA1F094584665A` FOREIGN KEY (product_id) REFERENCES product (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
    }
}
