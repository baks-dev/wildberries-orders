<?php
/*
 *  Copyright 2025.  Baks.dev <admin@baks.dev>
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

namespace BaksDev\Wildberries\Orders\Repository\WbOrdersAlarm;

use BaksDev\Core\Doctrine\DBALQueryBuilder;
use BaksDev\Orders\Order\Entity\Event\OrderEvent;
use BaksDev\Orders\Order\Entity\Order;
use BaksDev\Orders\Order\Entity\Products\OrderProduct;
use BaksDev\Orders\Order\Entity\User\Delivery\OrderDelivery;
use BaksDev\Orders\Order\Entity\User\OrderUser;
use BaksDev\Orders\Order\Type\Status\OrderStatus;
use BaksDev\Orders\Order\Type\Status\OrderStatus\OrderStatusNew;
use BaksDev\Products\Product\Type\Event\ProductEventUid;

final readonly class WbOrdersAlarmRepository implements WbOrdersAlarmInterface
{
    public function __construct(private DBALQueryBuilder $DBALQueryBuilder) {}

    /**
     * Метод возвращает количеств срочных заказов продукта, требующих особое внимание
     * Заказы с интервалом 24 часов
     */
    public function countOrderAlarmByProduct(ProductEventUid $product): int
    {
        $dbal = $this->DBALQueryBuilder->createQueryBuilder(self::class);

        $dbal
            ->addSelect('COUNT(*)')
            ->from(OrderProduct::class, 'orders_product')
            ->where('orders_product.product = :product')
            ->setParameter('product', $product, ProductEventUid::TYPE);

        $dbal->join('orders_product',
            Order::class,
            'orders',
            'orders.event = orders_product.event'
        );

        $dbal->join('orders',
            OrderEvent::class,
            'orders_event',
            '
                orders_event.id = orders.event AND
                orders_event.status = :status
            '
        )
            ->setParameter(
                key: 'status',
                value: OrderStatusNew::class,
                type: OrderStatus::TYPE
            );

        $dbal->leftJoin('orders',
            OrderUser::class,
            'orders_user',
            'orders_user.event = orders.event'
        );

        $dbal->join('orders_user',
            OrderDelivery::class,
            'orders_delivery',
            '
                orders_delivery.usr = orders_user.id AND
                orders_delivery.delivery_date < ( NOW() - interval \'24 HOUR\')
            '
        );

        return $dbal->fetchOne();
    }
}