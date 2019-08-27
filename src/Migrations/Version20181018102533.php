<?php declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20181018102533 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('CREATE SEQUENCE storage_objects_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE storage_objects (id INT NOT NULL, url VARCHAR(255) NOT NULL, access_key VARCHAR(255) NOT NULL, secret_key VARCHAR(255) NOT NULL, bucket_id VARCHAR(255) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('ALTER TABLE organizations ADD storage_object_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE organizations ADD CONSTRAINT FK_427C1C7FCB364924 FOREIGN KEY (storage_object_id) REFERENCES storage_objects (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_427C1C7FCB364924 ON organizations (storage_object_id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE organizations DROP CONSTRAINT FK_427C1C7FCB364924');
        $this->addSql('DROP SEQUENCE storage_objects_id_seq CASCADE');
        $this->addSql('DROP TABLE storage_objects');
        $this->addSql('DROP INDEX UNIQ_427C1C7FCB364924');
        $this->addSql('ALTER TABLE organizations DROP storage_object_id');
    }
}
