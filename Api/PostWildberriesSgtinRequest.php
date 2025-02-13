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

final class PostWildberriesSgtinRequest extends Wildberries
{
    private string $order;

    private array $sgtin;

    public function forOrder(string $order): self
    {
        $order = str_replace('W-', '', (string) $order);

        $this->order = $order;

        return $this;
    }

    public function sgtin(string $sgtin): self
    {
        /** Обрезаем честный знак до длины */

        // Позиция для третьей группы
        $thirdGroupPos = -1;

        preg_match_all('/\((\d{2})\)/', $sgtin, $matches, PREG_OFFSET_CAPTURE);

        if(count($matches[0]) >= 3)
        {
            $thirdGroupPos = $matches[0][2][1];
        }

        // Если находимся на третьей группе, обрезаем строку
        if($thirdGroupPos !== -1)
        {
            $markingcode = substr($sgtin, 0, $thirdGroupPos);

            // Убираем круглые скобки
            $this->sgtin[] = preg_replace('/\((\d{2})\)/', '$1', $markingcode);
        }

        return $this;
    }

    /**
     * Закрепить за сборочным заданием код маркировки товара
     *
     * @see https://dev.wildberries.ru/openapi/orders-fbs/#tag/Metadannye/paths/~1api~1v3~1orders~1{orderId}~1meta~1sgtin/put
     */
    public function update(): bool
    {
        if($this->isExecuteEnvironment() === false)
        {
            $this->logger->critical('Запрос может быть выполнен только в PROD окружении', [self::class.':'.__LINE__]);
            return true;
        }

        if(empty($this->sgtin))
        {
            $this->logger->warning(
                sprintf('%s: Отсутствуют честные знаки на заказ', $this->order),
                [self::class.':'.__LINE__]
            );

            return true;
        }

        $data['sgtins'] = $this->sgtin;

        $response = $this->marketplace()->TokenHttpClient()->request(
            'PUT',
            sprintf('/api/v3/orders/%s/meta/sgtin', $this->order),
            ['json' => $data]
        );

        if($response->getStatusCode() !== 200)
        {
            $content = $response->toArray(false);

            $this->logger->critical(
                'wildberries-orders: Ошибка при передаче честных заказов',
                [$content, self::class.':'.__LINE__]
            );

            return false;
        }

        return true;
    }
}