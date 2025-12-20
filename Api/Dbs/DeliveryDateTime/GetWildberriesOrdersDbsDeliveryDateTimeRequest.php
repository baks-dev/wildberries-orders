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

namespace BaksDev\Wildberries\Orders\Api\Dbs\DeliveryDateTime;

use BaksDev\Wildberries\Api\Wildberries;

final class GetWildberriesOrdersDbsDeliveryDateTimeRequest extends Wildberries
{
    /**
     *
     * Дата и время доставки
     * Метод возвращает информацию о выбранных покупателем дате и времени доставки сборочных заданий.
     *
     * @see https://dev.wildberries.ru/openapi/orders-dbs#tag/Sborochnye-zadaniya-DBS/paths/~1api~1v3~1dbs~1orders~1delivery-date/post
     *
     * @return WildberriesOrdersDbsDeliveryDateTimeDTO|false
     */
    public function find(int|string $order): WildberriesOrdersDbsDeliveryDateTimeDTO|false
    {
        $order = str_replace('W-', '', (string) $order);

        $response = $this->marketplace()->TokenHttpClient()->request(
            'POST',
            '/api/v3/dbs/orders/delivery-date',
            ['json' => ['orders' => [(int) $order]]],
        );

        $content = $response->toArray(false);

        if($response->getStatusCode() !== 200)
        {
            $this->logger->critical(
                'wildberries-orders: Ошибка при получении информации о дате и времени доставки',
                [$content, self::class.':'.__LINE__],
            );

            return false;
        }

        if(empty($content['orders']))
        {
            return false;
        }

        $order = current($content['orders']);

        return new WildberriesOrdersDbsDeliveryDateTimeDTO(...$order);

    }
}