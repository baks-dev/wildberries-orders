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

namespace BaksDev\Wildberries\Orders\Api\Dbs;

use BaksDev\Wildberries\Api\Wildberries;

/** Перевести в доставку */
final class UpdateWbOrderDbsDeliverRequest extends Wildberries
{
    /**
     * Перевести заказ в доставку
     *
     * @see https://dev.wildberries.ru/openapi/orders-dbs/#tag/Sborochnye-zadaniya-DBS/paths/~1api~1v3~1dbs~1orders~1{orderId}~1deliver/patch
     */
    public function update(int|string $order): bool
    {
        if($this->isExecuteEnvironment() === false)
        {
            $this->logger->critical('Запрос может быть выполнен только в PROD окружении', [self::class.':'.__LINE__]);
            return true;
        }

        if(false === $this->isOrders())
        {
            return true;
        }

        $order = str_replace('W-', '', (string) $order);

        $response = $this
            ->marketplace()
            ->TokenHttpClient()
            ->request(
                method: 'PATCH',
                url: sprintf('/api/v3/dbs/orders/%s/deliver', $order),
            );

        if($response->getStatusCode() !== 204)
        {
            if($response->getStatusCode() === 429)
            {
                sleep(1);
            }

            $content = $response->toArray(false);

            $this->logger->critical(
                sprintf(
                    'wildberries-orders: Ошибка %s при перемещении заказа %s в доставку',
                    $response->getStatusCode(), $order,
                ),
                [$content, self::class.':'.__LINE__],
            );

            if($content['code'] === 'StatusMismatch' && $response->getStatusCode() === 409)
            {
                return true;
            }

            return false;
        }

        return true;
    }

}