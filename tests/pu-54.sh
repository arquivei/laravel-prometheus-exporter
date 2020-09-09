#!/bin/sh

cp composer.json original-composer.json
sed '/        <log type="coverage-html" target="coverage" showUncoveredFiles="true"\/>/d' phpunit.xml > phpunit-54.xml

composer require "illuminate/routing:5.4.*" --no-update
composer require "illuminate/support:5.4.*" --no-update
composer require "orchestra/testbench:3.4.*" --no-update --dev
composer require "phpunit/phpunit:5.*" --no-update --dev
composer update --no-interaction --prefer-dist

rm composer.json
mv original-composer.json composer.json

vendor/bin/phpunit --configuration phpunit-54.xml
RETVAL=$?
rm phpunit-54.xml

exit ${RETVAL}
