<?php
/*
 *  Copyright 2026.  Baks.dev <admin@baks.dev>
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
use BaksDev\Materials\Sign\BaksDevMaterialsSignBundle;
use BaksDev\Materials\Sign\Entity\Event\MaterialSignEvent;
use BaksDev\Materials\Sign\Entity\MaterialSign;
use BaksDev\Materials\Sign\Repository\CurrentEvent\MaterialSignCurrentEventInterface;
use BaksDev\Materials\Sign\Repository\MaterialSignByOrder\MaterialSignByOrderInterface;
use BaksDev\Materials\Sign\UseCase\Admin\Status\MaterialSignDoneDTO;
use BaksDev\Materials\Sign\UseCase\Admin\Status\MaterialSignStatusHandler;
use BaksDev\Orders\Order\Entity\Event\OrderEvent;
use BaksDev\Orders\Order\Entity\Order;
use BaksDev\Orders\Order\Repository\CurrentOrderEvent\CurrentOrderEventInterface;
use BaksDev\Orders\Order\Type\Status\OrderStatus\Collection\OrderStatusCompleted;
use BaksDev\Orders\Order\UseCase\Admin\Status\OrderStatusDTO;
use BaksDev\Orders\Order\UseCase\Admin\Status\OrderStatusHandler;
use BaksDev\Products\Sign\BaksDevProductsSignBundle;
use BaksDev\Products\Sign\Entity\Event\ProductSignEvent;
use BaksDev\Products\Sign\Entity\ProductSign;
use BaksDev\Products\Sign\Repository\CurrentEvent\ProductSignCurrentEventInterface;
use BaksDev\Products\Sign\Repository\ProductSignByOrder\ProductSignByOrderInterface;
use BaksDev\Products\Sign\UseCase\Admin\Status\ProductSignDoneDTO;
use BaksDev\Products\Sign\UseCase\Admin\Status\ProductSignStatusHandler;
use BaksDev\Wildberries\Orders\Api\FindAllWildberriesOrdersStatusFbsRequest;
use BaksDev\Wildberries\Orders\Commands\UpdateWildberriesOrdersCompletedCommand;
use BaksDev\Wildberries\Orders\Type\DeliveryType\TypeDeliveryFbsWildberries;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(priority: 0)]
final readonly class WildberriesOrderCompletedDispatcher
{
    public function __construct(
        #[Target('wildberriesOrdersLogger')] private LoggerInterface $logger,
        private FindAllWildberriesOrdersStatusFbsRequest $FindAllWildberriesOrdersStatusRequest,
        private MessageDispatchInterface $messageDispatch,
        private CurrentOrderEventInterface $CurrentOrderEvent,
        private OrderStatusHandler $OrderStatusHandler,
        private DeduplicatorInterface $deduplicator,

        private ?MaterialSignByOrderInterface $MaterialSignByOrder = null,
        private ?MaterialSignCurrentEventInterface $MaterialSignCurrentEvent = null,
        private ?MaterialSignStatusHandler $MaterialSignStatusHandler = null,

        private ?ProductSignByOrderInterface $ProductSignByOrder = null,
        private ?ProductSignCurrentEventInterface $ProductSignCurrentEvent = null,
        private ?ProductSignStatusHandler $ProductSignStatusHandler = null,

    ) {}

    public function __invoke(WildberriesOrderCompletedMessage $message): void
    {
        /** Дедубликатор по идентификатору заказа */
        $Deduplicator = $this->deduplicator
            ->namespace('orders-order')
            ->deduplication([
                (string) $message->getOrder(),
                self::class,
            ]);

        if($Deduplicator->isExecuted() === true)
        {
            return;
        }


        $OrderEvent = $this->CurrentOrderEvent
            ->forOrder($message->getOrder())
            ->find();

        if(false === ($OrderEvent instanceof OrderEvent))
        {
            return;
        }

        if(false === $OrderEvent->isDeliveryTypeEquals(TypeDeliveryFbsWildberries::TYPE))
        {
            $Deduplicator->save();
            return;
        }

        if($OrderEvent->isStatusEquals(OrderStatusCompleted::class))
        {
            return;
        }

        $isCompleted = $this->FindAllWildberriesOrdersStatusRequest
            ->profile($message->getProfile())
            ->addOrder($message->getIdentifier())
            ->findOrderCompleted();

        if(false === $isCompleted)
        {
            /** Поверяем, что заказ не отменен */

            $isCancel = $this->FindAllWildberriesOrdersStatusRequest->findOrderCancel();

            if(false !== $isCancel)
            {
                return;
            }

            /**  Делаем повторную проверку позже */

            $this->logger->warning(
                sprintf('Заказ %s еще не выполнен! Делаем проверку позже', $message->getIdentifier()),
            );

            $this->messageDispatch->dispatch(
                message: $message,
                stamps: [new MessageDelay('1 day')],
                transport: $message->getProfile().'-low',
            );

            return;
        }

        /**
         * Поиск сырьевого честного знака
         */

        if(class_exists(BaksDevMaterialsSignBundle::class))
        {
            $MaterialSigns = $this->MaterialSignByOrder
                ->forOrder($message->getOrder())
                ->findAll();

            if($MaterialSigns)
            {
                foreach($MaterialSigns as $MaterialSign)
                {
                    $MaterialSignEvent = $this->MaterialSignCurrentEvent
                        ->forMaterialSign($MaterialSign['id'])
                        ->find();

                    if(false === ($MaterialSignEvent instanceof MaterialSignEvent))
                    {
                        continue;
                    }

                    $MaterialSignDoneDTO = new MaterialSignDoneDTO();
                    $MaterialSignEvent->getDto($MaterialSignDoneDTO);

                    $MaterialSignHandle = $this->MaterialSignStatusHandler->handle($MaterialSignDoneDTO);

                    if(false === ($MaterialSignHandle instanceof MaterialSign))
                    {
                        $this->logger->critical(
                            sprintf('wildberries-orders: Ошибка %s при изменении статуса сырьевого честного знака на Done «Выполнен»', $MaterialSignHandle),
                            [
                                var_export($message, true),
                                self::class.':'.__LINE__,
                            ],
                        );

                        continue;
                    }

                    $this->logger->info(sprintf('%s: применили статус сырьевого честного знака на Done «Выполнен»', $message->getIdentifier()));
                }
            }

        }


        /**
         * Поиск продуктового честного знака
         */

        if(class_exists(BaksDevProductsSignBundle::class))
        {
            $ProductSigns = $this->ProductSignByOrder
                ->forOrder($message->getOrder())
                ->findAll();

            if($ProductSigns)
            {
                foreach($ProductSigns as $ProductSign)
                {
                    $ProductSignEvent = $this->ProductSignCurrentEvent
                        ->forProductSign($ProductSign['id'])
                        ->find();

                    if(false === ($ProductSignEvent instanceof ProductSignEvent))
                    {
                        continue;
                    }

                    $ProductSignDoneDTO = new ProductSignDoneDTO();
                    $ProductSignEvent->getDto($ProductSignDoneDTO);

                    $ProductSignHandle = $this->ProductSignStatusHandler->handle($ProductSignDoneDTO);

                    if(false === ($ProductSignHandle instanceof ProductSign))
                    {
                        $this->logger->critical(
                            sprintf('wildberries-orders: Ошибка %s при изменении статуса продуктового честного знака на Done «Выполнен»', $ProductSignHandle),
                            [
                                var_export($message, true),
                                self::class.':'.__LINE__,
                            ],
                        );

                        continue;
                    }

                    $this->logger->info(sprintf('%s: применили статус продуктового честного знака на Done «Выполнен»', $message->getIdentifier()));
                }
            }
        }

        /**
         * Обновляем статус заказа на Completed «Выполнен»
         */

        $OrderStatusDTO = new OrderStatusDTO(
            OrderStatusCompleted::class,
            $OrderEvent->getId(),
        );

        $Order = $this->OrderStatusHandler->handle($OrderStatusDTO);

        if(false === ($Order instanceof Order))
        {
            $this->logger->critical(
                sprintf('wildberries-orders: Ошибка %s при изменении статуса заказа на Completed «Выполнен»', $Order),
                [$message, self::class.':'.__LINE__],
            );

            return;
        }

        $this->logger->info(sprintf('%s: применили статус заказа на Completed «Выполнен»', $message->getIdentifier()));
    }
}
