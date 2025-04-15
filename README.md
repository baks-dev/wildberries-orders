# BaksDev Wildberries Orders

[![Version](https://img.shields.io/badge/version-7.2.47-blue)](https://github.com/baks-dev/wildberries-orders/releases)
![php 8.4+](https://img.shields.io/badge/php-min%208.4-red.svg)
[![packagist](https://img.shields.io/badge/packagist-green)](https://packagist.org/packages/baks-dev/wildberries-orders)

Модуль заказов Wildberries

## Установка

``` bash
$ composer require baks-dev/wildberries-orders
```

## Дополнительно

Для работы с заказами выполнить комманду для добавления типа профиля и доставку:

* #### FBS

``` bash
php bin/console baks:users-profile-type:wildberries-fbs
php bin/console baks:payment:wildberries-fbs
php bin/console baks:delivery:wildberries-fbs
```

* #### DBS

``` bash
php bin/console baks:users-profile-type:wildberries-dbs
php bin/console baks:payment:wildberries-dbs
php bin/console baks:delivery:wildberries-dbs
```

Установка конфигурации и файловых ресурсов:

``` bash
$ php bin/console baks:assets:install
```

Изменения в схеме базы данных с помощью миграции

``` bash
$ php bin/console doctrine:migrations:diff

$ php bin/console doctrine:migrations:migrate
```

## Тестирование

``` bash
$ php bin/phpunit --group=wildberries-orders
```

## Лицензия ![License](https://img.shields.io/badge/MIT-green)

The MIT License (MIT). Обратитесь к [Файлу лицензии](LICENSE.md) за дополнительной информацией.
