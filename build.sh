#!/bin/bash
set -e

export COMPOSER_ALLOW_SUPERUSER=1

cd core
composer install --no-dev --optimize-autoloader --no-scripts
cd ..
