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

namespace BaksDev\Wildberries\Orders\Messenger\UpdateOrdersStatus;

use BaksDev\Wildberries\Orders\Api\WildberriesOrdersStatusRequest;
use BaksDev\Wildberries\Orders\Entity\Event\WbOrdersEvent;
use BaksDev\Wildberries\Orders\Entity\WbOrders;
use BaksDev\Wildberries\Orders\Repository\AllOrdersByStatus\AllOrdersByStatusInterface;
use BaksDev\Wildberries\Orders\Type\OrderStatus\WbOrderStatus;
use BaksDev\Wildberries\Orders\Type\WildberriesStatus\Status\WildberriesStatusCanceled;
use BaksDev\Wildberries\Orders\Type\WildberriesStatus\Status\WildberriesStatusCanceledClient;
use BaksDev\Wildberries\Orders\Type\WildberriesStatus\Status\WildberriesStatusDefect;
use BaksDev\Wildberries\Orders\Type\WildberriesStatus\Status\WildberriesStatusSold;
use BaksDev\Wildberries\Orders\Type\WildberriesStatus\WildberriesStatus;
use BaksDev\Wildberries\Orders\UseCase\Command\Status\StatusWbOrderDTO;
use BaksDev\Wildberries\Orders\UseCase\Command\Status\StatusWbOrderHandler;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class UpdateOrdersStatusHandler
{
    public function __construct(
        #[AutowireIterator('baks.wb.status')] private iterable $WildberriesStatus,
        #[Target('wildberriesOrdersLogger')] private LoggerInterface $logger,
        private EntityManagerInterface $entityManager,
        private AllOrdersByStatusInterface $allOrdersByStatus,
        private WildberriesOrdersStatusRequest $wildberriesOrdersStatus,
        private StatusWbOrderHandler $statusWbOrderHandler
    ) {}

    /**
     * Метод обновляет статусы заказов
     */
    public function __invoke(UpdateOrdersStatusMessage $message): void
    {

        // TODO: Получить все заказы Wildberries, проверить статусы и обновить
        // возможно достаточно только отмененную
        return;

        $profile = $message->getProfile();


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
                        $WbOrdersEvent = $this->entityManager
                            ->getRepository(WbOrdersEvent::class)
                            ->find($currentOrder['order_event']);

                        /** @var StatusWbOrderDTO $WbOrderDTO */
                        $WbOrderDTO = $WbOrdersEvent->getDto(StatusWbOrderDTO::class);

                        $WbOrderDTO->setStatus(new WbOrderStatus($apiStatus['supplierStatus']));
                        $WbOrderDTO->setWildberries(new WildberriesStatus($apiStatus['wbStatus']));

                        $handle = $this->statusWbOrderHandler->handle($WbOrderDTO);

                        if($handle instanceof WbOrders)
                        {
                            $this->logger
                                ->info(
                                    'Обновили статус заказа Wildberries',
                                    [
                                        'profile' => $profile,
                                        'order' => $apiStatus['id']
                                    ]
                                );

                            continue;
                        }

                        $this->logger
                            ->critical(
                                sprintf('%s: Ошибка при обновлении статуса заказа Wildberries ', $handle),
                                [
                                    'profile' => $profile,
                                    'order' => $apiStatus['id'],
                                    self::class.':'.__LINE__
                                ]
                            );

                    }
                }
            }
        }

    }
}