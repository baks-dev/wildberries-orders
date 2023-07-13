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

namespace BaksDev\Wildberries\Orders\Entity;

use BaksDev\Orders\Order\Entity\Order;
use BaksDev\Orders\Order\Type\Id\OrderUid;
use BaksDev\Wildberries\Orders\Entity\Event\WbOrdersEvent;
use BaksDev\Wildberries\Orders\Type\Event\WbOrdersEventUid;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/* WbOrders */

#[ORM\Entity]
#[ORM\Table(name: 'wb_orders')]
class WbOrders
{
    public const TABLE = 'wb_orders';

    /** ID */
    #[Assert\NotBlank]
    #[Assert\Uuid]
    #[ORM\Id]
    #[ORM\Column(type: OrderUid::TYPE)]
    private OrderUid $id;

    /** ID заказа WB */
    #[Assert\NotBlank]
    #[ORM\Column(type: Types::INTEGER, unique: true)]
    private int $orders;

    /** ID События */
    #[Assert\NotBlank]
    #[Assert\Uuid]
    #[ORM\Column(type: WbOrdersEventUid::TYPE, unique: true)]
    private WbOrdersEventUid $event;


    public function __construct(OrderUid|Order $order, int $wbOrder)
    {
        $this->id = $order instanceof Order ? $order->getId() : $order;
        $this->orders = $wbOrder;
    }

    public function getId(): OrderUid
    {
        return $this->id;
    }

    public function getEvent(): WbOrdersEventUid
    {
        return $this->event;
    }

    public function setEvent(WbOrdersEventUid|WbOrdersEvent $event): void
    {
        $this->event = $event instanceof WbOrdersEvent ? $event->getId() : $event;
    }

}