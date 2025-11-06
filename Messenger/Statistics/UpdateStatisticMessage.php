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

namespace BaksDev\Wildberries\Orders\Messenger\Statistics;

use BaksDev\Orders\Order\Type\Id\OrderUid;
use BaksDev\Products\Product\Type\Event\ProductEventUid;
use BaksDev\Products\Product\Type\Id\ProductUid;
use BaksDev\Products\Product\Type\Invariable\ProductInvariableUid;


final readonly class UpdateStatisticMessage
{
    /**
     * Идентификатор
     */
    private string $id;

    /**
     * Идентификатор события
     */
    private string $event;

    /**
     * Идентификатор invariable
     */
    private string $invariable;

    public function __construct(ProductUid $id, ProductEventUid $event, ProductInvariableUid $invariable)
    {
        $this->id = (string) $id;
        $this->event = (string) $event;
        $this->invariable = (string) $invariable;
    }


    /**
     * Идентификатор
     */
    public function getProduct(): ProductUid
    {
        return new ProductUid($this->id);
    }


    /**
     * Идентификатор события
     */
    public function getProductEvent(): ProductEventUid
    {
        return new ProductEventUid($this->event);
    }

    /**
     * Идентификатор invariable
     */
    public function getProductInvariable(): ProductInvariableUid
    {
        return new ProductInvariableUid($this->invariable);
    }

}