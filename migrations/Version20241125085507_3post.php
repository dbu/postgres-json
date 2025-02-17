<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20241125085507_3post extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Additional manual steps for things after the generated schema is applied';
    }

    public function up(Schema $schema): void
    {
        // indexes on JSON fields can not be defined in Doctrine.
        // this index is seen by doctrine and breaks the schema tool: https://github.com/doctrine/dbal/issues/6781
        $this->addSql('CREATE INDEX json_author ON auction_json_indexed ( (CAST(item->>\'$.author\' AS CHAR(255))) )');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX json_author');
    }
}
