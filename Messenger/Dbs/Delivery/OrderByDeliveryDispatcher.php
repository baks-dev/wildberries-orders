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

namespace BaksDev\Wildberries\Orders\Messenger\Dbs\Delivery;


use BaksDev\Core\Messenger\MessageDispatchInterface;
use BaksDev\Orders\Order\Entity\Event\OrderEvent;
use BaksDev\Orders\Order\Messenger\OrderMessage;
use BaksDev\Orders\Order\Repository\CurrentOrderEvent\CurrentOrderEventInterface;
use BaksDev\Orders\Order\Type\Status\OrderStatus\Collection\OrderStatusExtradition;
use BaksDev\Wildberries\Orders\Type\DeliveryType\TypeDeliveryDbsWildberries;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/** Метод создает сообщение на обновление статуса заказа в маркетплейсе нa Delivery «Доставляется»  */
#[AsMessageHandler(priority: 0)]
final readonly class OrderByDeliveryDispatcher
{
    public function __construct(
        private CurrentOrderEventInterface $CurrentOrderEventRepository,
        private MessageDispatchInterface $messageDispatch,
    ) {}

    public function __invoke(OrderMessage $message): void
    {
        $OrderEvent = $this->CurrentOrderEventRepository
            ->forOrder($message->getId())
            ->find();

        if(false === ($OrderEvent instanceof OrderEvent))
        {
            return;
        }

        if(false === $OrderEvent->isDeliveryTypeEquals(TypeDeliveryDbsWildberries::TYPE))
        {
            return;
        }

        if($OrderEvent->isStatusEquals(OrderStatusExtradition::class))
        {
            return;
        }

        if(is_null($OrderEvent->getOrderTokenIdentifier()))
        {
            return;
        }

        /** Отправляем сообщение на изменение статуса */

        $WildberriesOrdersDeliveryMessage = new WildberriesOrdersDeliveryMessage($message->getId());

        $this->messageDispatch->dispatch(
            $WildberriesOrdersDeliveryMessage,
            transport: (string) $OrderEvent->getOrderProfile(),
        );
    }
}
