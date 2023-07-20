<?php

namespace BaksDev\Wildberries\Orders\Repository\WbOrdersOld;


use BaksDev\Products\Product\Type\Event\ProductEventUid;
use DateTimeImmutable;

interface WbOrdersOldInterface
{
    /**
     * Метод возвращает дату самого старого невыполненного заказа данного продукта (со статусом NEW)
     */
    public function getOldOrderDateByProduct(ProductEventUid $product): ?DateTimeImmutable;
}