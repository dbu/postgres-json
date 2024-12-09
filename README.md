# Postgres JSON Experiments

I built this repository to play around with JSON and postgres, as well as trying out Doctrine support for JSON.

The slides of my talk are [in the doc folder](doc/).

The repository comes with Docker, setting it up should be as simple as running `make up`.

The database port is exposed to the host machine so you can run queries directly from your IDE / postgres client.

`make shell` gives you a bash inside the application docker container.

## Performance

A couple of SQL scripts are provided in the `doc/` folder.

[schema.sql](doc/schema.sql) contains the database schema used for the tests.

The 10k, 100k, 1M and 10M scripts insert a number of rows into each of the tables.
This is generated dummy data and therefor completely realistic. However, this allows to measure and compare raw database
costs of the different variants and indexing or not.  

[queries.sql](doc/queries.sql) defines some queries and contains some notes.

To measure raw PostgreSQL performance, I ran these scripts in a PostgreSQL client.
The numbers for my machine are collected in [results.ods](doc/results.ods).
The interesting part is the relative values. Absolute values depend on the hardware.

## Doctrine Integration

You find PHP code in `src/` with some documentation and examples on how to integrate Doctrine and Postgres JSON.

There are Doctrine migration classes in `migrations/`, execute them with `bin/console doctrine:migrations:migrate`.

You can generate fixture data through PHP as well. Use the dbal command to generate any number of rows with an efficient batch-size of 1000 (small row size increases the overhead significantly):

    bin/console fixtures:import:dbal 10000 --batch-size=1000

You can also generate fixtures by creating and storing entities:

    bin/console fixtures:import:em 10000

This is very slow, because of all the overhead of serializing objects and because of the entity manager.

Example query (the interesting part is in the AuctionJsonbRepository and AuctionJsonbGinRepository):

    bin/console app:query

### Working with the models

This sample application uses the doctrine repository classes as kludge to use Symfony serializer to translate between the model class and the array data that Doctrine requires.
A more elegant solution would be to use doctrine listeners, as proposed e.g. in [this blog post by Dave Gebler](https://davegebler.com/post/php/hybrid-databases-with-symfony-and-doctrine).
                
