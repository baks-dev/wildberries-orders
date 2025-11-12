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

namespace BaksDev\Wildberries\Orders\Repository\WbOrdersAlarm;

use BaksDev\Core\Doctrine\DBALQueryBuilder;
use BaksDev\Orders\Order\Entity\Event\OrderEvent;
use BaksDev\Orders\Order\Entity\Order;
use BaksDev\Orders\Order\Entity\Products\OrderProduct;
use BaksDev\Orders\Order\Entity\User\Delivery\OrderDelivery;
use BaksDev\Orders\Order\Entity\User\OrderUser;
use BaksDev\Orders\Order\Type\Status\OrderStatus;
use BaksDev\Orders\Order\Type\Status\OrderStatus\Collection\OrderStatusNew;
use BaksDev\Products\Product\Entity\Event\ProductEvent;
use BaksDev\Products\Product\Entity\Offers\ProductOffer;
use BaksDev\Products\Product\Entity\Offers\Variation\Modification\ProductModification;
use BaksDev\Products\Product\Entity\Offers\Variation\ProductVariation;
use BaksDev\Products\Product\Entity\ProductInvariable;
use BaksDev\Products\Product\Type\Event\ProductEventUid;
use BaksDev\Products\Product\Type\Invariable\ProductInvariableUid;
use BaksDev\Products\Product\Type\Offers\Id\ProductOfferUid;
use BaksDev\Products\Product\Type\Offers\Variation\Id\ProductVariationUid;
use BaksDev\Products\Product\Type\Offers\Variation\Modification\Id\ProductModificationUid;
use InvalidArgumentException;

final class WbOrdersAlarmRepository implements WbOrdersAlarmInterface
{
    private ProductEventUid|false $product = false;

    private ProductOfferUid|false $offer = false;

    private ProductVariationUid|false $variation = false;

    private ProductModificationUid|false $modification = false;

    public function __construct(private DBALQueryBuilder $DBALQueryBuilder) {}

    public function forProductEvent(ProductEvent|ProductEventUid|string $product): self
    {

        if(is_string($product))
        {
            $product = new ProductEventUid($product);
        }

        if($product instanceof ProductEvent)
        {
            $product = $product->getId();
        }

        $this->product = $product;

        return $this;
    }

    public function forOffer(ProductOffer|ProductOfferUid|string $offer): self
    {
        if(empty($offer))
        {
            $this->offer = false;
            return $this;
        }

        if(is_string($offer))
        {
            $offer = new ProductOfferUid($offer);
        }

        if($offer instanceof ProductOffer)
        {
            $offer = $offer->getId();
        }

        $this->offer = $offer;

        return $this;
    }

    public function forVariation(ProductVariation|ProductVariationUid|string|null|false $variation): self
    {
        if(empty($variation))
        {
            $this->variation = false;
            return $this;
        }

        if(is_string($variation))
        {
            $variation = new ProductVariationUid($variation);
        }

        if($variation instanceof ProductVariation)
        {
            $variation = $variation->getId();
        }

        $this->variation = $variation;

        return $this;
    }

    public function forModification(ProductModification|ProductModificationUid|string|null|false $modification): self
    {
        if(empty($modification))
        {
            $this->modification = false;
            return $this;
        }

        if(is_string($modification))
        {
            $modification = new ProductModificationUid($modification);
        }

        if($modification instanceof ProductModification)
        {
            $modification = $modification->getId();
        }

        $this->modification = $modification;

        return $this;
    }

    /**
     * Метод возвращает количеств срочных заказов продукта, требующих особое внимание
     */
    public function count(): int
    {
        $dbal = $this->DBALQueryBuilder->createQueryBuilder(self::class);

        if(false === $this->product)
        {
            throw new InvalidArgumentException('Invalid Argument ProductEvent');
        }

        $dbal->select('COUNT(*)');

        $dbal->from(Order::class, 'ord');

        $dbal
            ->join(
                'ord',
                OrderEvent::class,
                'event',
                '
                event.id = orders.event AND
                event.status = :status
            ',
            )
            ->setParameter(
                key: 'status',
                value: OrderStatusNew::class,
                type: OrderStatus::TYPE,
            );

        $dbal
            ->join(
                'ord',
                OrderProduct::class,
                'product',
                '
                    product.event = ord.event AND 
                    product.product = :product AND 
                    product.offer '.(false === ($this->offer instanceof ProductOfferUid) ? ' IS NULL' : ' = :offer').' AND
                    product.variation '.(false === ($this->variation instanceof ProductVariationUid) ? ' IS NULL' : ' = :variation').' AND
                    product.modification '.(false === ($this->modification instanceof ProductModificationUid) ? ' IS NULL' : ' = :modification').'
                ',
            )
            ->setParameter(
                key: 'product',
                value: $this->product,
                type: ProductEventUid::TYPE,
            );

        false === $this->offer ?: $dbal->setParameter(key: 'offer', value: $this->offer, type: ProductOfferUid::TYPE);
        false === $this->variation ?: $dbal->setParameter(key: 'variation', value: $this->variation, type: ProductVariationUid::TYPE);
        false === $this->modification ?: $dbal->setParameter(key: 'modification', value: $this->modification, type: ProductModificationUid::TYPE);

        $dbal->leftJoin('orders',
            OrderUser::class,
            'orders_user',
            'orders_user.event = orders.event',
        );

        $dbal->join('orders_user',
            OrderDelivery::class,
            'orders_delivery',
            '
                orders_delivery.usr = orders_user.id AND
                orders_delivery.delivery_date < NOW()
            ',
        );

        return $dbal->fetchOne() ?: 0;
    }


    /**
     * Метод возвращает количеств срочных заказов продукта, требующих особое внимание
     * Заказы с интервалом 24 часов
     *
     * @deprecated
     */
    public function countOrderAlarmByProduct(ProductEventUid $product): int
    {
        $dbal = $this->DBALQueryBuilder->createQueryBuilder(self::class);

        $dbal
            ->select('COUNT(*)')
            ->from(OrderProduct::class, 'orders_product')
            ->where('orders_product.product = :product')
            ->setParameter('product', $product, ProductEventUid::TYPE);

        $dbal->join('orders_product',
            Order::class,
            'orders',
            'orders.event = orders_product.event',
        );

        $dbal->join('orders',
            OrderEvent::class,
            'orders_event',
            '
                orders_event.id = orders.event AND
                orders_event.status = :status
            ',
        )
            ->setParameter(
                key: 'status',
                value: OrderStatusNew::class,
                type: OrderStatus::TYPE,
            );

        $dbal->leftJoin('orders',
            OrderUser::class,
            'orders_user',
            'orders_user.event = orders.event',
        );

        $dbal->join('orders_user',
            OrderDelivery::class,
            'orders_delivery',
            '
                orders_delivery.usr = orders_user.id AND
                orders_delivery.delivery_date < NOW()
            ',
        );

        return $dbal->fetchOne() ?: 0;
    }
}