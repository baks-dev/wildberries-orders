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

final class FindAllWildberriesOrdersStatusRequest extends Wildberries
{
    /**
     * Список идентификаторов сборочных заданий
     */
    private array $orders = [];


    /**
     * Добавить в список идентификатор сборочного задания
     */
    public function addOrder(int|string $order): self
    {
        $order = (int) str_replace('W-', '', (string) $order);

        $this->orders[$order] = $order;

        return $this;
    }

    /**
     * Получить статусы сборочных заданий
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
     * @see https://dev.wildberries.ru/openapi/orders-fbs/#tag/Sborochnye-zadaniya/paths/~1api~1v3~1orders~1status/post
     *
     */
    private function request(): array|false
    {
        if(empty($this->orders))
        {
            return false;
        }


        $chunk = array_chunk($this->orders, 1000);

        $result = [];
        $this->orders = [];

        foreach($chunk as $orders)
        {
            $data = ["orders" => $orders];

            $response = $this->marketplace()->TokenHttpClient()->request(
                'POST',
                '/api/v3/orders/status',
                ['json' => $data],
            );

            $content = $response->toArray(false);

            if($response->getStatusCode() !== 200)
            {
                $this->logger->critical(
                    sprintf('wildberries-orders: Ошибка %s при получении статусов сборочных заданий', $response->getStatusCode()),
                    [
                        'orders' => $orders,
                        'content' => $content,
                        'profile' => (string) $this->profile,
                        self::class.':'.__LINE__
                    ]
                );

                continue;
            }

            $result = array_merge($content['orders'], $result);

        }


        return $result;
    }


    public function findOrderCancel(): array|false
    {
        $request = $this->request();

        if(empty($request))
        {
            return false;
        }

        $orders = array_filter($request, static function($item) {
            return in_array($item['wbStatus'], ['declined_by_client', 'canceled']);
        });

        return empty($orders) ? false :

            array_map(static function($item) {
                return 'W-'.$item['id'];
            }, $orders);
    }

    /**
     * Метод получает статус сборочного задания со статусом Sold «Cборочное задание получено покупателем»
     */
    public function findOrderCompleted(): array|false
    {
        $request = $this->request();

        if(empty($request))
        {
            return false;
        }

        $orders = array_filter($request, static function($item) {
            return $item['wbStatus'] === 'sold';
        });

        return empty($orders) ? false :

            array_map(static function($item) {
                return 'W-'.$item['id'];
            }, $orders);
    }

}