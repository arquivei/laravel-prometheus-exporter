PHP_VERSION ?= 7.3
PHP ?= bin/php -d "xdebug.mode=off"
COMPOSER ?= bin/composer

.PHONY: setup
setup:
	DOCKER_BUILDKIT=1 docker build -f .docker/Dockerfile -t laravel-prometheus-exporter:7.2 --build-arg PHP_VERSION=7.2 .
	DOCKER_BUILDKIT=1 docker build -f .docker/Dockerfile -t laravel-prometheus-exporter:7.3 --build-arg PHP_VERSION=7.3 .
	DOCKER_BUILDKIT=1 docker build -f .docker/Dockerfile -t laravel-prometheus-exporter:7.4 --build-arg PHP_VERSION=7.4 .
	DOCKER_BUILDKIT=1 docker build -f .docker/Dockerfile -t laravel-prometheus-exporter:8.0 --build-arg PHP_VERSION=8.0 .

.PHONY: publish
publish: publish-7.2 publish-7.3 publish-7.4 publish-8.0

.PHONY: publish-%
publish-%:
	docker tag laravel-prometheus-exporter:${*} arquivei/laravel-prometheus-exporter:${*}
	docker push arquivei/laravel-prometheus-exporter:${*}

.PHONY: vendor
vendor:
	PHP_VERSION=$(PHP_VERSION) $(COMPOSER) install

.PHONY: tests
tests:
	PHP_VERSION=$(PHP_VERSION) $(PHP) vendor/bin/phpunit

.PHONY: ci-local
ci-local: ci-local-7.2 ci-local-7.3 ci-local-7.4 ci-local-8.0

ci-local-%:
	rm -rf composer.lock vendor/

	PHP_VERSION=${*} $(COMPOSER) install
	PHP_VERSION=${*} $(PHP) vendor/bin/phpunit

	PHP_VERSION=${*} $(COMPOSER) update --prefer-lowest --prefer-dist --prefer-stable --no-interaction
	PHP_VERSION=${*} $(PHP) vendor/bin/phpunit
