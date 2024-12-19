<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20241216173251 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE category ADD image VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE menu ADD image VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE menu_product DROP FOREIGN KEY FK_5B9119134584665A');
        $this->addSql('ALTER TABLE menu_product DROP FOREIGN KEY FK_5B911913CCD7E912');
        $this->addSql('ALTER TABLE menu_product ADD CONSTRAINT FK_5B9119134584665A FOREIGN KEY (product_id) REFERENCES product (id)');
        $this->addSql('ALTER TABLE menu_product ADD CONSTRAINT FK_5B911913CCD7E912 FOREIGN KEY (menu_id) REFERENCES menu (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE menu_product DROP FOREIGN KEY FK_5B911913CCD7E912');
        $this->addSql('ALTER TABLE menu_product DROP FOREIGN KEY FK_5B9119134584665A');
        $this->addSql('ALTER TABLE menu_product ADD CONSTRAINT FK_5B911913CCD7E912 FOREIGN KEY (menu_id) REFERENCES menu (id) ON UPDATE NO ACTION ON DELETE CASCADE');
        $this->addSql('ALTER TABLE menu_product ADD CONSTRAINT FK_5B9119134584665A FOREIGN KEY (product_id) REFERENCES product (id) ON UPDATE NO ACTION ON DELETE CASCADE');
        $this->addSql('ALTER TABLE category DROP image');
        $this->addSql('ALTER TABLE menu DROP image');
    }
}
