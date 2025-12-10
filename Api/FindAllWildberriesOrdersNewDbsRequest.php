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

namespace BaksDev\Wildberries\Orders\Api;

use BaksDev\Wildberries\Api\Wildberries;
use BaksDev\Wildberries\Orders\UseCase\New\WildberriesOrderDTO;
use Generator;

final class FindAllWildberriesOrdersNewDbsRequest extends Wildberries
{
    /**
     * Получить список новых сборочных заданий.
     * Возвращает список всех новых сборочных заданий у продавца на данный момент.
     *
     * @see https://dev.wildberries.ru/openapi/orders-dbs#tag/Sborochnye-zadaniya-DBS/paths/~1api~1v3~1dbs~1orders~1new/get
     *
     * @return Generator<WildberriesOrderDTO>|false
     */
    public function findAll(): Generator|false
    {
        if(false === $this->isStock())
        {
            return false;
        }

        $response = $this->marketplace()->TokenHttpClient()->request(
            'GET',
            '/api/v3/dbs/orders/new',
        );

        $content = $response->toArray(false);

        if($response->getStatusCode() !== 200)
        {
            $this->logger->critical(
                'wildberries-orders: Ошибка при получении новых заказов',
                [$content, self::class.':'.__LINE__],
            );

            return false;
        }

        if(empty($content['orders']))
        {
            return false;
        }

        foreach($content['orders'] as $order)
        {
            /** Пропускаем, если заказ на другой склад */
            if((string) $order['warehouseId'] !== $this->getWarehouse())
            {
                continue;
            }

            /** Сразу отправляем заказ на сборку для получения информации о клиенте */
            if($this->isExecuteEnvironment() !== false)
            {
                $response = $this
                    ->marketplace()
                    ->TokenHttpClient()
                    ->request(
                        method: 'PATCH',
                        url: sprintf('/api/v3/dbs/orders/%s/confirm', $order),
                    );

                /** Пробуем добавить заказ позже */
                if($response->getStatusCode() !== 204)
                {
                    continue;
                }
            }

            yield new WildberriesOrderDTO($order, $this->getProfile(), $this->getTokenIdentifier());
        }
    }
}