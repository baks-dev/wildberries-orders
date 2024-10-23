<?php
/*
 *  Copyright 2024.  Baks.dev <admin@baks.dev>
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

namespace BaksDev\Wildberries\Orders\Forms\WbOrdersProductFilter;

use BaksDev\Products\Category\Type\Id\CategoryProductUid;
use Symfony\Component\HttpFoundation\Request;

final class WbOrdersProductFilterDTO implements WbOrdersProductFilterInterface
{

    public const category = 'UnCVUqxNgQ';
    public const offer = 'btqffBoUOn';
    public const variation = 'xaATHFylBJ';
    public const modification = 'KtMLwXzmqI';

    private Request $request;

    /**
     * Категория
     */
    private ?CategoryProductUid $category = null;

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
     * Категория
     */
    public function setCategory(CategoryProductUid|string|null $category): void
    {
        if(empty($category))
        {
            $this->request->getSession()->remove(self::category);
        }

        if(is_string($category))
        {
            $category = new CategoryProductUid($category);
        }

        $this->category = $category;
    }


    public function getCategory(bool $readonly = false): ?CategoryProductUid
    {
        if($readonly)
        {
            return $this->category;
        }

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
        if(empty($variation) || empty($this->category) || empty($this->offer))
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
        if(empty($modification) || empty($this->category) || empty($this->offer) || empty($this->variation))
        {
            $this->request->getSession()->remove(self::modification);
        }

        $this->modification = $modification;
    }

}
