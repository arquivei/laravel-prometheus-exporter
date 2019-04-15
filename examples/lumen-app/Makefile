help:
	@echo "Please use 'make <target>' where <target> is one of the following:"
	@echo "  up              to create and start project services."
	@echo "  start           to start project services."
	@echo "  down            to stop and remove all project services."
	@echo "  stop            to stop project services."
	@echo "  rebuild         to destroy the example app container and rebuild from Dockerfile."
	@echo "  provision       to provision Grafana datasources and dashboards"
	@echo "  generate-links  to generate the possible services links"
	@echo "  create-db       to create the database."
	@echo "  create-db       to create the database."
	@echo "  migrate         to run db migrations."
	@echo "  update          to perform Composer update."
	@echo "  call            to perform a number of requests to a default test route."

SHELL := /bin/bash
up:
	set -a ;\
    source .env.docker ;\
	docker-compose up -d ;\
	make setup-env ;\
	make install ;\
	make create-db ;\
	make migrate ;\
	make provision ;\
	make generate-links ;

SHELL := /bin/bash
start:
	set -a ;\
    source .env.docker ;\
	docker-compose start
	make setup-env
	make create-db
	make migrate
	make generate-links

SHELL := /bin/bash
stop:
	set -a ;\
    source .env.docker ;\
	docker-compose stop

SHELL := /bin/bash
down:
	set -a ;\
    source .env.docker ;\
	docker-compose down

SHELL := /bin/bash
rebuild:
	make down
	docker rmi -f lumen-example-app_app
	set -a ;\
    source .env.docker ;\
	docker-compose up -d ;\

provision:
	docker exec -it lumen-example-app scripts/grafana_provision.sh

generate-links:
	docker exec -it lumen-example-app scripts/url_print.sh

create-db:
	docker exec lumen-example-app php artisan db:create

setup-env:
	docker exec lumen-example-app cp .env.example .env

migrate:
	docker exec -it lumen-example-app php artisan migrate

update:
	docker exec -it lumen-example-app composer update

install:
	docker exec -it lumen-example-app composer install

SHELL := /bin/bash
call:
	set -a ;\
    source .env.docker ;\
	sh scripts/call_test_route.sh ;\
