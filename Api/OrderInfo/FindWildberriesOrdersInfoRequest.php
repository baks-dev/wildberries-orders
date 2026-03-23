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

namespace BaksDev\Wildberries\Orders\Api\OrderInfo;

use BaksDev\Wildberries\Api\Wildberries;
use DateInterval;
use DateTimeImmutable;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Contracts\Cache\ItemInterface;
use function Symfony\Component\String\s;

#[Autoconfigure(public: true)]
final class FindWildberriesOrdersInfoRequest extends Wildberries
{

    private string|false $srid = false;

    public function setSrid(string $srid): self
    {
        $this->srid = $srid;
        return $this;
    }


    /**
     * Метод возвращает информацию о заказах.
     *
     * @see https://dev.wildberries.ru/openapi/orders-dbs#tag/Sborochnye-zadaniya-DBS/paths/~1api~1v3~1dbs~1orders~1new/get
     *
     * //return array|false
     */
    public function findAll(): array|false
    {
        $cache = $this->getCacheInit('wildberries-orders');

        $key = md5($this->getTokenIdentifier().self::class);
        //$cache->deleteItem($key);

        $orders = $cache->get($key, function(ItemInterface $item) {

            $item->expiresAfter(DateInterval::createFromDateString('1 second'));

            $response = $this->statistics()->TokenHttpClient()->request(
                'GET',
                sprintf(
                    '/api/v1/supplier/orders?dateFrom=%s',
                    new DateTimeImmutable()->sub(DateInterval::createFromDateString('1 week'))->format('Y-m-d'),
                ),
            );

            $content = $response->toArray(false);

            if($response->getStatusCode() !== 200)
            {
                $this->logger->critical(
                    sprintf('wildberries-orders: Ошибка при получении о заказах'),
                    [$content, self::class.':'.__LINE__],
                );

                return false;
            }

            if(empty($content))
            {
                return false;
            }

            $item->expiresAfter(DateInterval::createFromDateString('1 minutes'));

            return $content;

        });

        return $orders;
    }
}