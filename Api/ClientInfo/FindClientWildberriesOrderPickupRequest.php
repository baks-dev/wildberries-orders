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

namespace BaksDev\Wildberries\Orders\Api\ClientInfo;

use BaksDev\Wildberries\Api\Wildberries;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;

/** Перевести на сборку */
#[Autoconfigure(public: true)]
final class FindClientWildberriesOrderPickupRequest extends Wildberries
{
    /**
     * Информация о покупателе
     * Метод возвращает информацию о покупателе по ID сборочного задания.
     *
     * @see https://dev.wildberries.ru/docs/openapi/in-store-pickup#tag/Sborochnye-zadaniya-Samovyvoz/paths/~1api~1v3~1click-collect~1orders~1client/post
     */
    public function find(int|string $order): ClientWildberriesOrdersDTO|false
    {
        $order = str_replace('W-', '', (string) $order);

        $response = $this
            ->marketplace()
            ->TokenHttpClient()
            ->request(
                method: 'POST',
                url: '/api/v3/click-collect/orders/client',
                options: ['json' => ['orders' => [(int) $order]]],
            );

        $content = $response->toArray(false);

        if($response->getStatusCode() !== 200)
        {
            $this->logger->critical(
                'wildberries-orders: Ошибка при получении информации о клиенте',
                [$content, self::class.':'.__LINE__],
            );

            return false;
        }

        if(empty($content['orders']))
        {
            $this->logger->critical(
                'wildberries-orders: Ошибка при получении информации о клиенте',
                [$content, self::class.':'.__LINE__],
            );

            return false;
        }

        return new ClientWildberriesOrdersDTO(current($content['orders']));
    }

}