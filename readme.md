# LOC - Lockio 

## Dependencies
You need:
- Docker
- IDE of your choice

## Installation

- Clone the repository
- Install Docker and Docker-Compose (see https://docs.docker.com/install/)

## Startup

- run `docker-compose -f docker-compose.local.yml up -d`
- run `docker exec lockio-core-dev php /usr/bin/composer install`
- run `docker exec lockio-core-dev php bin/console doctrine:migrations:migrate -n`

## Load fixtures
- run `docker exec lockio-core-dev php bin/console doctrine:fixtures:load -n`

## Synchronization countries ids with Bexio
- run `docker exec lockio-core-dev php bin/console bexio:sync-countries -n`

## Stop Docker
- run `docker-compose down`

## Running tests

- run `docker exec lockio-core-dev bin/phpunit`

## Use the application

The main application is accessible under `localhost:8080`. Postgres database is available under `localhost:5440`. Also there is a PhpPgAdmin under `localhost:5441`.
(user: `lockio-dev`, pw: `lockio-dev`)

## Development Server

Server can be found under https://dev-vault.lockio.ch.
