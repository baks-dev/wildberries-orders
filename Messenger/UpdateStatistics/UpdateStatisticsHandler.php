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

namespace BaksDev\Wildberries\Orders\Messenger\UpdateStatistics;

use BaksDev\Orders\Order\Entity\Order;
use BaksDev\Orders\Order\Entity\Products\OrderProduct;
use BaksDev\Products\Product\Entity\Product;
use BaksDev\Wildberries\Orders\Entity\WbOrdersStatistics;
use BaksDev\Wildberries\Orders\Messenger\WbOrderMessage;
use BaksDev\Wildberries\Orders\Repository\WbOrdersAlarm\WbOrdersAlarmInterface;
use BaksDev\Wildberries\Orders\Repository\WbOrdersAnalog\WbOrdersAnalogInterface;
use BaksDev\Wildberries\Orders\Repository\WbOrdersOld\WbOrdersOldInterface;
use BaksDev\Wildberries\Orders\UseCase\Command\Statistic\WbOrdersStatisticsDTO;
use BaksDev\Wildberries\Orders\UseCase\Command\Statistic\WbOrdersStatisticsHandler;
use Doctrine\ORM\EntityManagerInterface;
use DomainException;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class UpdateStatisticsHandler
{
    private EntityManagerInterface $entityManager;
    private WbOrdersAlarmInterface $ordersAlarm;
    private WbOrdersAnalogInterface $ordersAnalog;
    private WbOrdersOldInterface $ordersOld;
    private WbOrdersStatisticsHandler $ordersStatisticsHandler;
    private LoggerInterface $messageDispatchLogger;

    public function __construct(
        EntityManagerInterface $entityManager,
        WbOrdersAlarmInterface $ordersAlarm,
        WbOrdersAnalogInterface $ordersAnalog,
        WbOrdersOldInterface $ordersOld,
        WbOrdersStatisticsHandler $ordersStatisticsHandler,
        LoggerInterface $messageDispatchLogger,
    )
    {
        $this->entityManager = $entityManager;
        $this->ordersAlarm = $ordersAlarm;
        $this->ordersAnalog = $ordersAnalog;
        $this->ordersOld = $ordersOld;
        $this->ordersStatisticsHandler = $ordersStatisticsHandler;
        $this->messageDispatchLogger = $messageDispatchLogger;
    }

    /**
     * При обновлении заказа - обновляем статистику по продукции в заказе
     */
    public function __invoke(WbOrderMessage $message): void
    {

        /**
         * Получаем заказ по идентификатору
         */

        $Order = $this->entityManager->getRepository(Order::class)->find($message->getId());

        if(!$Order)
        {
            $this->messageDispatchLogger->warning(
                sprintf('Невозможно найти заказ ( %s id=\'%s\' )', Order::TABLE, $message->getId()),
                [__LINE__ => __FILE__]);
            return;
        }

        /**
         * Получаем продукцию в заказе
         */

        $products = $this->entityManager
            ->getRepository(OrderProduct::class)
            ->findBy(['event' => $Order->getEvent()]);


        if(!$products)
        {
            $this->messageDispatchLogger->warning(
                sprintf('Невозможно найти продукцию ( %s event=\'%s\' )', OrderProduct::TABLE, $Order->getEvent()),
                [__LINE__ => __FILE__]
            );
            return;
        }

        $this->entityManager->clear();

        foreach($products as $data)
        {
            /** @var Product $Product */
            $Product = $this->entityManager
                ->getRepository(Product::class)
                ->findOneBy(['event' => $data->getProduct()]);

            if(!$Product)
            {
                continue;
            }

            $this->messageDispatchLogger->info(
                sprintf('Обновляем статистику Wildberries продукции ( ProductUid : %s )', $Product->getId()) ,
                [__LINE__ => __FILE__]);

            $productUid = $Product->getId();
            $ProductEventUid = $Product->getEvent();

            /**
             * Получаем объект статистики, если не найден - создаем новый
             */
            $WbOrdersProductStats = $this->entityManager
                ->getRepository(WbOrdersStatistics::class)
                ->find($productUid);

            $WbOrdersStatisticsDTO = new WbOrdersStatisticsDTO();
            $WbOrdersStatisticsDTO->setProduct($productUid);
            $WbOrdersProductStats ? $WbOrdersProductStats->getDto($WbOrdersStatisticsDTO) : false;

            /**
             * Получаем и обновляем срочные
             */
            $alarm = $this->ordersAlarm->countOrderAlarmByProduct($ProductEventUid);
            $WbOrdersStatisticsDTO->setAlarm($alarm);


            /**
             * Получаем и обновляем аналоги
             */
            $analog = $this->ordersAnalog->countOrderAnalogByProduct($ProductEventUid);
            $WbOrdersStatisticsDTO->setAnalog($analog);


            /**
             * Получаем и обновляем дату самого старого невыполненного заказа
             */
            $old = $this->ordersOld->getOldOrderDateByProduct($ProductEventUid);
            $WbOrdersStatisticsDTO->setOld($old);

            /**
             * Обновляем статистику по продукции
             */

            $StatisticsHandler = $this->ordersStatisticsHandler->handle($WbOrdersStatisticsDTO);

            if(!$StatisticsHandler instanceof WbOrdersStatistics)
            {
                throw new DomainException(sprintf(
                    '%s: Ошибка при обновлении статистики продукта ( ProductUid : %s )',
                    $StatisticsHandler,
                    $productUid
                ));
            }

        }
    }
}