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

use BaksDev\Core\Messenger\MessageDispatchInterface;
use BaksDev\Orders\Order\Entity\Order;
use BaksDev\Orders\Order\Entity\Products\OrderProduct;
use BaksDev\Products\Product\Entity\Product;
use BaksDev\Wildberries\Orders\Messenger\WbOrderMessage;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class UpdateStatisticsHandler
{
    private EntityManagerInterface $entityManager;
    private LoggerInterface $logger;
    private MessageDispatchInterface $messageDispatch;

    public function __construct(
        MessageDispatchInterface $messageDispatch,
        EntityManagerInterface $entityManager,
        LoggerInterface $wildberriesOrdersLogger,
    )
    {
        $this->entityManager = $entityManager;
        $this->logger = $wildberriesOrdersLogger;
        $this->messageDispatch = $messageDispatch;
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
            $this->logger->warning(
                sprintf('Невозможно найти заказ ( %s id=\'%s\' )', Order::TABLE, $message->getId()),
                [__FILE__.':'.__LINE__]);
            return;
        }

        /**
         * Получаем всю продукцию в заказе
         */

        $products = $this->entityManager
            ->getRepository(OrderProduct::class)
            ->findBy(['event' => $Order->getEvent()]);

        if(!$products)
        {
            $this->logger->warning(
                'Невозможно найти продукцию',
                [
                    'table' => OrderProduct::TABLE,
                    'event' => $Order->getEvent(),
                    __FILE__.':'.__LINE__
                ]
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

            /* Отправляем сообщение в шину для обновления статистики */
            $this->messageDispatch->dispatch(
                message: new UpdateStatisticMessage($Product->getId(), $Product->getEvent()),
                transport: 'async'
            );
        }
    }
}