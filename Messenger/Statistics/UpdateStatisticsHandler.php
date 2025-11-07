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

namespace BaksDev\Wildberries\Orders\Messenger\Statistics;

use BaksDev\Core\Messenger\MessageDispatchInterface;
use BaksDev\Orders\Order\Messenger\OrderMessage;
use BaksDev\Orders\Order\Repository\OrderProducts\OrderProductResultDTO;
use BaksDev\Orders\Order\Repository\OrderProducts\OrderProductsInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(priority: -100)]
final readonly class UpdateStatisticsHandler
{
    public function __construct(
        private MessageDispatchInterface $messageDispatch,
        private OrderProductsInterface $OrderProducts,
    ) {}

    /**
     * При обновлении заказа - обновляем статистику по продукции в заказе
     */
    public function __invoke(OrderMessage $message): void
    {
        $products = $this->OrderProducts
            ->order($message->getId())
            ->findAllProducts();

        if(false === $products || false === $products->valid())
        {
            return;
        }

        /** @var OrderProductResultDTO $OrderProductResultDTO */
        foreach($products as $OrderProductResultDTO)
        {
            /* Отправляем сообщение в шину для обновления статистики */
            $this->messageDispatch->dispatch(
                message: new UpdateStatisticMessage($OrderProductResultDTO->getProduct(), $OrderProductResultDTO->getProductEvent(), $OrderProductResultDTO->getProductInvariable()),
                transport: 'async'
            );
        }
    }
}