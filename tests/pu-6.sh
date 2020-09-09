#!/bin/sh

cp composer.json original-composer.json
sed '/        <log type="coverage-html" target="coverage" showUncoveredFiles="true"\/>/d' phpunit.xml > phpunit-6.xml

composer require "illuminate/routing:6.*" --no-update
composer require "illuminate/support:6.*" --no-update
composer require "orchestra/testbench:4.*" --no-update --dev
composer require "phpunit/phpunit:8.*" --no-update --dev
composer update --no-interaction --prefer-dist

rm composer.json
mv original-composer.json composer.json

vendor/bin/phpunit --configuration phpunit-6.xml
RETVAL=$?
rm phpunit-6.xml

exit ${RETVAL}
