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

namespace BaksDev\Wildberries\Orders\Messenger\Schedules\CancelOrders;

use BaksDev\Centrifugo\Server\Publish\CentrifugoPublishInterface;
use BaksDev\Core\Deduplicator\DeduplicatorInterface;
use BaksDev\Orders\Order\Entity\Event\OrderEvent;
use BaksDev\Orders\Order\Entity\Order;
use BaksDev\Orders\Order\Repository\CurrentOrderEvent\CurrentOrderEventInterface;
use BaksDev\Orders\Order\UseCase\Admin\Canceled\CanceledOrderDTO;
use BaksDev\Orders\Order\UseCase\Admin\Status\OrderStatusHandler;
use BaksDev\Wildberries\Orders\Api\FindAllWildberriesOrdersStatusRequest;
use BaksDev\Wildberries\Orders\Repository\AllWbOrdersNew\AllWbOrdersNewInterface;
use BaksDev\Wildberries\Orders\Schedule\UpdateOrdersStatus\UpdateWildberriesOrdersCancelSchedules;
use BaksDev\Wildberries\Repository\AllWbTokensByProfile\AllWbTokensByProfileInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class CancelWildberriesOrdersScheduleDispatcher
{
    public function __construct(
        #[Target('wildberriesOrdersLogger')] private LoggerInterface $logger,
        private AllWbOrdersNewInterface $AllWbOrdersNewInterface,
        private FindAllWildberriesOrdersStatusRequest $FindAllWildberriesOrdersStatusRequest,
        private CurrentOrderEventInterface $CurrentOrderEvent,
        private CentrifugoPublishInterface $CentrifugoPublish,
        private OrderStatusHandler $OrderStatusHandler,
        private DeduplicatorInterface $deduplicator,
        private AllWbTokensByProfileInterface $AllWbTokensByProfileRepository,
    ) {}

    /**
     * Метод обновляет статусы заказов
     */
    public function __invoke(CancelWildberriesOrdersScheduleMessage $message): void
    {
        /** Получаем все токены профиля */

        $tokensByProfile = $this->AllWbTokensByProfileRepository
            ->forProfile($message->getProfile())
            ->findAll();

        if(false === $tokensByProfile || false === $tokensByProfile->valid())
        {
            return;
        }

        foreach($tokensByProfile as $WbTokenUid)
        {
            /**
             * Ограничиваем периодичность запросов
             */
            $DeduplicatorExecuted = $this->deduplicator
                ->namespace('wildberries-orders')
                ->expiresAfter(UpdateWildberriesOrdersCancelSchedules::INTERVAL)
                ->deduplication([$WbTokenUid, self::class]);

            if($message->isDeduplicator() && $DeduplicatorExecuted->isExecuted())
            {
                continue;
            }

            /** Добавляем дедубликатор обновления (удалям в конце данного процесса) */
            $DeduplicatorExecuted->save();

            /**
             * Получаем все заказы Wildberries
             */

            $orders = $this->AllWbOrdersNewInterface
                ->forProfile($message->getProfile())
                ->findAll();

            $orders = iterator_to_array($orders);

            /** Присваиваем токен авторизации */
            $this->FindAllWildberriesOrdersStatusRequest
                ->forTokenIdentifier($WbTokenUid)
                ->profile($message->getProfile())
                ->clearOrders();

            /** Добавляем в объект Request идентификатор заказа Wildberries для получения его статуса */
            array_map(function($OrderUid) {
                $this->FindAllWildberriesOrdersStatusRequest->addOrder($OrderUid->getAttr());
            }, iterator_to_array($orders));

            /** Получаем список отмен */
            $cancels = $this->FindAllWildberriesOrdersStatusRequest->findOrderCancel();

            if(false === $cancels)
            {
                continue;
            }

            foreach($cancels as $cancel)
            {
                $Deduplicator = $this->deduplicator
                    ->expiresAfter('30 days')
                    ->deduplication([$cancel, self::class]);

                if($Deduplicator->isExecuted())
                {
                    continue;
                }

                /**
                 * Отменяем заказы
                 */

                $filter = array_filter($orders, static function($OrderUid) use ($cancel) {
                    return $OrderUid->getAttr() === $cancel;
                });

                $OrderUid = current($filter);

                if(false === $OrderUid)
                {
                    $this->logger->warning('Заказа %s для отмены не найдено', $cancel);

                    continue;
                }

                $OrderEvent = $this->CurrentOrderEvent
                    ->forOrder($OrderUid)
                    ->find();

                if(false === ($OrderEvent instanceof OrderEvent))
                {
                    $this->logger->warning('События для отмены заказа  %s не найдено', $cancel);

                    continue;
                }

                /**
                 * Отправляем сокет для скрытия
                 */

                $this->CentrifugoPublish
                    ->addData(['identifier' => (string) $OrderUid])
                    ->addData(['profile' => false])
                    ->send('remove');

                $this->CentrifugoPublish
                    ->addData(['profile' => false]) // Скрывает у всех
                    ->addData(['order' => (string) $OrderUid])
                    ->send('orders');


                /**
                 * Отменяем заказ
                 */

                $OrderCanceledDTO = new CanceledOrderDTO();
                $OrderEvent->getDto($OrderCanceledDTO);
                $OrderCanceledDTO->setComment('Отмена пользователем Wildberries');


                $Order = $this->OrderStatusHandler->handle($OrderCanceledDTO);

                if(false === ($Order instanceof Order))
                {
                    $this->logger->critical('Ошибка при отмене заказа %s', $cancel);
                    continue;
                }

                $Deduplicator->save();

                $this->logger->info(sprintf('Заказ %s успешно отменен', $cancel));
            }
        }
    }
}