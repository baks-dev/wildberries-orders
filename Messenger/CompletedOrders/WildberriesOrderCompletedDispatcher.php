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

use BaksDev\Core\Doctrine\DBALQueryBuilder;
use BaksDev\Core\Messenger\MessageDelay;
use BaksDev\Core\Messenger\MessageDispatchInterface;
use BaksDev\Materials\Sign\BaksDevMaterialsSignBundle;
use BaksDev\Materials\Sign\Repository\MaterialSignByOrder\MaterialSignByOrderRepository;
use BaksDev\Orders\Order\Entity\Event\OrderEvent;
use BaksDev\Orders\Order\Entity\Order;
use BaksDev\Orders\Order\Repository\CurrentOrderEvent\CurrentOrderEventInterface;
use BaksDev\Orders\Order\Type\Status\OrderStatus\OrderStatusCompleted;
use BaksDev\Orders\Order\UseCase\Admin\Status\OrderStatusDTO;
use BaksDev\Orders\Order\UseCase\Admin\Status\OrderStatusHandler;
use BaksDev\Products\Sign\BaksDevProductsSignBundle;
use BaksDev\Products\Sign\Repository\ProductSignByOrder\ProductSignByOrderRepository;
use BaksDev\Wildberries\Orders\Api\FindAllWildberriesOrdersStatusRequest;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(priority: 0)]
final class WildberriesOrderCompletedDispatcher
{
    public function __construct(
        #[Target('wildberriesOrdersLogger')] private LoggerInterface $logger,
        private FindAllWildberriesOrdersStatusRequest $FindAllWildberriesOrdersStatusRequest,
        private MessageDispatchInterface $messageDispatch,
        private CurrentOrderEventInterface $CurrentOrderEvent,
        private OrderStatusHandler $OrderStatusHandler,
        private DBALQueryBuilder $DBALQueryBuilder,
    ) {}

    public function __invoke(WildberriesOrderCompletedMessage $message): bool
    {

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
                return false;
            }

            /**  Делаем повторную проверку позже */

            $this->messageDispatch->dispatch(
                message: $message,
                stamps: [new MessageDelay('1 day')],
                transport: 'wildberries-orders-low',
            );

            return false;
        }


        /**
         * Поиск сырьевого честного знака
         */

        if(class_exists(BaksDevMaterialsSignBundle::class))
        {
            $MaterialSignByOrderRepository = new MaterialSignByOrderRepository($this->DBALQueryBuilder);

            $MaterialSigns = $MaterialSignByOrderRepository
                ->forOrder($message->getOrder())
                ->findAll();

            if($MaterialSigns)
            {
                dd($MaterialSigns); /* TODO: удалить !!! */
            }

        }


        /**
         * Поиск продуктового честного знака
         */

        if(class_exists(BaksDevProductsSignBundle::class))
        {
            $ProductSignByOrderRepository = new ProductSignByOrderRepository($this->DBALQueryBuilder);

            $ProductSigns = $ProductSignByOrderRepository
                ->forOrder($message->getOrder())
                ->findAll();

            if($ProductSigns)
            {
                dd($ProductSigns); /* TODO: удалить !!! */
            }

        }


        dd('.....................'); /* TODO: удалить !!! */

        /**
         * Обновляем статус заказа на Completed «Выполнен»
         */

        $OrderEvent = $this->CurrentOrderEvent
            ->forOrder($message->getOrder())
            ->find();

        if(false === ($OrderEvent instanceof OrderEvent))
        {
            return false;
        }

        $OrderStatusDTO = new OrderStatusDTO(
            OrderStatusCompleted::class,
            $OrderEvent->getId(),
        );

        $Order = $this->OrderStatusHandler->handle($OrderStatusDTO);

        if(false === ($Order instanceof Order))
        {
            $this->logger->critical(
                sprintf('wildberries-orders: Ошибка %s при изменении статуса заказа на Completed «Выполнен»', $Order),
                [$message, self::class.':'.__LINE__]
            );

            return false;
        }

        return true;
    }
}
