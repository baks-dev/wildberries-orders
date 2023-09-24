
#### Если вы измените файл конфигурации службы, вам необходимо перезагрузить демон:

``` bash

systemctl daemon-reload

```


####  Название файла в директории /etc/systemd/system

``` text

baks-scheduler@.service

```


#### Содержимое файла

``` text
[Unit]
Description=Baks Scheduler %i

[Service]
ExecStart=php /.......PATH_TO_PROJECT......../bin/console messenger:consume scheduler_default --memory-limit=128m --time-limit=3600
Restart=always
RestartSec=3

[Install]
WantedBy=default.target

```


#### Команды для выполнения


``` bash

systemctl daemon-reload

systemctl enable baks-scheduler@1.service
systemctl start baks-scheduler@1.service

systemctl disable baks-scheduler@1.service
systemctl stop baks-scheduler@1.service

```

#### Запуск из консоли на 1 минуту

``` bash

php bin/console messenger:consume scheduler_default --time-limit=60 -vv

```
