#!/usr/bin/env sh
# Downloads PHPUnit 11 (needs PHP 8.2+) into tools/phpunit.phar. The phar is git-ignored: it is
# a development tool and never ships to the server. Run the tests with
#   C:/xampp/php/php.exe tools/phpunit.phar
set -e
cd "$(dirname "$0")"
curl -fsSL -o phpunit.phar https://phar.phpunit.de/phpunit-11.phar
echo "downloaded tools/phpunit.phar"
