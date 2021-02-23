# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [2.0.0] - 2020-02-12
### Added
 - Add PHP 7.2 support.
 - Add PHP 8 support.
 - Add pipeline for running automated tests.
 - Add makefile and docker images for development purposes.
 - Add support for Guzzle 7.
 - Add default metric `php_version`.

### Changed
 - Replace `endclothing/prometheus_client_php` dependency with `promphp/prometheus_client_php` .
 - Move collectors instantiation from the boot method to the callable that creates
the prometheus exporter. This means that the collectors are instantiated only when the
exporter is required, preventing the need of a redis connection when just executing
`php artisan`, for example.

### Removed
 - Drop Laravel 5.x support.

## [1.0.1] - 2017-08-30
### Changed
 - Fix config retrieval of `prometheus.storage_adapters`

## [1.0.0] - 2017-07-27
### Added
 - Initial release
