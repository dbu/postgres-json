-- if you use doctrine, run `bin/console doctrine:migrations:migrate` instead of this script.

CREATE TABLE auction_generated_fields (id SERIAL NOT NULL, title VARCHAR(255) generated always as (item->>'$.title') stored NOT NULL, start_date DATETIME generated always as (item->>'$.startDate') stored NOT NULL, end_date DATETIME generated always as (item->>'$.endDate') stored NOT NULL, author VARCHAR(255) generated always as (item->>'$.author') stored, current_price INT DEFAULT NULL, item JSON NOT NULL, PRIMARY KEY(id));

CREATE TABLE auction_generated_fields_indexed (id SERIAL NOT NULL, title VARCHAR(255) generated always as (item->>'$.title') stored NOT NULL, start_date DATETIME generated always as (item->>'$.startDate') stored NOT NULL, end_date DATETIME generated always as (item->>'$.endDate') stored NOT NULL, author VARCHAR(255) generated always as (item->>'$.author') stored, current_price INT DEFAULT NULL, item JSON NOT NULL, PRIMARY KEY(id));
CREATE INDEX IDX_D638BFC295275AB8 ON auction_generated_fields_indexed (start_date);
CREATE INDEX IDX_D638BFC2BDAFD8C8 ON auction_generated_fields_indexed (author);

CREATE TABLE auction_json (id SERIAL NOT NULL, title VARCHAR(255) NOT NULL, start_date DATETIME NOT NULL, end_date DATETIME NOT NULL, current_price INT DEFAULT NULL, item JSON NOT NULL, PRIMARY KEY(id));

CREATE TABLE auction_json_indexed (id SERIAL NOT NULL, title VARCHAR(255) NOT NULL, start_date DATETIME  NOT NULL, end_date DATETIME NOT NULL, current_price INT DEFAULT NULL, item JSON NOT NULL, PRIMARY KEY(id));
CREATE INDEX IDX_5BF05BCF95275AB8 ON auction_json_indexed (start_date);
CREATE INDEX json_author ON auction_json_indexed ((CAST(item->>'$.author' AS CHAR(255))));
