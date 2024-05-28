<?php

namespace BaksDev\Wildberries\Orders\Repository\WbOrdersAnalog;

use BaksDev\Products\Product\Type\Event\ProductEventUid;

interface WbOrdersAnalogInterface
{
    /**
     * Метод возвращает количество аналогичных НОВЫХ заказов продукта (все ТП и множественные варианты)
     */
    public function countOrderAnalogByProduct(ProductEventUid $product): int;
}