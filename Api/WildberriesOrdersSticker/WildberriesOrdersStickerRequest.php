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

namespace BaksDev\Wildberries\Orders\Api\WildberriesOrdersSticker;

use App\Kernel;
use BaksDev\Wildberries\Api\Wildberries;
use DateInterval;
use InvalidArgumentException;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Contracts\Cache\ItemInterface;

final class WildberriesOrdersStickerRequest extends Wildberries
{

    /**
     * Идентификатор сборочного задания
     */
    private int|false $order = false;

    /**
     * Ширина этикетки
     */
    private int $width = 40;

    /**
     * Высота этикетки
     */
    private int $height = 30;

    /**
     * Тип этикетки
     * "svg" "zplv" "zplh" "png"
     */
    private $type = 'svg';

    /**
     * Добавить в список идентификатор сборочного задания
     */
    public function forOrderWb(int|string $order): self
    {
        $order = str_replace('W-', '', (string) $order);

        $this->order = (int) $order;

        return $this;
    }


    /**
     * Возвращает файл с этикеткой на указанное сборочное задание
     * Можно запросить этикетку в формате svg, zplv (вертикальный), zplh (горизонтальный), png.
     *
     * @see https://dev.wildberries.ru/openapi/orders-fbs/#tag/Sborochnye-zadaniya/paths/~1api~1v3~1orders~1stickers/post
     *
     * @note По умолчанию стикер в формате SVG 40x30
     *
     */
    public function getOrderSticker(): string|false
    {

        if(false === $this->order)
        {
            throw new InvalidArgumentException(
                'Не указан идентификатор сборочного задания через вызов метода addOrder: ->forOrder(5632423)'
            );
        }

        $cache = new FilesystemAdapter('wildberries-orders');
        $key = md5($this->getProfile().$this->order.$this->type.$this->width.$this->height.self::class);
        //$cache->deleteItem($key);

        $file = $cache->get($key, function(ItemInterface $item): string|false {

            $item->expiresAfter(DateInterval::createFromDateString('1 second'));

            $data = ["orders" => [$this->order]];

            // Member has private visibility but can be accessed via '__call' magic method

            $response = $this->marketplace()->TokenHttpClient()->request(
                'POST',
                '/api/v3/orders/stickers?type='.$this->type.'&width='.$this->width.'&height='.$this->height,
                ['json' => $data],
            );

            $content = $response->toArray(false);

            if($response->getStatusCode() !== 200)
            {
                $this->logger->critical(
                    sprintf('wildberries-orders: Ошибка при получении стикера заказа %s', $this->order),
                    [$content, self::class.':'.__LINE__]
                );

                return false;
            }

            if(false === isset($content['stickers']))
            {
                return false;
            }

            $sticker = current($content['stickers']);

            if(empty($sticker['file']))
            {
                return false;
            }

            $item->expiresAfter(DateInterval::createFromDateString('1 day'));

            return $sticker['file'];
        });

        return $file;
    }


    /**
     * Устанавливает размер стикера 400x300
     */
    public function setSmallSize(): self
    {
        $this->width = 40;
        $this->height = 30;

        return $this;
    }


    /**
     * Устанавливает размер стикера 580x400
     */
    public function setBigSize(): self
    {
        $this->width = 58;
        $this->height = 40;

        return $this;
    }


    /**
     * Тип этикетки svg
     */
    public function setTypeSVG(): self
    {
        $this->type = 'svg';

        return $this;
    }


    /**
     * Тип этикетки zplv (вертикальный)
     */
    public function setTypeZPLV(): self
    {
        $this->type = 'zplv';

        return $this;
    }


    /**
     * Тип этикетки zplh (горизонтальный)
     */
    public function setTypeZPLH(): self
    {
        $this->type = 'zplh';

        return $this;
    }


    /**
     * Тип этикетки png
     */
    public function setTypePNG(): self
    {
        $this->type = 'png';

        return $this;
    }


}