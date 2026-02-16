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

namespace BaksDev\Wildberries\Orders\Messenger\Schedules\NewOrders;

use BaksDev\Core\Deduplicator\DeduplicatorInterface;
use BaksDev\Core\Messenger\MessageDispatchInterface;
use BaksDev\Delivery\Type\Id\Choice\TypeDeliveryPickup;
use BaksDev\Delivery\Type\Id\DeliveryUid;
use BaksDev\Orders\Order\Entity\Order;
use BaksDev\Wildberries\Orders\Api\ClientInfo\FindClientWildberriesOrderPickupRequest;
use BaksDev\Wildberries\Orders\Api\FindAllWildberriesOrdersNewPickupRequest;
use BaksDev\Wildberries\Orders\Schedule\NewOrders\UpdateWildberriesOrdersNewSchedules;
use BaksDev\Wildberries\Orders\UseCase\New\NewWildberriesOrderDTO;
use BaksDev\Wildberries\Orders\UseCase\New\NewWildberriesOrderHandler;
use BaksDev\Wildberries\Products\Api\Cards\FindAllWildberriesCardsRequest;
use BaksDev\Wildberries\Products\Api\Cards\WildberriesCardDTO;
use BaksDev\Wildberries\Products\Messenger\Cards\CardNew\WildberriesCardNewMassage;
use BaksDev\Wildberries\Repository\AllWbTokensByProfile\AllWbTokensByProfileInterface;
use BaksDev\Wildberries\Type\id\WbTokenUid;
use Generator;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/** Получает список новых сборочных заданий Самовывоз */
#[AsMessageHandler]
final readonly class NewWildberriesOrderPickupScheduleDispatcher
{
    public function __construct(
        #[Target('wildberriesOrdersLogger')] private LoggerInterface $logger,
        private FindAllWildberriesOrdersNewPickupRequest $wildberriesOrdersNew,
        private DeduplicatorInterface $deduplicator,
        private NewWildberriesOrderHandler $WildberriesOrderHandler,
        private AllWbTokensByProfileInterface $AllWbTokensByProfileRepository,
        private FindClientWildberriesOrderPickupRequest $FindClientWildberriesOrdersRequest
    ) {}

    public function __invoke(NewWildberriesOrdersScheduleMessage $message): void
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

            $Deduplicator = $this->deduplicator
                ->namespace('wildberries-orders')
                ->expiresAfter(UpdateWildberriesOrdersNewSchedules::INTERVAL)
                ->deduplication([self::class, (string) $WbTokenUid]);

            if($Deduplicator->isExecuted())
            {
                continue;
            }

            /** Добавляем дедубликатор обновления (удалям в конце данного процесса) */
            $Deduplicator->save();

            /**
             * Получаем список НОВЫХ сборочных заданий
             */

            $orders = $this->wildberriesOrdersNew
                ->forTokenIdentifier($WbTokenUid)
                ->findAll();

            if(false === $orders || false === $orders->valid())
            {
                $Deduplicator->delete();
                continue;
            }

            $this->ordersCreate($orders, $message, $WbTokenUid);


            /** Удаляем дедубликатор обновления */
            $Deduplicator->delete();

        }
    }

    /**
     * Добавляем новые заказы Wildberries
     */
    private function ordersCreate(
        Generator $orders,
        NewWildberriesOrdersScheduleMessage $message,
        WbTokenUid $WbTokenUid
    ): void
    {
        /** @var NewWildberriesOrderDTO $WildberriesOrderDTO */
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

            $DeliveryUid = $WildberriesOrderDTO->getUsr()->getDelivery()->getDelivery();

            if(false === ($DeliveryUid instanceof DeliveryUid))
            {
                continue;
            }

            if(false === $DeliveryUid->equals(TypeDeliveryPickup::TYPE))
            {
                continue;
            }

            $Order = $this->WildberriesOrderHandler->handle($WildberriesOrderDTO);

            if($Order === true)
            {
                $this->logger->info(
                    sprintf('Новый заказ %s уже добавлен в систему', $WildberriesOrderDTO->getNumber()),
                    [self::class.':'.__LINE__],
                );

                $Deduplicator->save();

                continue;
            }


            $this->logger->info(
                sprintf('Добавили новый заказ %s', $WildberriesOrderDTO->getNumber()),
                [self::class.':'.__LINE__],
            );

            $Deduplicator->save();
        }
    }
}
