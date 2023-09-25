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

declare(strict_types=1);

namespace BaksDev\Wildberries\Orders\Messenger\UpdateOrdersStatus;

use BaksDev\Wildberries\Api\Token\Orders\WildberriesOrdersStatus;
use BaksDev\Wildberries\Orders\Entity\Event\WbOrdersEvent;
use BaksDev\Wildberries\Orders\Repository\AllOrdersByStatus\AllOrdersByStatusInterface;
use BaksDev\Wildberries\Orders\Type\OrderStatus\WbOrderStatus;
use BaksDev\Wildberries\Orders\Type\WildberriesStatus\Status\WildberriesStatusCanceled;
use BaksDev\Wildberries\Orders\Type\WildberriesStatus\Status\WildberriesStatusCanceledClient;
use BaksDev\Wildberries\Orders\Type\WildberriesStatus\Status\WildberriesStatusDefect;
use BaksDev\Wildberries\Orders\Type\WildberriesStatus\Status\WildberriesStatusSold;
use BaksDev\Wildberries\Orders\Type\WildberriesStatus\WildberriesStatus;
use BaksDev\Wildberries\Orders\UseCase\Command\NewEdit\WbOrderDTO;
use BaksDev\Wildberries\Orders\UseCase\Command\NewEdit\WbOrderHandler;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\TaggedIterator;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class UpdateOrdersStatusHandler
{
    private EntityManagerInterface $entityManager;
    private iterable $WildberriesStatus;
    private AllOrdersByStatusInterface $allOrdersByStatus;
    private WildberriesOrdersStatus $wildberriesOrdersStatus;
    private WbOrderHandler $WildberriesOrderHandler;
    private LoggerInterface $messageDispatchLogger;


    public function __construct(
        #[TaggedIterator('baks.wb.status')] iterable $WildberriesStatus,
        EntityManagerInterface $entityManager,
        AllOrdersByStatusInterface $allOrdersByStatus,
        WildberriesOrdersStatus $wildberriesOrdersStatus,
        WbOrderHandler $WildberriesOrderHandler,
        LoggerInterface $messageDispatchLogger,

    )
    {
        $this->entityManager = $entityManager;
        $this->WildberriesStatus = $WildberriesStatus;
        $this->allOrdersByStatus = $allOrdersByStatus;
        $this->wildberriesOrdersStatus = $wildberriesOrdersStatus;
        $this->WildberriesOrderHandler = $WildberriesOrderHandler;
        $this->messageDispatchLogger = $messageDispatchLogger;
    }

    public function __invoke(UpdateOrderStatusMessage $message): void
    {
        $profile = $message->getProfile();

        $this->messageDispatchLogger
            ->info(
                sprintf('%s: Обновляем статусы заказов Wildberries', $profile),
                [__LINE__ => __FILE__]
            );

        foreach($this->WildberriesStatus as $wbStatus)
        {
            /** Пропускаем заказы которые уже выполнены (со статусом Доставлен, Отменен, Дефект) */
            if(
                $wbStatus instanceof WildberriesStatusSold ||
                $wbStatus instanceof WildberriesStatusCanceled ||
                $wbStatus instanceof WildberriesStatusCanceledClient ||
                $wbStatus instanceof WildberriesStatusDefect
            )
            {
                continue;
            }

            /* Получаем все заказы по статусу */
            $orders = $this->allOrdersByStatus
                ->fetchAllOrdersByWildberriesStatusAssociativeIndexed($profile, $wbStatus);

            if(!$orders)
            {
                continue;
            }

            /** Делим все заказы по 1000 items */
            $chunkedOrders = array_chunk($orders, 1000);

            foreach($chunkedOrders as $chunkedOrder)
            {
                $wbOrdersAll = array_column($chunkedOrder, 'order_wb');

                /** Получаем Wildberries API указанные заказы и их статусы  */
                $apiWbOrdersStatus = $this->wildberriesOrdersStatus
                    ->profile($profile)
                    ->setOrders($wbOrdersAll)
                    ->request()
                    ->getContent();

                foreach($apiWbOrdersStatus as $apiStatus)
                {
                    /** Не обновляем заказы со статусом Новый */
                    if($apiStatus['supplierStatus'] === 'new' && $apiStatus['wbStatus'] === 'waiting')
                    {
                        continue;
                    }

                    $currentOrder = $orders[$apiStatus['id']];

                    /** Если у заказа был изменен статус - обновляем заказ с новым статусом Wildberries */
                    if(
                        $currentOrder['event_status'] !== $apiStatus['supplierStatus'] ||
                        $currentOrder['event_wildberries'] !== $apiStatus['wbStatus']
                    )
                    {
                        $WbOrdersEvent = $this->entityManager->getRepository(WbOrdersEvent::class)->find($currentOrder['order_event']);
                        $WbOrderDTO = new WbOrderDTO($profile, $apiStatus['id']);
                        $WbOrdersEvent->getDto($WbOrderDTO);

                        $WbOrderDTO->setStatus(new WbOrderStatus($apiStatus['supplierStatus']));
                        $WbOrderDTO->setWildberries(new WildberriesStatus($apiStatus['wbStatus']));

                        $this->WildberriesOrderHandler->handle($WbOrderDTO);

                        $this->messageDispatchLogger
                            ->info(
                                sprintf('%s: Обновили статус заказа Wildberries ( order : %s ) ', $profile, $apiStatus['id']),
                                [__LINE__ => __FILE__]
                            );
                    }
                }
            }
        }

    }
}