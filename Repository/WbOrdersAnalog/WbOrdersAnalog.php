<?php

declare(strict_types=1);

namespace BaksDev\Wildberries\Orders\Repository\WbOrdersAnalog;

use BaksDev\Orders\Order\Entity\Order;
use BaksDev\Orders\Order\Entity\Products\OrderProduct;
use BaksDev\Products\Product\Type\Event\ProductEventUid;
use BaksDev\Wildberries\Orders\Entity\Event\WbOrdersEvent;
use BaksDev\Wildberries\Orders\Entity\WbOrders;
use BaksDev\Wildberries\Orders\Type\OrderStatus\Status\WbOrderStatusNew;
use BaksDev\Wildberries\Orders\Type\OrderStatus\WbOrderStatus;
use Doctrine\DBAL\Connection;

final class WbOrdersAnalog implements WbOrdersAnalogInterface
{
    private Connection $connection;

    public function __construct(
        Connection $connection,
    )
    {
        $this->connection = $connection;
    }

    /**
     * Метод возвращает количество аналогичных НОВЫХ заказов продукта (все ТП и множественные варианты)
     */
    public function countOrderAnalogByProduct(ProductEventUid $product): int
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


        $qb->join(
            'orders',
            WbOrders::TABLE,
            'count_wb_orders',
            'count_wb_orders.id = orders.id'
        );

        $qb->join(
            'count_wb_orders',
            WbOrdersEvent::TABLE,
            'count_wb_orders_event',
            'count_wb_orders_event.id = count_wb_orders.event AND count_wb_orders_event.status = :wb_orders_status'
        );

        //$status = new WbOrderStatus(WbOrderStatusEnum::NEW);
        $qb->setParameter('wb_orders_status', WbOrderStatusNew::STATUS, WbOrderStatus::TYPE);

        return $qb->fetchOne();
    }
}