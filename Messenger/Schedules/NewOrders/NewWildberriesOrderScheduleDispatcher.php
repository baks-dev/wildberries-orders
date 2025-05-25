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

namespace BaksDev\Wildberries\Orders\Messenger\Schedules\NewOrders;

use BaksDev\Core\Deduplicator\DeduplicatorInterface;
use BaksDev\Core\Messenger\MessageDispatchInterface;
use BaksDev\Orders\Order\Entity\Order;
use BaksDev\Wildberries\Orders\Api\FindAllWildberriesOrdersNewRequest;
use BaksDev\Wildberries\Orders\Schedule\NewOrders\UpdateWildberriesOrdersNewSchedules;
use BaksDev\Wildberries\Orders\UseCase\New\WildberriesOrderDTO;
use BaksDev\Wildberries\Orders\UseCase\New\WildberriesOrderHandler;
use BaksDev\Wildberries\Products\Api\Cards\FindAllWildberriesCardsRequest;
use BaksDev\Wildberries\Products\Api\Cards\WildberriesCardDTO;
use BaksDev\Wildberries\Products\Messenger\Cards\CardNew\WildberriesCardNewMassage;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class NewWildberriesOrderScheduleDispatcher
{
    public function __construct(
        #[Target('wildberriesOrdersLogger')] private LoggerInterface $logger,
        private FindAllWildberriesOrdersNewRequest $wildberriesOrdersNew,
        private FindAllWildberriesCardsRequest $WildberriesCardsRequest,
        private DeduplicatorInterface $deduplicator,
        private WildberriesOrderHandler $WildberriesOrderHandler,
        private MessageDispatchInterface $messageDispatch
    ) {}

    public function __invoke(NewWildberriesOrdersScheduleMessage $message): void
    {
        $DeduplicatorExecuted = $this->deduplicator
            ->namespace('wildberries-orders')
            ->expiresAfter(UpdateWildberriesOrdersNewSchedules::INTERVAL)
            ->deduplication([$message->getProfile(), self::class]);

        if($message->isDeduplicator() && $DeduplicatorExecuted->isExecuted())
        {
            return;
        }

        $DeduplicatorExecuted->save();

        /**
         * Получаем список НОВЫХ сборочных заданий по основному идентификатору компании
         */

        $orders = $this->wildberriesOrdersNew
            ->profile($message->getProfile())
            ->findAll();


        if(false === $orders || false === $orders->valid())
        {
            return;
        }

        /** Добавляем новые заказы Wildberries */


        /** @var WildberriesOrderDTO $WildberriesOrderDTO */
        foreach($orders as $WildberriesOrderDTO)
        {
            $Deduplicator = $this->deduplicator
                ->namespace('wildberries-orders')
                ->expiresAfter('1 week')
                ->deduplication([$WildberriesOrderDTO->getNumber(), self::class]);

            if($message->isDeduplicator() && $Deduplicator->isExecuted())
            {
                continue;
            }

            $handle = $this->WildberriesOrderHandler->handle($WildberriesOrderDTO);

            if($handle === true)
            {
                $this->logger->info(
                    sprintf('Новый заказ %s уже добавлен в систему', $WildberriesOrderDTO->getNumber()),
                    [self::class.':'.__LINE__],
                );

                $Deduplicator->save();

                continue;
            }

            if(false === ($handle instanceof Order))
            {
                /**
                 * Пробуем обновить карточку по артикулу
                 */

                $article = explode(':', $handle);
                $article = current($article);

                $this->logger->critical(
                    sprintf('wildberries-orders: Ошибка при добавлении нового заказа %s. Пробуем добавить карточку по артикулу %s', $WildberriesOrderDTO->getNumber(), $article),
                    [$handle, $message->getProfile(), self::class.':'.__LINE__],
                );

                /** Получаем список карточек WB */

                $WildberriesCards = $this->WildberriesCardsRequest
                    ->profile($message->getProfile())
                    ->findAll($article);

                /** @var WildberriesCardDTO $WildberriesCardDTO */
                foreach($WildberriesCards as $WildberriesCardDTO)
                {
                    /** Передаем на обновление найденный артикул */
                    $WildberriesCardNewMassage = new WildberriesCardNewMassage(
                        profile: $message->getProfile(),
                        article: $WildberriesCardDTO->getArticle(),
                    );

                    $this->messageDispatch->dispatch(
                        $WildberriesCardNewMassage,
                        transport: (string) $message->getProfile(),
                    );
                }
            }

            $this->logger->info(
                sprintf('Добавили новый заказ %s', $WildberriesOrderDTO->getNumber()),
                [self::class.':'.__LINE__],
            );

            $Deduplicator->save();
        }
    }
}
