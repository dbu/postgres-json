SELECT COUNT(*) FROM auction_json WHERE item->>'author' = 'Author 1';
SELECT COUNT(*) FROM auction_json_indexed WHERE item->>'author' = 'Author 1';
SELECT COUNT(*) FROM auction_jsonb WHERE item->>'author' = 'Author 1';
SELECT COUNT(*) FROM auction_jsonb_indexed WHERE item->>'author' = 'Author 1';
SELECT COUNT(*) FROM auction_jsonb_gin WHERE item->>'author' = 'Author 1';
SELECT COUNT(*) FROM auction_generated_fields WHERE author = 'Author 1';
SELECT COUNT(*) FROM auction_generated_fields_indexed WHERE author = 'Author 1';

-- extract the value from json, rather than compare with a json string
SELECT COUNT(*) FROM auction_jsonb WHERE item->>'author' = 'Author 1';
SELECT COUNT(*) FROM auction_jsonb WHERE item->'author' = '"Author 1"';


-- alternatively, use json path functions
-- json path is very powerful, similar to xpath or css selectors
-- CREATE INDEX jsonb_author_path ON auction_jsonb_indexed ((json_value(item, '$.author')));
-- new in postgres 17
SELECT COUNT(*) FROM auction_jsonb_indexed WHERE json_value(item, '$.author') = 'Author 1';
SELECT COUNT(*) FROM auction_jsonb_gin WHERE json_value(item, '$.author') = 'Author 1';
-- with older postgres, you have jsonb_path_query but that returns a set, not a value
SELECT jsonb_path_query(item, '$.author') FROM auction_jsonb_indexed LIMIT 1;

SELECT COUNT(*) FROM auction_jsonb_indexed WHERE jsonb_path_exists(item, '$.author');


-- alternatively access elements in object with a (potentially multi level) array of keys
-- does not share index with arrow notation, but can have its own index
-- CREATE INDEX jsonb_author_contain ON auction_jsonb_indexed ((item #>> '{author}'));

SELECT item #> '{"author"}' AS author  FROM auction_jsonb_indexed LIMIT 1;
SELECT item #>> '{"author"}' AS author  FROM auction_jsonb_indexed LIMIT 1;
SELECT COUNT(*) FROM auction_jsonb WHERE item #>> '{"author"}' = 'Author 1';
SELECT COUNT(*) FROM auction_jsonb_indexed WHERE item #>> '{"author"}' = 'Author 1';
SELECT COUNT(*) FROM auction_jsonb_gin WHERE item #>> '{"author"}' = 'Author 1';

-- or you can access fields like you would in PHP
-- again does not share index with arrow or # notation, but can have its own index
-- CREATE INDEX jsonb_author_hashnotation ON auction_jsonb_indexed ((item['author']));

SELECT COUNT(*) FROM auction_jsonb_indexed WHERE item['author']->>0 = 'Author 1';
SELECT COUNT(*) FROM auction_jsonb_gin WHERE item['author']->>0 = 'Author 1';
-- semantically equivalent but massively slower
SELECT COUNT(*) FROM auction_jsonb_indexed WHERE item['author'] = '"Author 1"';

-- querying for containing value (only available with jsonb)
-- this does not use the index, but can use the GIN
SELECT COUNT(*) FROM auction_jsonb WHERE item @> '{"author": "Author 1"}';
SELECT COUNT(*) FROM auction_jsonb_indexed WHERE item @> '{"author": "Author 1"}';
SELECT COUNT(*) FROM auction_jsonb_gin WHERE item @> '{"author": "Author 1"}';

-- has field (? or jsonb_exists are the same construct)
SELECT COUNT(*) FROM auction_jsonb_indexed WHERE item ? 'author';
SELECT COUNT(*) FROM auction_jsonb WHERE jsonb_exists(item, 'author');
SELECT COUNT(*) FROM auction_jsonb_gin WHERE jsonb_exists(item, 'author');
-- has any of the fields
SELECT COUNT(*) FROM auction_jsonb_indexed WHERE item ?| array['author', 'foobar'];
SELECT COUNT(*) FROM auction_jsonb_indexed WHERE jsonb_exists_any(item, array['author', 'foobar']);
SELECT COUNT(*) FROM auction_jsonb_gin WHERE item ?| array['author', 'foobar'];
-- has all the specified fields
SELECT COUNT(*) FROM auction_jsonb_indexed WHERE item ?& array['author', 'title'];
SELECT COUNT(*) FROM auction_jsonb_indexed WHERE jsonb_exists_all(item, array['author', 'title']);
SELECT COUNT(*) FROM auction_jsonb_gin WHERE item ?& array['author', 'title'];

-- unwrap objects
SELECT item FROM auction_jsonb LIMIT 1;
SELECT json_serialize(item) FROM auction_json LIMIT 1;
SELECT jsonb_each_text(item) FROM auction_jsonb LIMIT 10;
SELECT jsonb_path_query(item, '$.genre') FROM auction_jsonb LIMIT 1;


-- updating
SELECT item->>'author' AS old FROM auction_jsonb WHERE id=41;
UPDATE auction_jsonb
SET item = jsonb_set(item, '{author}', '"New Author"', true)
WHERE id=41;
SELECT item->>'author' AS new FROM auction_jsonb WHERE id=41;

-- only update property if exists, but don't create
SELECT item->>'foo' AS old FROM auction_jsonb WHERE id=42;
UPDATE auction_jsonb
SET item = jsonb_set(item, '{foo}', '"Bar"', false)
WHERE id=42;
SELECT item->>'foo' AS new FROM auction_jsonb WHERE id=42;

-- create property if not exists
UPDATE auction_jsonb SET item = item - 'foo' WHERE id=43;
SELECT item->>'foo' AS old FROM auction_jsonb WHERE id=43;
UPDATE auction_jsonb
SET item = jsonb_set(item, '{foo}', '"Bar"', true)
WHERE id=43;
SELECT item->>'foo' AS new FROM auction_jsonb WHERE id=43;

UPDATE auction_jsonb SET item = jsonb_set(item, '{author}', '"Author 4"') WHERE id=44;
SELECT item->>'author' AS old FROM auction_jsonb WHERE id=44;
UPDATE auction_jsonb
SET item = jsonb_set(item, '{author}', 'null')
WHERE id=44;
SELECT item->'author' AS new FROM auction_jsonb WHERE id=44;


-- deleting

SELECT item->>'author' AS old FROM auction_jsonb WHERE id=50;
UPDATE auction_jsonb
SET item = item - 'author'
WHERE id=50;
SELECT item->>'author' AS new FROM auction_jsonb WHERE id=50;

SELECT item->>'author' AS old FROM auction_jsonb WHERE id=51;
UPDATE auction_jsonb
SET item = item - array['author', 'title']
WHERE id=51;
SELECT item->>'author' AS new FROM auction_jsonb WHERE id=51;

SELECT item->'author' AS old FROM auction_jsonb WHERE id=52;
UPDATE auction_jsonb
SET item = jsonb_set_lax(item, '{author}', null, true, 'delete_key')
WHERE id=52;
SELECT item->'author' AS new FROM auction_jsonb WHERE id=52;

-- fun with jsonb_set_lax
UPDATE auction_jsonb SET item = jsonb_set(item, '{author}', '"Author 3"', true) WHERE id=60;
SELECT item->'author' AS old FROM auction_jsonb WHERE id=60;
UPDATE auction_jsonb
SET item = jsonb_set_lax(item, '{author}', null)
WHERE id=60;
SELECT item->'author' AS new FROM auction_jsonb WHERE id=60;

SELECT item->'author' AS old FROM auction_jsonb WHERE id=61;
UPDATE auction_jsonb
SET item = jsonb_set_lax(item, '{author}', null, true, 'return_target')
WHERE id=61;
SELECT item->'author' AS new FROM auction_jsonb WHERE id=61;

SELECT jsonb('{"type":"book", "author": "Author 1"}') AS create_item;
SELECT '{"type":"book", "author": "Author 1"}'::jsonb AS cast_item;

SELECT * FROM jsonb_each((SELECT item FROM auction_jsonb WHERE id=1));

-- Some deep JSON, they only work when the fixtures have been loaded through Symfony with the --extra-data option
SELECT item #> '{key-0-1,key-1-1,key-2-2,key-3-2}' AS old FROM auction_jsonb WHERE id=60;
UPDATE auction_jsonb SET item = jsonb_set(item, '{key-0-1,key-1-1,key-2-2,key-3-2}', '"Deep"', true) WHERE id<1000;
SELECT item #> '{key-0-1,key-1-1,key-2-2,key-3-2}' AS new FROM auction_jsonb WHERE id=60;

SELECT item #> '{key-0-1,key-1-1,key-2-2,key-3-2}' AS old FROM auction_jsonb_gin LIMIT 1;
UPDATE auction_jsonb_gin SET item = jsonb_set(item, '{key-0-1,key-1-1,key-2-2,key-3-2}', '"Deop"', true) WHERE id>0;
SELECT item #> '{key-0-1,key-1-1,key-2-2,key-3-2}' AS new FROM auction_jsonb_gin WHERE id=60;


SELECT
    table_name,
    pg_size_pretty(table_size) AS table_size,
    pg_size_pretty(indexes_size) AS indexes_size,
    pg_size_pretty(total_size) AS total_size
FROM (
         SELECT
             table_name,
             pg_table_size(table_name) AS table_size,
             pg_indexes_size(table_name) AS indexes_size,
             pg_total_relation_size(table_name) AS total_size
         FROM (
                  SELECT ('"public"."' || table_name || '"') AS table_name
                  FROM information_schema.tables
                  WHERE table_schema = 'public'
              ) AS all_tables
         ORDER BY total_size DESC
     ) AS pretty_sizes;