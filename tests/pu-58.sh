#!/bin/sh

cp composer.json original-composer.json
sed '/        <log type="coverage-html" target="coverage" showUncoveredFiles="true"\/>/d' phpunit.xml > phpunit-58.xml

composer require "illuminate/routing:5.8.*" --no-update
composer require "illuminate/support:5.8.*" --no-update
composer require "orchestra/testbench:3.8.*" --no-update --dev
composer require "phpunit/phpunit:8.*" --no-update --dev
composer update --no-interaction --prefer-dist

rm composer.json
mv original-composer.json composer.json

vendor/bin/phpunit --configuration phpunit-58.xml
RETVAL=$?
rm phpunit-58.xml

exit ${RETVAL}
