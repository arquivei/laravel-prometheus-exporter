#!/bin/sh

cp composer.json original-composer.json
sed '/        <log type="coverage-html" target="coverage" showUncoveredFiles="true"\/>/d' phpunit.xml > phpunit-55.xml

composer require "illuminate/routing:5.5.*" --no-update
composer require "illuminate/support:5.5.*" --no-update
composer require "orchestra/testbench:3.5.*" --no-update --dev
composer require "phpunit/phpunit:6.*" --no-update --dev
composer update --no-interaction --prefer-dist

rm composer.json
mv original-composer.json composer.json

vendor/bin/phpunit --configuration phpunit-55.xml
RETVAL=$?
rm phpunit-55.xml

exit ${RETVAL}
