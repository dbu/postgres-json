SELECT COUNT(*) FROM auction_json WHERE item->>'$.author' = 'Author 1';
SELECT COUNT(*) FROM auction_json_indexed WHERE CAST(item->>'$.author' AS CHAR(255)) = 'Author 1';
SELECT COUNT(*) FROM auction_generated_fields WHERE author = 'Author 1';
SELECT COUNT(*) FROM auction_generated_fields_indexed WHERE author = 'Author 1';
-- Supposedly this should be using the index on the author field, but i did not see that work
EXPLAIN SELECT COUNT(*) FROM auction_generated_fields_indexed WHERE CAST(item->>'$.author' AS CHAR(255)) = 'Author 1';

-- extract the value from json, rather than compare with a json string
SELECT COUNT(*) FROM auction_json WHERE item->>'$.author' = 'Author 1';
# to use index, we need to cast the same way as for the index
SELECT COUNT(*) FROM auction_json_indexed WHERE CAST(item->>'$.author' AS CHAR(255)) = 'Author 1';
SELECT COUNT(*) FROM auction_json WHERE item->'$.author' = CAST('"Author 1"' AS JSON);

-- alternatively, use json path functions
-- json path is very powerful, similar to xpath or css selectors
-- CREATE INDEX json_author_path ON auction_json_indexed ((json_value(item, '$.author')));
SELECT COUNT(*) FROM auction_json_indexed WHERE json_value(item, '$.author') = 'Author 1';

SELECT COUNT(*) FROM auction_json_indexed WHERE json_contains_path(item, '$.author');


-- The "#>" notation is not available in mysql

-- The array notation item['author'] is not available in mysql

-- querying for containing value (only available with jsonb)
-- this does not use the index, but can use the GIN
SELECT COUNT(*) FROM auction_json WHERE JSON_CONTAINS(item, '{"author": "Author 1"}');
SELECT COUNT(*) FROM auction_json_indexed WHERE JSON_CONTAINS(item, '{"author": "Author 1"}');

-- has field
SELECT COUNT(*) FROM auction_json WHERE json_contains_path(item, 'one', '$.author');
-- has any of the fields
SELECT COUNT(*) FROM auction_json_indexed WHERE json_contains_path(item, 'any', '$.author', '$.foobar');
-- has all the specified fields
SELECT COUNT(*) FROM auction_json_indexed WHERE json_contains_path(item, 'all', '$.author', '$.title');

-- unwrap objects
SELECT item FROM auction_json LIMIT 1;
-- jsonb_each_text is not available in mysql
-- jsonb_path_query is not available in mysql


-- updating
SELECT item->>'$.author' AS old FROM auction_json WHERE id=41;
UPDATE auction_json
SET item = JSON_SET(item, '$.author', '"New Author"')
WHERE id=41;
SELECT item->>'$.author' AS new FROM auction_json WHERE id=41;

-- only update property if exists, but don't create
SELECT item->>'$.foo' AS old FROM auction_json WHERE id=42;
UPDATE auction_json
SET item = JSON_REPLACE(item, '$.foo', '"Bar"')
WHERE id=42;
SELECT item->>'$.foo' AS new FROM auction_json WHERE id=42;

-- create property if not exists
UPDATE auction_json SET item = JSON_REMOVE(item, '$.foo') WHERE id=43;
SELECT item->>'$.foo' AS old FROM auction_json WHERE id=43;
UPDATE auction_json
SET item = JSON_INSERT(item, '$.foo', '"Bar"')
WHERE id=43;
SELECT item->>'foo' AS new FROM auction_json WHERE id=43;

UPDATE auction_json SET item = JSON_SET(item, '$.author', '"Author 4"') WHERE id=44;
SELECT item->>'$.author' AS old FROM auction_json WHERE id=44;
UPDATE auction_json
SET item = JSON_SET(item, '$.author', null)
WHERE id=44;
SELECT item->>'$.author' AS new FROM auction_json WHERE id=44;


-- deleting

SELECT item->>'$.author' AS old FROM auction_json WHERE id=50;
UPDATE auction_json
SET item = JSON_REMOVE(item, '$.author')
WHERE id=50;
SELECT item->>'$.author' AS new FROM auction_json WHERE id=50;

-- no implicit removal like json_set_lax of postgres

SELECT CAST('{"type":"book", "author": "Author 1"}' AS JSON) AS cast_item;

-- no equivalent to JSON_EACH

-- Some deep JSON, they only work when the fixtures have been loaded through Symfony with the --extra-data option
-- MySQL needs each path segment in quotes to not stumble over the "-" in the name.
SELECT JSON_EXTRACT(item, '$."key-0-1"."key-1-1"."key-2-2"."key-3-2"') AS old FROM auction_json WHERE id=60;
UPDATE auction_json SET item = JSON_SET(item, '$."key-0-1"."key-1-1"."key-2-2"."key-3-2"', '"Deep"') WHERE id<1000;
SELECT JSON_EXTRACT(item, '$."key-0-1"."key-1-1"."key-2-2"."key-3-2"') AS new FROM auction_json WHERE id=60;

UPDATE auction_json SET item = JSON_SET(item, '$."key-0-1"."key-1-1"."key-2-2"."key-3-2"', '"Dee"') WHERE id<1000;


-- mysql does not update its information, we need to call analyze to trigger an update
-- table_rows seems to be an estimate
analyze table auction_json;
analyze table auction_json_indexed;
analyze table auction_generated_fields;
analyze table auction_generated_fields_indexed;

SELECT
    table_name AS `Table`,
    table_rows,
    round((data_length / 1024 / 1024), 2) `table_size in MB`,
    round((index_length / 1024 / 1024), 2) `index_size in MB` ,
    round(((data_length + index_length) / 1024 / 1024), 2) `total_size in MB`
FROM information_schema.TABLES
WHERE TABLE_SCHEMA = 'app'
ORDER BY (data_length + index_length) DESC;
