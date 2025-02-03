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

final class WildberriesOrdersStatusRequest extends Wildberries
{

    /**
     * Список статусов сборочных заданий
     *
     * "id": 5632423 - Идентификатор сборочного задания
     *
     * "supplierStatus": "new" - Статус сборочного задания продавца (устанавливается продавцом)
     * ( Enum: "new" "confirm" "complete" "cancel")
     *
     * "wbStatus": "waiting" - Статус сборочного задания в системе Wildberries
     * ( Enum: "waiting" "sorted" "sold" "canceled" "canceled_by_client")
     */
    private array $content = [];

    /**
     * Список идентификаторов сборочных заданий
     */
    private array $orders = [];


    /**
     * Добавить в список идентификатор сборочного задания
     */
    public function addOrder(int|string $order): void
    {
        $this->orders[] = $order;
    }


    public function setOrders(array $orders): self
    {
        $this->orders = $orders;
        return $this;
    }


    /** Получить статусы сборочных заданий
     *
     * Возвращает статусы сборочных заданий по переданному списку идентификаторов сборочных заданий.
     *
     * --- supplierStatus - статус сборочного задания, триггером изменения которого является сам продавец.
     *
     * new    Новое сборочное задание
     * confirm    На сборке    (При добавлении сборочного задания к поставке)
     * complete    В доставке    (При переводе в доставку соответствующей поставки)
     * cancel    Отменено продавцом
     *
     * --- wbStatus - статус сборочного задания в системе Wildberries.
     *
     * waiting - сборочное задание в работе
     * sorted - сборочное задание отсортировано
     * sold - сборочное задание получено покупателем
     * canceled - отмена сборочного задания
     * canceled_by_client - отмена сборочного задания покупателем
     *
     *
     * @see https://openapi.wildberries.ru/#tag/Marketplace-Sborochnye-zadaniya/paths/~1api~1v3~1orders~1status/post
     *
     */
    public function request(): self
    {

        if(empty($this->orders))
        {
            throw new InvalidArgumentException(
                'Не указан cписок идентификаторов сборочных заданий через вызов метода addOrder: ->addOrder(5632423)'
            );
        }

        $data = ["orders" => $this->orders];

        $response = $this->TokenHttpClient()->request(
            'POST',
            '/api/v3/orders/status',
            ['json' => $data],
        );

        if($response->getStatusCode() !== 200)
        {
            $content = $response->toArray(false);
            //$this->logger->critical('curl -X POST "' . $url . '" ' . $curlHeader . ' -d "' . $data . '"');
            throw new DomainException(
                message: $response->getStatusCode().': '.$content['message'] ?? self::class,
                code: $response->getStatusCode()
            );
        }
        $content = $response->toArray(false);
        $this->content = $content['orders'];

        return $this;
    }


    /**
     * Список статусов сборочных заданий
     *
     * "id": 5632423 - Идентификатор сборочного задания
     *
     * "supplierStatus": "new" - Статус сборочного задания продавца (устанавливается продавцом)
     * ( Enum: "new" "confirm" "complete" "cancel")
     *
     * "wbStatus": "waiting" - Статус сборочного задания в системе Wildberries
     * ( Enum: "waiting" "sorted" "sold" "canceled" "canceled_by_client")
     */
    public function getContent(): array
    {
        return $this->content;
    }
}