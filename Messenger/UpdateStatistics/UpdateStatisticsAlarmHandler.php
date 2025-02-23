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

namespace BaksDev\Wildberries\Orders\Messenger\UpdateStatistics;

use BaksDev\Wildberries\Orders\Entity\WbOrdersStatistics;
use BaksDev\Wildberries\Orders\Repository\WbOrdersAlarm\WbOrdersAlarmInterface;
use BaksDev\Wildberries\Orders\UseCase\Command\Statistic\WbOrdersStatisticsDTO;
use BaksDev\Wildberries\Orders\UseCase\Command\Statistic\WbOrdersStatisticsHandler;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(priority: 10)]
final readonly class UpdateStatisticsAlarmHandler
{
    public function __construct(
        #[Target('wildberriesOrdersLogger')] private LoggerInterface $logger,
        private EntityManagerInterface $entityManager,
        private WbOrdersAlarmInterface $ordersAlarm,
        private WbOrdersStatisticsHandler $ordersStatisticsHandler,
    ) {}

    /**
     * Обновляем статистику срочных заказов Wildberries
     */
    public function __invoke(UpdateStatisticMessage $message): void
    {
        $this->logger->info(
            'Обновляем статистику срочных заказов Wildberries',
            [
                'ProductUid' => $message->getProduct(),
                self::class.':'.__LINE__
            ]
        );

        /**
         * Получаем объект статистики, если не найден - будет создан новый
         */

        $WbOrdersProductStats = $this->entityManager
            ->getRepository(WbOrdersStatistics::class)
            ->find($message->getProduct());

        $WbOrdersStatisticsDTO = new WbOrdersStatisticsDTO();
        $WbOrdersStatisticsDTO->setProduct($message->getProduct());
        false === ($WbOrdersProductStats instanceof WbOrdersStatistics) ?: $WbOrdersProductStats->getDto($WbOrdersStatisticsDTO);

        /**
         * Получаем и обновляем срочные
         */

        $alarm = $this->ordersAlarm->countOrderAlarmByProduct($message->getProductEvent());
        $WbOrdersStatisticsDTO->setAlarm($alarm);
        $StatisticsHandler = $this->ordersStatisticsHandler->handle($WbOrdersStatisticsDTO);

        if(!$StatisticsHandler instanceof WbOrdersStatistics)
        {
            $this->logger->warning(
                'Ошибка при обновлении статистики срочных заказов Wildberries',
                [
                    'code' => $StatisticsHandler,
                    'ProductUid' => $message->getProduct(),
                    self::class.':'.__LINE__
                ]);
        }
    }
}