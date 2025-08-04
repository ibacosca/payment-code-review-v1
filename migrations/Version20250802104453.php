<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250802104453 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE subscriptions_tbl (id SERIAL NOT NULL, initial_transaction_id INT NOT NULL, uuid UUID NOT NULL, payment_token VARCHAR(255) NOT NULL, amount DOUBLE PRECISION NOT NULL, currency VARCHAR(3) NOT NULL, status VARCHAR(20) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, next_billing_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_C110F629E3E91058 ON subscriptions_tbl (initial_transaction_id)');
        $this->addSql('COMMENT ON COLUMN subscriptions_tbl.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN subscriptions_tbl.next_billing_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE subscriptions_tbl ADD CONSTRAINT FK_C110F629E3E91058 FOREIGN KEY (initial_transaction_id) REFERENCES payment_transactions_tbl (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE subscriptions_tbl DROP CONSTRAINT FK_C110F629E3E91058');
        $this->addSql('DROP TABLE subscriptions_tbl');
    }
}
