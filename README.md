# BaksDev Wildberries Orders

![Version](https://img.shields.io/badge/version-6.3.3-blue) ![php 8.1+](https://img.shields.io/badge/php-min%208.1-red.svg)

Модуль заказов Wildberries

## Установка

``` bash
$ composer require baks-dev/wildberries-orders
```

## Дополнительно

Изменения в схеме базы данных с помощью миграции

``` bash
$ php bin/console doctrine:migrations:diff

$ php bin/console doctrine:migrations:migrate
```

Установка файловых ресурсов в публичную директорию (javascript, css, image ...):

``` bash
$ php bin/console baks:assets:install
```

Тесты

``` bash
$ php bin/phpunit --group=wildberries-orders
```

## Лицензия ![License](https://img.shields.io/badge/MIT-green)

The MIT License (MIT). Обратитесь к [Файлу лицензии](LICENSE.md) за дополнительной информацией.


