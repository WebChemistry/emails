#!/usr/bin/env sh

if [ "$1" = "stop" ]; then
  docker stop mysql-container-for-tests
elif [ "$1" = "tests" ]; then
  DB_DSN='pdo-mysql://root:root@127.0.0.1:3306/test' vendor/bin/phpunit tests
else
  docker run --rm --name mysql-container-for-tests -e MYSQL_ROOT_PASSWORD=root -e MYSQL_DATABASE=test -p 3306:3306 -d mysql:8.0
fi
