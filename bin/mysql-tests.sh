#!/usr/bin/env sh

echo "Runnning MySQL instance..."

docker run --rm --name mysql-container-for-tests -e MYSQL_ROOT_PASSWORD=root -e MYSQL_DATABASE=test -p 3306:3306 -d mysql:8.0

CURRENT_DIR=$(dirname $0)

echo "Waiting for MySQL to be ready..."
until docker exec mysql-container-for-tests mysqladmin ping -h localhost --silent; do
    sleep 2
done
echo "MySQL is up and running!"

DB_DSN=pdo-mysql://root:root@127.0.0.1:3306/test vendor/bin/phpunit tests

echo "Stopping MySQL instance..."

docker stop mysql-container-for-tests
