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
        // the indexes are not seen by the Doctrine diff tool and are left alone.
        $this->addSql('CREATE INDEX json_author ON auction_json_indexed ((item->>\'author\'))');
        $this->addSql('CREATE INDEX jsonb_author ON auction_jsonb_indexed ((item->>\'author\'))');
        $this->addSql('CREATE INDEX auction_json_gin_idx ON auction_jsonb_gin USING GIN (item)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX json_author');
        $this->addSql('DROP INDEX jsonb_author');
    }
}
