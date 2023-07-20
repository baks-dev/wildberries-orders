<?php
/*
 *  Copyright 2023.  Baks.dev <admin@baks.dev>
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

namespace BaksDev\Wildberries\Orders\Forms\WbOrdersFilter;

use BaksDev\Products\Category\Type\Id\ProductCategoryUid;
use BaksDev\Wildberries\Orders\Type\OrderStatus\Status\Collection\WbOrderStatusInterface;
use BaksDev\Wildberries\Orders\Type\WildberriesStatus\Status\Collection\WildberriesStatusInterface;
use Symfony\Component\HttpFoundation\Request;

final class WbOrdersFilterDTO implements WbOrdersFilterInterface
{
    public const status = 'YlbCMGkeVb';
    public const wildberries = 'XTLedgbcTp';
    public const category = 'UnCVUqxNgQ';
    public const offer = 'btqffBoUOn';
    public const variation = 'xaATHFylBJ';
    public const modification = 'KtMLwXzmqI';

    private Request $request;


    /**
     * Статус сборочного задания
     */
    private ?WbOrderStatusInterface $status = null;

    /**
     * Внутренний статус Wildberries
     */
    private ?WildberriesStatusInterface $wildberries = null;

    /**
     * Категория
     */
    private ?ProductCategoryUid $category = null;

    /**
     * Торговое предложение
     */
    private ?string $offer = null;

    /**
     * Множественный вариант торгового предложения
     */
    private ?string $variation = null;

    /**
     * Модификатор множественного варианта торгового предложения
     */
    private ?string $modification = null;


    public function __construct(Request $request)
    {
        $this->request = $request;

    }


    /**
     * Status
     */
    public function getStatus(): ?WbOrderStatusInterface
    {
        return $this->status ?: $this->request->getSession()->get(self::status);

    }

    public function setStatus(?WbOrderStatusInterface $status): void
    {
        if($status === null)
        {
            $this->request->getSession()->remove(self::status);
        }

        $this->status = $status;
    }


    /**
     * Wildberries
     */
    public function getWildberries(): ?WildberriesStatusInterface
    {
        return $this->wildberries ?: $this->request->getSession()->get(self::wildberries);
    }

    public function setWildberries(?WildberriesStatusInterface $wildberries): void
    {
        if($wildberries === null)
        {
            $this->request->getSession()->remove(self::wildberries);
        }

        $this->wildberries = $wildberries;
    }


    /**
     * Категория
     */
    public function setCategory(?ProductCategoryUid $category): void
    {
        if(empty($category))
        {
            $this->request->getSession()->remove(self::category);
        }

        $this->category = $category;
    }


    public function getCategory(): ?ProductCategoryUid
    {
        return $this->category ?: $this->request->getSession()->get(self::category);
    }


    /** Торговое предложение */

    public function getOffer(): ?string
    {
        return $this->offer ?: $this->request->getSession()->get(self::offer);
    }

    public function setOffer(?string $offer): void
    {
        if(empty($offer) || empty($this->category))
        {
            $this->request->getSession()->remove(self::offer);
        }

        $this->offer = $offer;
    }


    /** Множественный вариант торгового предложения */

    public function getVariation(): ?string
    {
        return $this->variation ?: $this->request->getSession()->get(self::variation);
    }

    public function setVariation(?string $variation): void
    {
        if(empty($variation) || empty($this->category) ||  empty($this->offer))
        {
            $this->request->getSession()->remove(self::variation);
        }

        $this->variation = $variation;
    }


    /** Модификатор множественного варианта торгового предложения */

    public function getModification(): ?string
    {
        return $this->modification ?: $this->request->getSession()->get(self::modification);
    }

    public function setModification(?string $modification): void
    {
        if(empty($modification) || empty($this->category) ||  empty($this->offer) || empty($this->variation) )
        {
            $this->request->getSession()->remove(self::modification);
        }

        $this->modification = $modification;
    }

}
