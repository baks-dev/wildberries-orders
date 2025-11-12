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

namespace BaksDev\Wildberries\Orders\Messenger\CompletedOrders;


use BaksDev\Core\Deduplicator\DeduplicatorInterface;
use BaksDev\Core\Messenger\MessageDelay;
use BaksDev\Core\Messenger\MessageDispatchInterface;
use BaksDev\Orders\Order\Entity\Event\OrderEvent;
use BaksDev\Orders\Order\Messenger\OrderMessage;
use BaksDev\Orders\Order\Repository\CurrentOrderEvent\CurrentOrderEventInterface;
use BaksDev\Orders\Order\Repository\OrderEvent\OrderEventInterface;
use BaksDev\Orders\Order\Type\Status\OrderStatus\Collection\OrderStatusMarketplace;
use BaksDev\Orders\Order\Type\Status\OrderStatus\Collection\OrderStatusNew;
use BaksDev\Users\Profile\UserProfile\Type\Id\UserProfileUid;
use BaksDev\Wildberries\Orders\Type\DeliveryType\TypeDeliveryFbsWildberries;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * Запускаем ежедневную проверку статуса заказа при доставке службой маркетплейса
 */
#[AsMessageHandler(priority: 0)]
final class CheckStatusWhenChangeOrderStatusDispatcher
{
    public function __construct(
        private OrderEventInterface $OrderEventRepository,
        private CurrentOrderEventInterface $CurrentOrderEvent,
        private DeduplicatorInterface $deduplicator,
        private MessageDispatchInterface $messageDispatch,
    ) {}

    public function __invoke(OrderMessage $message): void
    {

        /** Дедубликатор по идентификатору заказа */
        $Deduplicator = $this->deduplicator
            ->namespace('orders-order')
            ->deduplication([
                (string) $message->getId(),
                self::class
            ]);

        if($Deduplicator->isExecuted() === true)
        {
            return;
        }

        $OrderEvent = $this->OrderEventRepository
            ->find($message->getEvent());

        if(false === ($OrderEvent instanceof OrderEvent))
        {
            return;
        }

        /** Если статус заказа не Marketplace «Передан службе маркетплейса» */
        if(false === $OrderEvent->isStatusEquals(OrderStatusMarketplace::class))
        {
            return;
        }

        /** Если тип заказа не FBS Wildberries «Доставка службой Wildberries» */
        if(false === $OrderEvent->isDeliveryTypeEquals(TypeDeliveryFbsWildberries::TYPE))
        {
            return;
        }

        /** Получаем активное событие заказа в случае если статус заказа изменился */
        if(false === ($OrderEvent->getOrderProfile() instanceof UserProfileUid))
        {
            $OrderEvent = $this->CurrentOrderEvent
                ->forOrder($message->getId())
                ->find();

            if(false === ($OrderEvent instanceof OrderEvent))
            {
                return;
            }
        }

        /** Запускаем ежедневную проверку статусов  */

        $WildberriesOrderCompletedMessage = new WildberriesOrderCompletedMessage(
            $OrderEvent->getOrderProfile(),
            $OrderEvent->getMain(),
            $OrderEvent->getOrderNumber()
        );

        $this->messageDispatch->dispatch(
            $WildberriesOrderCompletedMessage,
            stamps: [new MessageDelay('1 day')],
            transport: $OrderEvent->getOrderProfile().'-low',
        );
    }
}
