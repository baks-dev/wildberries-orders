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

namespace BaksDev\Wildberries\Orders\UseCase\Command\Statistic;

use BaksDev\Products\Product\Type\Id\ProductUid;
use BaksDev\Wildberries\Orders\Entity\WbOrdersStatisticsInterface;
use DateTimeImmutable;
use ReflectionProperty;
use Symfony\Component\Validator\Constraints as Assert;

/** @see WbOrdersStatistics */
final class WbOrdersStatisticsDTO implements WbOrdersStatisticsInterface
{

    /**
     * Идентификатор продукта
     */
    #[Assert\NotBlank]
    #[Assert\Uuid]
    private readonly ProductUid $product;

    /**
     * Количество аналогичных заказов
     */
    private int $analog = 0;

    /**
     * Количество срочных аналогичных заказов
     */
    private int $alarm = 0;


    /**
     * Дата самого старого требующий сборки заказа
     */
    private ?DateTimeImmutable $old = null;


    /**
     * Идентификатор продукта
     */
    public function getProduct(): ProductUid
    {
        return $this->product;
    }

    public function setProduct(ProductUid $product): void
    {
        if(false === new ReflectionProperty(self::class, 'product')->isInitialized($this))
        {
            $this->product = $product;
        }
    }


    /**
     * Количество аналогичных заказов
     */
    public function getAnalog(): ?int
    {
        return $this->analog;
    }

    public function setAnalog(?int $analog): void
    {
        $this->analog = $analog;
    }


    /**
     * Количество срочных аналогичных заказов
     */
    public function getAlarm(): int
    {
        return $this->alarm;
    }

    public function setAlarm(int $alarm): void
    {
        $this->alarm = $alarm;
    }


    /**
     * Дата самого старого требующий сборки заказа
     */
    public function getOld(): ?DateTimeImmutable
    {
        return $this->old;
    }

    public function setOld(?DateTimeImmutable $old): void
    {
        $this->old = $old;
    }

}