<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250804084504 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE payment_transactions_tbl ADD billing_address_first_name VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE payment_transactions_tbl ADD billing_address_last_name VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE payment_transactions_tbl ADD billing_address_address1 VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE payment_transactions_tbl ADD billing_address_address2 VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE payment_transactions_tbl ADD billing_address_city VARCHAR(100) NOT NULL');
        $this->addSql('ALTER TABLE payment_transactions_tbl ADD billing_address_state VARCHAR(100) NOT NULL');
        $this->addSql('ALTER TABLE payment_transactions_tbl ADD billing_address_postal VARCHAR(20) NOT NULL');
        $this->addSql('ALTER TABLE payment_transactions_tbl ADD billing_address_country VARCHAR(5) NOT NULL');
        $this->addSql('ALTER TABLE payment_transactions_tbl ADD billing_address_email VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE payment_transactions_tbl ADD billing_address_phone VARCHAR(30) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE payment_transactions_tbl DROP billing_address_first_name');
        $this->addSql('ALTER TABLE payment_transactions_tbl DROP billing_address_last_name');
        $this->addSql('ALTER TABLE payment_transactions_tbl DROP billing_address_address1');
        $this->addSql('ALTER TABLE payment_transactions_tbl DROP billing_address_address2');
        $this->addSql('ALTER TABLE payment_transactions_tbl DROP billing_address_city');
        $this->addSql('ALTER TABLE payment_transactions_tbl DROP billing_address_state');
        $this->addSql('ALTER TABLE payment_transactions_tbl DROP billing_address_postal');
        $this->addSql('ALTER TABLE payment_transactions_tbl DROP billing_address_country');
        $this->addSql('ALTER TABLE payment_transactions_tbl DROP billing_address_email');
        $this->addSql('ALTER TABLE payment_transactions_tbl DROP billing_address_phone');
    }
}
