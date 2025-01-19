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

final class WildberriesOrdersStickerDTO
{

    /**
     * Идентификатор сборочного задания
     */
    private int $order;


    /**
     * Идентификатор этикетки (для печати подписи)
     */
    private string $part;


    /**
     * Закодированное значение этикетки
     */
    private string $barcode;


    /**
     * Полное представление этикетки в заданном формате. (кодировка base64)
     */
    private string $sticker;


    public function __construct(array $content)
    {
        $stickers = current($content["stickers"]);

        $this->order = $stickers['orderId'];
        $this->part = $stickers['partA'].' '.$stickers['partB'];
        $this->barcode = $stickers['barcode'];
        $this->sticker = $stickers['file'];
    }

    /**
     * Order
     */
    public function getOrder(): int
    {
        return $this->order;
    }

    /**
     * Part
     */
    public function getPart(): string
    {
        return $this->part;
    }

    /**
     * Barcode
     */
    public function getBarcode(): string
    {
        return $this->barcode;
    }

    /**
     * Sticker
     */
    public function getSticker(): string
    {
        return $this->sticker;
    }


}