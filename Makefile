PHP_VERSION ?= 8.1.5
PHP ?= bin/php -d "xdebug.mode=off"
COMPOSER ?= bin/composer

.PHONY: setup
setup:
	DOCKER_BUILDKIT=1 docker build -f .docker/Dockerfile -t laravel-prometheus-exporter:$(PHP_VERSION) --build-arg PHP_VERSION=$(PHP_VERSION) .

.PHONY: vendor
vendor:
	PHP_VERSION=$(PHP_VERSION) $(COMPOSER) install

.PHONY: tests
tests:
	PHP_VERSION=$(PHP_VERSION) $(PHP) vendor/bin/phpunit

.PHONY: ci-local
ci-local: ci-local-8.0 ci-local-$(PHP_VERSION)

.PHONY: ci-local-%
ci-local-%:
	rm -rf composer.lock vendor/ .phpunit.cache/

	PHP_VERSION=${*} $(COMPOSER) install
	PHP_VERSION=${*} $(PHP) vendor/bin/phpunit

	PHP_VERSION=${*} $(COMPOSER) update --prefer-lowest --prefer-dist --prefer-stable --no-interaction
	PHP_VERSION=${*} $(PHP) vendor/bin/phpunit
