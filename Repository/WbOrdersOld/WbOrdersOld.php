<?php

declare(strict_types=1);

namespace BaksDev\Wildberries\Orders\Repository\WbOrdersOld;

use BaksDev\Orders\Order\Entity\Order;
use BaksDev\Orders\Order\Entity\Products\OrderProduct;
use BaksDev\Products\Product\Type\Event\ProductEventUid;
use BaksDev\Wildberries\Orders\Entity\Event\WbOrdersEvent;
use BaksDev\Wildberries\Orders\Entity\WbOrders;
use BaksDev\Wildberries\Orders\Type\OrderStatus\Status\WbOrderStatusNew;
use BaksDev\Wildberries\Orders\Type\OrderStatus\WbOrderStatus;
use DateTimeImmutable;
use Doctrine\DBAL\Connection;

final class WbOrdersOld implements WbOrdersOldInterface
{
    private Connection $connection;

    public function __construct(
        Connection $connection,
    )
    {
        $this->connection = $connection;
    }

    /**
     * Метод возвращает дату самого старого невыполненного заказа данного продукта (со статусом NEW)
     */
    public function getOldOrderDateByProduct(ProductEventUid $product): ?DateTimeImmutable
    {
        $qb = $this->connection->createQueryBuilder();

        $qb->select('MIN(wb_orders_event.created)');
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
            'wb_orders',
            'wb_orders.id = orders.id'
        );

        $qb->join('wb_orders',
            WbOrdersEvent::TABLE,
            'wb_orders_event',
            'wb_orders_event.id = wb_orders.event AND wb_orders_event.status = :wb_orders_status'
        );

        $qb->setParameter('wb_orders_status', WbOrderStatusNew::STATUS, WbOrderStatus::TYPE);

        $oldDate = $qb->fetchOne();

        return !empty($oldDate) ? new DateTimeImmutable($oldDate) : null;
    }
}