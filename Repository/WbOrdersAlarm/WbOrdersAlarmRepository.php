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

namespace BaksDev\Wildberries\Orders\Repository\WbOrdersAlarm;

use BaksDev\Orders\Order\Entity\Order;
use BaksDev\Orders\Order\Entity\Products\OrderProduct;
use BaksDev\Products\Product\Type\Event\ProductEventUid;
use BaksDev\Wildberries\Orders\Entity\Event\WbOrdersEvent;
use BaksDev\Wildberries\Orders\Entity\WbOrders;
use BaksDev\Wildberries\Orders\Type\OrderStatus\Status\WbOrderStatusNew;
use BaksDev\Wildberries\Orders\Type\OrderStatus\WbOrderStatus;
use Doctrine\DBAL\Connection;

final class WbOrdersAlarmRepository implements WbOrdersAlarmInterface
{

    private Connection $connection;

    public function __construct(
        Connection $connection,
    )
    {
        $this->connection = $connection;
    }

    /**
     * Метод возвращает количеств срочных заказов продукта, требующих особое внимание
     * Заказы с интервалом 36 часов
     */
    public function countOrderAlarmByProduct(ProductEventUid $product): int
    {
        $qb = $this->connection->createQueryBuilder();

        $qb->select('COUNT(*)');
        $qb->from(OrderProduct::TABLE, 'orders_product');

        $qb->join('orders_product',
            Order::TABLE,
            'orders',
            'orders.event = orders_product.event'
        );


        $qb->where('orders_product.product = :product');
        $qb->setParameter('product', $product, ProductEventUid::TYPE);


        $qb->join('orders',
            WbOrders::TABLE,
            'alarm_wb_orders',
            'alarm_wb_orders.id = orders.id'
        );

        $qb->join('alarm_wb_orders',
            WbOrdersEvent::TABLE,
            'alarm_wb_orders_event',
            'alarm_wb_orders_event.id = alarm_wb_orders.event AND
				alarm_wb_orders_event.status = :wb_orders_status AND
				alarm_wb_orders_event.created < ( NOW() - interval \'36 HOUR\')
 			');


        //$status = new WbOrderStatus(new WbOrderStatusNew::STATUS);
        $qb->setParameter('wb_orders_status', WbOrderStatusNew::STATUS, WbOrderStatus::TYPE);

        return $qb->fetchOne();
    }
}