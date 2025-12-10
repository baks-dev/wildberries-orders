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

namespace BaksDev\Wildberries\Orders\Messenger\Dbs\Package;


use BaksDev\Core\Messenger\MessageDelay;
use BaksDev\Core\Messenger\MessageDispatchInterface;
use BaksDev\Orders\Order\Entity\Event\OrderEvent;
use BaksDev\Orders\Order\Repository\CurrentOrderEvent\CurrentOrderEventInterface;
use BaksDev\Orders\Order\Type\Status\OrderStatus\Collection\OrderStatusCanceled;
use BaksDev\Orders\Order\Type\Status\OrderStatus\Collection\OrderStatusPackage;
use BaksDev\Wildberries\Orders\Api\Dbs\UpdateWildberriesOrdersPackageRequest;
use BaksDev\Wildberries\Type\id\WbTokenUid;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/** Метод обновляет статус заказа в маркетплейсе нa Package «На сборке»  */
#[AsMessageHandler(priority: 0)]
final readonly class WildberriesOrdersPackageDispatcher
{
    public function __construct(
        private CurrentOrderEventInterface $CurrentOrderEventRepository,
        private UpdateWildberriesOrdersPackageRequest $UpdateWildberriesOrdersPackageRequest,
        private MessageDispatchInterface $messageDispatch,
    ) {}

    /** Метод обновляет статус заказа в маркетплейсе нa Package «На сборке»  */
    public function __invoke(WildberriesOrdersPackageMessage $message): void
    {
        $OrderEvent = $this->CurrentOrderEventRepository
            ->forOrder($message->getId())
            ->find();

        if(false === ($OrderEvent instanceof OrderEvent))
        {
            return;
        }

        if(false === $OrderEvent->isStatusEquals(OrderStatusPackage::class))
        {
            return;
        }

        /** Токен из заказа в системе (был установлен при получении заказа из Ozon) */
        $WbTokenUid = new WbTokenUid($OrderEvent->getOrderTokenIdentifier());

        /**
         * Обновляем статус заказа в маркетплейсе нa Package «На сборке»
         */
        $isPackage = $this->UpdateWildberriesOrdersPackageRequest
            ->forTokenIdentifier($WbTokenUid)
            ->update($OrderEvent->getOrderNumber());

        /** Пробуем обновить через минуту */
        if(false === $isPackage)
        {
            $this->messageDispatch->dispatch(
                $message,
                stamps: [new MessageDelay('1 minute')],
                transport: (string) $OrderEvent->getOrderProfile().'-low',
            );
        }

    }
}
