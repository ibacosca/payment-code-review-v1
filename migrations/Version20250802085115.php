<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250802085115 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE payment_transactions_tbl ALTER transaction_id DROP NOT NULL');
        $this->addSql('ALTER TABLE payment_transactions_tbl ALTER _used_token DROP NOT NULL');
        $this->addSql('ALTER TABLE payment_transactions_tbl ALTER last4_digits DROP NOT NULL');
        $this->addSql('ALTER TABLE payment_transactions_tbl ALTER created_at TYPE TIMESTAMP(0) WITHOUT TIME ZONE');
        $this->addSql('COMMENT ON COLUMN payment_transactions_tbl.created_at IS \'(DC2Type:datetime_immutable)\'');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE payment_transactions_tbl ALTER transaction_id SET NOT NULL');
        $this->addSql('ALTER TABLE payment_transactions_tbl ALTER _used_token SET NOT NULL');
        $this->addSql('ALTER TABLE payment_transactions_tbl ALTER last4_digits SET NOT NULL');
        $this->addSql('ALTER TABLE payment_transactions_tbl ALTER created_at TYPE TIMESTAMP(0) WITHOUT TIME ZONE');
        $this->addSql('COMMENT ON COLUMN payment_transactions_tbl.created_at IS NULL');
    }
}
