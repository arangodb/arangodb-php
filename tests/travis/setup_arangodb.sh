#!/bin/bash

echo "PHP version: $TRAVIS_PHP_VERSION"

if [[ "$TRAVIS_PHP_VERSION" == "7.4" ]] ; then 
wget --no-check-certificate "https://phar.phpunit.de/phpunit-9.5.phar"
mv phpunit-9.5.phar ./phpunit
fi

if [[ "$TRAVIS_PHP_VERSION" == "8.0" ]] ; then 
wget --no-check-certificate "https://phar.phpunit.de/phpunit-9.5.phar"
mv phpunit-9.5.phar ./phpunit
fi

chmod +x ./phpunit

echo "./phpunit --version"
./phpunit --version

DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
cd $DIR

docker pull arangodb/arangodb-preview:3.10-nightly
docker run -d -e ARANGO_ROOT_PASSWORD="test" -p 8529:8529 arangodb/arangodb-preview:3.10-nightly arangod --database.extended-names-databases true

sleep 2

n=0
# timeout value for startup
timeout=60 
while [[ (-z `curl -H 'Authorization: Basic cm9vdDp0ZXN0' -s 'http://127.0.0.1:8529/_api/version' `) && (n -lt timeout) ]] ; do
  echo -n "."
  sleep 1s
  n=$[$n+1]
done

if [[ n -eq timeout ]];
then
    echo "Could not start ArangoDB. Timeout reached."
    exit 1
fi

echo "ArangoDB is up"
