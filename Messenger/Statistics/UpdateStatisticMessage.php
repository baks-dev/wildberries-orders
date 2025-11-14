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
use BaksDev\Products\Product\Type\Offers\Id\ProductOfferUid;
use BaksDev\Products\Product\Type\Offers\Variation\Id\ProductVariationUid;
use BaksDev\Products\Product\Type\Offers\Variation\Modification\Id\ProductModificationUid;


final readonly class UpdateStatisticMessage
{
    /**
     * Идентификатор события продукта
     */
    private string $event;

    private string|false $offer;

    private string|false $variation;

    private string|false $modification;


    /**
     * Идентификатор invariable
     */
    private string $invariable;


    public function __construct(
        ProductInvariableUid $invariable,
        ProductEventUid $event,
        ProductOfferUid|null|false $offer,
        ProductVariationUid|null|false $variation,
        ProductModificationUid|null|false $modification,
    )
    {
        $this->event = (string) $event;
        $this->invariable = (string) $invariable;

        $this->offer = $offer instanceof ProductOfferUid ? (string) $offer : false;
        $this->variation = $variation instanceof ProductVariationUid ? (string) $variation : false;
        $this->modification = $modification instanceof ProductModificationUid ? (string) $modification : false;

    }

    /**
     * Идентификатор события
     */
    public function getProductEvent(): ProductEventUid
    {
        return new ProductEventUid($this->event);
    }

    public function getProductOffer(): ProductOfferUid|false
    {
        return $this->offer ? new ProductOfferUid($this->offer) : false;
    }

    public function getProductVariation(): ProductVariationUid|false
    {
        return $this->variation ? new ProductVariationUid($this->variation) : false;
    }

    public function getProductModification(): ProductModificationUid|false
    {
        return $this->modification ? new ProductModificationUid($this->modification) : false;
    }

    /**
     * Идентификатор invariable
     */
    public function getProductInvariable(): ProductInvariableUid
    {
        return new ProductInvariableUid($this->invariable);
    }


}