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

namespace BaksDev\Wildberries\Orders\Repository\WbOrdersById;


use BaksDev\Core\Doctrine\DBALQueryBuilder;
use BaksDev\Core\Doctrine\ORMQueryBuilder;
use BaksDev\Orders\Order\Type\Id\OrderUid;
use BaksDev\Wildberries\Orders\Entity\Event\WbOrdersEvent;
use BaksDev\Wildberries\Orders\Entity\WbOrders;
use Doctrine\DBAL\ParameterType;
use Doctrine\ORM\EntityManagerInterface;

final class WbOrdersByIdRepository implements WbOrdersByIdInterface
{

    private DBALQueryBuilder $DBALQueryBuilder;
    private ORMQueryBuilder $ORMQueryBuilder;

    public function __construct(
        DBALQueryBuilder $DBALQueryBuilder,
        ORMQueryBuilder $ORMQueryBuilder,

    )
    {
        $this->DBALQueryBuilder = $DBALQueryBuilder;
        $this->ORMQueryBuilder = $ORMQueryBuilder;
    }

    /**
     * Метод возвращает активное событие заказа по идентификатору заказа Wildberries
     */
    public function getWbOrderOrNullResult(int $order): ?WbOrdersEvent
    {
        $qb = $this->ORMQueryBuilder->createQueryBuilder(self::class);

        $qb->select('event');
        $qb->from(WbOrders::class, 'wb_orders');
        $qb->join(WbOrdersEvent::class, 'event', 'WITH', 'event.id = wb_orders.event');
        $qb->where('wb_orders.ord = :order');
        $qb->setParameter('order', $order, ParameterType::INTEGER);

        return $qb->getOneOrNullResult();

    }


    /**
     * Метод проверяет, имеется ли заказ по идентификатору заказа Wildberries
     */
    public function isExistWbOrder(int $order): bool
    {
        $qb = $this->DBALQueryBuilder->createQueryBuilder(self::class);

        $qb->from(WbOrders::class, 'wb_orders');
        $qb->where('wb_orders.ord = :order');
        $qb->setParameter('order', $order, ParameterType::INTEGER);

        return $qb->fetchExist();

    }

    /**
     * Метод возвращает активное событие заказа по идентификатору системного заказа
     */
    public function getWbOrderByOrderUidOrNullResult(OrderUid $order): ?WbOrdersEvent
    {
        $qb = $this->ORMQueryBuilder->createQueryBuilder(self::class);

        $qb->select('event');
        $qb->from(WbOrders::class, 'ord');
        $qb->join(WbOrdersEvent::class, 'event', 'WITH', 'event.id = ord.event');
        $qb->where('ord.id = :order');
        $qb->setParameter('order', $order, OrderUid::TYPE);

        return $qb->getOneOrNullResult();

    }
}