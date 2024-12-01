<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20241125085507_1pre extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Manually created migration for things we need in our entity but Doctrine does not generate';
    }

    public function up(Schema $schema): void
    {
        /*
         * With the default value of 4, postgres switches to full table scan when we have 10M rows.
         */
        $this->addSql('ALTER TABLESPACE pg_default SET (random_page_cost=1.1)');

        /* The expression for a generated table must be immutable. Date functions are inherently
         * mutable, because there could be time zones involved. The timezone can be specified in
         * each database connection.
         * IMHO postgres should offer a set of immutable functions that do not allow to do
         * timezone-depending things, rather than require the user to write custom functions.
         *
         * For some background, see https://gist.github.com/jgaskins/41719f1dff8eaf09855dd6af1c247d3b
         * Thanks a lot Jamie for explaining the intricacies of this subject to me!
         */
        $this->addSql('
CREATE FUNCTION text_to_timestamp(text) RETURNS TIMESTAMP
LANGUAGE sql IMMUTABLE AS
$$
  SELECT CASE
  WHEN $1 ~ \'^\d{4}-\d{2}-\d{2}( |T)\d{2}:\d{2}:\d{2}(\.\d+)?(\+00:00)?    $\' THEN
    CAST($1 AS timestamp without time zone)
  END
$$;');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP FUNCTION text_to_timestamp');
    }
}
