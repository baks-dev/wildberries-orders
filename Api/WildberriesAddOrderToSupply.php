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
use DomainException;
use InvalidArgumentException;

final class WildberriesAddOrderToSupply extends Wildberries
{

    /**
     * ID поставки
     *  Example: WB-GI-1234567
     */
    private ?string $supply = null;

    /**
     * ID сборочного задания
     *  Example: 5632423
     */
    private string $order;


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
        $this->order = (string) $order;

        return $this;
    }


    /**
     * Добавляет к поставке заказ и переводит в статус 1 ("В сборке").
     *
     * @see https://openapi.wildberries.ru/marketplace/api/ru/#tag/Postavki/paths/~1api~1v3~1supplies~1{supplyId}~1orders~1{orderId}/patch
     */
    public function add(): self
    {
        if($this->supply === null)
        {
            throw new InvalidArgumentException(
                'Не указан идентификатор поставки через вызов метода withSupply: ->withSupply("WB-GI-1234567")'
            );
        }

        if(empty($this->order))
        {
            throw new InvalidArgumentException(
                'Не указан cписок идентификаторов сборочных заданий через вызов метода addOrder: ->withOrder(5632423)'
            );
        }

        if($this->test)
        {
            return $this;
        }

        $response = $this->TokenHttpClient()->request(
            'PATCH',
            '/api/v3/supplies/'.$this->supply.'/orders/'.$this->order,
        );


        if($response->getStatusCode() !== 204)
        {
            $content = $response->toArray(false);

            throw new DomainException(
                message: $response->getStatusCode().': '.$content['message'] ?? self::class,
                code: $response->getStatusCode()
            );
        }

        return $this;
    }

}