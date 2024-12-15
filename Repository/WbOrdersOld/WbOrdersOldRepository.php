<?php
/*
 *  Copyright 2024.  Baks.dev <admin@baks.dev>
 *  
 *  Permission is hereby granted, free of charge, to any person obtaining a copy
 *  of this software and associated documentation files (the "Software"), to deal
 *  in the Software without restriction, including without limitation the rights
 *  to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 *  copies of the Software, and to permit persons to whom the Software is furnished
 *  to do so, subject to the following conditions:
 *  
 *  The above copyright notice and this permission notice shall be included in all
 *  copies or substantial portions of the Software.
 *  
 *  THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 *  IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 *  FITNESS FOR A PARTICULAR PURPOSE AND NON INFRINGEMENT. IN NO EVENT SHALL THE
 *  AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 *  LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 *  OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 *  THE SOFTWARE.
 */

declare(strict_types=1);

namespace BaksDev\Wildberries\Orders\Repository\WbOrdersOld;

use BaksDev\Core\Doctrine\DBALQueryBuilder;
use BaksDev\Orders\Order\Entity\Order;
use BaksDev\Orders\Order\Entity\Products\OrderProduct;
use BaksDev\Products\Product\Type\Event\ProductEventUid;
use BaksDev\Wildberries\Orders\Entity\Event\WbOrdersEvent;
use BaksDev\Wildberries\Orders\Entity\WbOrders;
use BaksDev\Wildberries\Orders\Type\OrderStatus\Status\WbOrderStatusNew;
use BaksDev\Wildberries\Orders\Type\OrderStatus\WbOrderStatus;
use DateTimeImmutable;

final readonly class WbOrdersOldRepository implements WbOrdersOldInterface
{
    public function __construct(private DBALQueryBuilder $DBALQueryBuilder) {}

    /**
     * Метод возвращает дату самого старого невыполненного заказа данного продукта (со статусом NEW)
     */
    public function getOldOrderDateByProduct(ProductEventUid $product): ?DateTimeImmutable
    {
        $qb = $this->DBALQueryBuilder->createQueryBuilder(self::class);

        $qb->select('MIN(wb_orders_event.created)');
        $qb->from(OrderProduct::class, 'orders_product');

        $qb->join('orders_product',
            Order::class,
            'orders',
            'orders.event = orders_product.event'
        );


        $qb->where('orders_product.product = :product');
        $qb->setParameter('product', $product, ProductEventUid::TYPE);


        $qb->join('orders',
            WbOrders::class,
            'wb_orders',
            'wb_orders.id = orders.id'
        );

        $qb->join('wb_orders',
            WbOrdersEvent::class,
            'wb_orders_event',
            'wb_orders_event.id = wb_orders.event AND wb_orders_event.status = :wb_orders_status'
        );

        $qb->setParameter('wb_orders_status', WbOrderStatusNew::STATUS, WbOrderStatus::TYPE);

        $oldDate = $qb->fetchOne();

        return !empty($oldDate) ? new DateTimeImmutable($oldDate) : null;
    }
}