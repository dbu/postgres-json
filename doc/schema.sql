-- if you use doctrine, run `bin/console doctrine:migrations:migrate` instead of this script.

-- tweak doctrine configuration to motivate it to use indexes when possible
ALTER TABLESPACE pg_default SET (random_page_cost=1.1);

CREATE OR REPLACE FUNCTION text_to_timestamp(text) RETURNS TIMESTAMP
LANGUAGE sql IMMUTABLE AS
$$
  SELECT CASE
  WHEN $1 ~ '^\d{4}-\d{2}-\d{2}[ T]\d{2}:\d{2}:\d{2}(\.\d+)?(\+00:00)?$' THEN
    CAST($1 AS timestamp without time zone)
  END
$$;

CREATE TABLE auction_generated_fields (id SERIAL NOT NULL, title VARCHAR(255) generated always as (item->>'title') stored NOT NULL, start_date TIMESTAMP(0) generated always as (text_to_timestamp(item->>'startDate')) stored NOT NULL, end_date TIMESTAMP(0) generated always as (text_to_timestamp(item->>'endDate')) stored NOT NULL, author VARCHAR(255) generated always as (item->>'author') stored, current_price INT DEFAULT NULL, item JSONB NOT NULL, PRIMARY KEY(id));

CREATE TABLE auction_generated_fields_indexed (id SERIAL NOT NULL, title VARCHAR(255) generated always as (item->>'title') stored NOT NULL, start_date TIMESTAMP(0) generated always as (text_to_timestamp(item->>'startDate')) stored NOT NULL, end_date TIMESTAMP(0) generated always as (text_to_timestamp(item->>'endDate')) stored NOT NULL, author VARCHAR(255) generated always as (item->>'author') stored, current_price INT DEFAULT NULL, item JSONB NOT NULL, PRIMARY KEY(id));
CREATE INDEX IDX_D638BFC295275AB8 ON auction_generated_fields_indexed (start_date);
CREATE INDEX IDX_D638BFC2BDAFD8C8 ON auction_generated_fields_indexed (author);

CREATE TABLE auction_json (id SERIAL NOT NULL, title VARCHAR(255) NOT NULL, start_date TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, end_date TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, current_price INT DEFAULT NULL, item JSON NOT NULL, PRIMARY KEY(id));

CREATE TABLE auction_json_indexed (id SERIAL NOT NULL, title VARCHAR(255) NOT NULL, start_date TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, end_date TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, current_price INT DEFAULT NULL, item JSON NOT NULL, PRIMARY KEY(id));
CREATE INDEX IDX_5BF05BCF95275AB8 ON auction_json_indexed (start_date);
CREATE INDEX json_author ON auction_json_indexed ((item->>'author'));

CREATE TABLE auction_jsonb (id SERIAL NOT NULL, title VARCHAR(255) NOT NULL, start_date TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, end_date TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, current_price INT DEFAULT NULL, item JSONB NOT NULL, PRIMARY KEY(id));

CREATE TABLE auction_jsonb_indexed (id SERIAL NOT NULL, title VARCHAR(255) NOT NULL, start_date TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, end_date TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, current_price INT DEFAULT NULL, item JSONB NOT NULL, PRIMARY KEY(id));
CREATE INDEX IDX_F94BA2B195275AB8 ON auction_jsonb_indexed (start_date);
CREATE INDEX jsonb_author ON auction_jsonb_indexed ((item->>'author'));

CREATE TABLE auction_jsonb_gin (id SERIAL NOT NULL, title VARCHAR(255) NOT NULL, start_date TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, end_date TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, current_price INT DEFAULT NULL, item JSONB NOT NULL, PRIMARY KEY(id));
CREATE INDEX auction_json_gin_idx ON auction_jsonb_gin USING GIN (item);