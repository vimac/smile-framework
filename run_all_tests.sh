#!/bin/bash

cd $(dirname $0)
if [ -f ./vendor/phpunit/phpunit/phpunit ]; then
    ./vendor/phpunit/phpunit/phpunit --bootstrap ./tests/bootstrap.php ./tests 
else
    echo "phpunit not installed by composer, check composer.json"
fi
