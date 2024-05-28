<?php

namespace BaksDev\Wildberries\Orders\Repository\WbOrdersAlarm;


use BaksDev\Products\Product\Type\Event\ProductEventUid;

interface WbOrdersAlarmInterface
{
    /**
     * Метод возвращает количеств срочных заказов продукта, требующих особое внимание
     * Заказы с интервалом 36 часов
     */
    public function countOrderAlarmByProduct(ProductEventUid $product): int;
}