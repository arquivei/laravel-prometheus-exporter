#!/bin/sh

cp composer.json original-composer.json
sed '/        <log type="coverage-html" target="coverage" showUncoveredFiles="true"\/>/d' phpunit.xml > phpunit-7.xml

composer require "illuminate/routing:7.*" --no-update
composer require "illuminate/support:7.*" --no-update
composer require "orchestra/testbench:5.*" --no-update --dev
composer require "phpunit/phpunit:8.*" --no-update --dev
composer update --no-interaction --prefer-dist

rm composer.json
mv original-composer.json composer.json

vendor/bin/phpunit --configuration phpunit-7.xml
RETVAL=$?
rm phpunit-7.xml

exit ${RETVAL}
