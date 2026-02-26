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

namespace BaksDev\Wildberries\Orders\Api;

use BaksDev\Wildberries\Api\Wildberries;
use InvalidArgumentException;

final class PostWildberriesAddOrderToSupplyRequest extends Wildberries
{

    /**
     * ID поставки
     *  Example: WB-GI-1234567
     */
    private string|false $supply = false;

    /**
     * ID сборочного задания
     *  Example: 5632423
     */
    private string|false $order = false;


    /**
     * ID поставки
     */
    public function withSupply(string $supply): self
    {
        $this->supply = $supply;

        return $this;
    }


    /**
     * ID сборочного задания
     */
    public function withOrder(string|int $order): self
    {
        $order = str_replace('W-', '', (string) $order);

        $this->order = $order;

        return $this;
    }


    /**
     * Добавляет к поставке заказ и переводит в статус 1 ("В сборке").
     *
     * @see https://dev.wildberries.ru/openapi/orders-fbs#tag/Postavki-FBS/paths/~1api~1marketplace~1v3~1supplies~1%7BsupplyId%7D~1orders/patch
     */
    public function add(): bool
    {

        if($this->isExecuteEnvironment() === false)
        {
            $this->logger->critical('Запрос может быть выполнен только в PROD окружении', [self::class.':'.__LINE__]);
            return true;
        }

        if(false === $this->supply)
        {
            throw new InvalidArgumentException(
                'Не указан идентификатор поставки через вызов метода withSupply: ->withSupply("WB-GI-1234567")',
            );
        }

        if(false === $this->order)
        {
            throw new InvalidArgumentException(
                'Не указан cписок идентификаторов сборочных заданий через вызов метода addOrder: ->withOrder(5632423)',
            );
        }

        $response = $this->marketplace()->TokenHttpClient()->request(
            'PATCH',
            sprintf('/api/marketplace/v3/supplies/%s/orders', $this->supply),
            ['json' => ['orders' => [(int) $this->order]]],
        );

        if($response->getStatusCode() !== 204)
        {
            $this->logger->critical(
                sprintf('wildberries-orders: Ошибка при добавлении заказа %s к поставке %s', $this->order, $this->supply),
                [$response->toArray(false), self::class.':'.__LINE__],
            );

            return false;
        }

        usleep(300000);

        // Сбрасываем идентификатор сборочного задания
        $this->order = false;

        return true;
    }

}