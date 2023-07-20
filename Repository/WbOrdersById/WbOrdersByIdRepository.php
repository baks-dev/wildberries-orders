<?php
/*
 *  Copyright 2022.  Baks.dev <admin@baks.dev>
 *
 *  Licensed under the Apache License, Version 2.0 (the "License");
 *  you may not use this file except in compliance with the License.
 *  You may obtain a copy of the License at
 *
 *  http://www.apache.org/licenses/LICENSE-2.0
 *
 *  Unless required by applicable law or agreed to in writing, software
 *  distributed under the License is distributed on an "AS IS" BASIS,
 *  WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 *  See the License for the specific language governing permissions and
 *   limitations under the License.
 *
 */

namespace BaksDev\Wildberries\Orders\Repository\WbOrdersById;


use BaksDev\Orders\Order\Type\Id\OrderUid;
use BaksDev\Wildberries\Orders\Entity\Event\WbOrdersEvent;
use BaksDev\Wildberries\Orders\Entity\WbOrders;
use Doctrine\DBAL\ParameterType;
use Doctrine\ORM\EntityManagerInterface;

final class WbOrdersByIdRepository implements WbOrdersByIdInterface
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * Метод возвращает активное событие заказа по идентификатору заказа Wildberries
     */
    public function getWbOrderOrNullResult(int $order): ?WbOrdersEvent
    {
        $qb = $this->entityManager->createQueryBuilder();

        $qb->select('event');
        $qb->from(WbOrders::class, 'wb_orders');
        $qb->join(WbOrdersEvent::class, 'event', 'WITH', 'event.id = wb_orders.event');
        $qb->where('wb_orders.ord = :order');
        $qb->setParameter('order', $order, ParameterType::INTEGER);

        return $qb->getQuery()->getOneOrNullResult();

    }

    /**
     * Метод возвращает активное событие заказа по идентификатору системного заказа
     */
    public function getWbOrderByOrderUidOrNullResult(OrderUid $order): WbOrdersEvent
    {
        $qb = $this->entityManager->createQueryBuilder();

        $qb->select('event');
        $qb->from(WbOrders::class, 'ord');
        $qb->join(WbOrdersEvent::class, 'event', 'WITH', 'event.id = ord.event');
        $qb->where('ord.id = :ord');
        $qb->setParameter('ord', $order, OrderUid::TYPE);

        return $qb->getQuery()->getOneOrNullResult();

    }
}