#!/bin/bash
composer install --no-dev --optimize-autoloader
php -S 0.0.0.0:$PORT -t /opt/render/project/src
