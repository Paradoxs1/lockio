<?php declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20181107102706 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('CREATE TABLE organization_storage_object (organization_id INT NOT NULL, storage_object_id INT NOT NULL, PRIMARY KEY(organization_id, storage_object_id))');
        $this->addSql('CREATE INDEX IDX_63BCBC5E32C8A3DE ON organization_storage_object (organization_id)');
        $this->addSql('CREATE INDEX IDX_63BCBC5ECB364924 ON organization_storage_object (storage_object_id)');
        $this->addSql('ALTER TABLE organization_storage_object ADD CONSTRAINT FK_63BCBC5E32C8A3DE FOREIGN KEY (organization_id) REFERENCES organizations (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE organization_storage_object ADD CONSTRAINT FK_63BCBC5ECB364924 FOREIGN KEY (storage_object_id) REFERENCES storage_objects (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE organizations DROP CONSTRAINT fk_427c1c7fcb364924');
        $this->addSql('DROP INDEX uniq_427c1c7fcb364924');
        $this->addSql('ALTER TABLE organizations DROP storage_object_id');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('CREATE SCHEMA public');
        $this->addSql('DROP TABLE organization_storage_object');
        $this->addSql('ALTER TABLE organizations ADD storage_object_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE organizations ADD CONSTRAINT fk_427c1c7fcb364924 FOREIGN KEY (storage_object_id) REFERENCES storage_objects (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE UNIQUE INDEX uniq_427c1c7fcb364924 ON organizations (storage_object_id)');
    }
}
