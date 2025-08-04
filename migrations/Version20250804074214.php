<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250804074214 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE payment_transactions_tbl ADD parent_transaction_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE payment_transactions_tbl ADD CONSTRAINT FK_5BB7A228311DBF04 FOREIGN KEY (parent_transaction_id) REFERENCES payment_transactions_tbl (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_5BB7A228311DBF04 ON payment_transactions_tbl (parent_transaction_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE payment_transactions_tbl DROP CONSTRAINT FK_5BB7A228311DBF04');
        $this->addSql('DROP INDEX IDX_5BB7A228311DBF04');
        $this->addSql('ALTER TABLE payment_transactions_tbl DROP parent_transaction_id');
    }
}
