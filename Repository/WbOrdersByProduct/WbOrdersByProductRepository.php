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

namespace BaksDev\Wildberries\Orders\Repository\WbOrdersByProduct;

use BaksDev\Core\Doctrine\ORMQueryBuilder;
use BaksDev\Orders\Order\Entity\Order;
use BaksDev\Orders\Order\Entity\Products\OrderProduct;
use BaksDev\Products\Product\Type\Event\ProductEventUid;
use BaksDev\Products\Product\Type\Offers\Id\ProductOfferUid;
use BaksDev\Products\Product\Type\Offers\Variation\Id\ProductVariationUid;
use BaksDev\Products\Product\Type\Offers\Variation\Modification\Id\ProductModificationUid;
use BaksDev\Wildberries\Orders\Entity\Event\WbOrdersEvent;
use BaksDev\Wildberries\Orders\Entity\WbOrders;
use BaksDev\Wildberries\Orders\Type\OrderStatus\Status\WbOrderStatusNew;

final class WbOrdersByProductRepository implements WbOrdersByProductInterface
{
    private ORMQueryBuilder $ORMQueryBuilder;

    public function __construct(ORMQueryBuilder $ORMQueryBuilder)
    {
        $this->ORMQueryBuilder = $ORMQueryBuilder;
    }


    /**
     * Метод получает ограниченное количество заказов Wildberries указанной продукции
     */
    public function findOldWbOrders(
        int $limit,
        ProductEventUid $product,
        ?ProductOfferUid $offer = null,
        ?ProductVariationUid $variation = null,
        ?ProductModificationUid $modification = null,
    )
    {
        $qb = $this->ORMQueryBuilder->createQueryBuilder(self::class);

        // $select = sprintf('new %s(field.id)', Class::class);


        $qb->from(OrderProduct::class, 'product');

        $qb->where('product.product = :product')
            ->setParameter('product', $product, ProductEventUid::TYPE);

        if($offer)
        {
            $qb->andWhere('product.offer = :offer')
                ->setParameter('offer', $offer, ProductOfferUid::TYPE);
        }
        else
        {
            $qb->andWhere('product.offer IS NULL');
        }


        if($variation)
        {
            $qb->andWhere('product.variation = :variation')
                ->setParameter('variation', $variation, ProductVariationUid::TYPE);
        }
        else
        {
            $qb->andWhere('product.variation IS NULL');
        }


        if($modification)
        {
            $qb->andWhere('product.modification = :modification')
                ->setParameter('modification', $modification, ProductModificationUid::TYPE);
        }
        else
        {
            $qb->andWhere('product.modification IS NULL');
        }


        $qb
            ->select('ord')
            ->join(
                Order::class,
                'ord',
                'WITH',
                'ord.event = product.event'
            );

        $qb->join(
            WbOrders::class,
            'wb_ord',
            'WITH',
            'wb_ord.id = ord.id'
        );

        $qb
            ->join(
                WbOrdersEvent::class,
                'wb_ord_event',
                'WITH',
                'wb_ord_event.id = wb_ord.event AND wb_ord_event.status = :status'
            )
            ->setParameter('status', WbOrderStatusNew::STATUS);


        $qb->orderBy('wb_ord_event.created');

        $qb->setMaxResults($limit);

        return $qb->getQuery()->getResult();
    }
}