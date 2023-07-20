<?php
/*
 *  Copyright 2023.  Baks.dev <admin@baks.dev>
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
    private int $ord;

    /** ID События */
    #[Assert\NotBlank]
    #[Assert\Uuid]
    #[ORM\Column(type: WbOrdersEventUid::TYPE, unique: true)]
    private WbOrdersEventUid $event;


    public function __construct(OrderUid|Order $order, int $wbOrder)
    {
        $this->id = $order instanceof Order ? $order->getId() : $order;
        $this->ord = $wbOrder;
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