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

namespace BaksDev\Wildberries\Orders\Repository\AllWbOrdersGroup;


use BaksDev\Core\Doctrine\DBALQueryBuilder;
use BaksDev\Core\Form\Search\SearchDTO;
use BaksDev\Core\Services\Paginator\PaginatorInterface;
use BaksDev\Orders\Order\Entity\Order;
use BaksDev\Orders\Order\Entity\Products\OrderProduct;
use BaksDev\Orders\Order\Entity\Products\Price\OrderPrice;
use BaksDev\Products\Category\Entity\Offers\CategoryProductOffers;
use BaksDev\Products\Category\Entity\Offers\Variation\CategoryProductVariation;
use BaksDev\Products\Category\Type\Id\CategoryProductUid;
use BaksDev\Products\Product\Entity\Category\ProductCategory;
use BaksDev\Products\Product\Entity\Event\ProductEvent;
use BaksDev\Products\Product\Entity\Info\ProductInfo;
use BaksDev\Products\Product\Entity\Offers\Image\ProductOfferImage;
use BaksDev\Products\Product\Entity\Offers\ProductOffer;
use BaksDev\Products\Product\Entity\Offers\Variation\Image\ProductVariationImage;
use BaksDev\Products\Product\Entity\Offers\Variation\ProductVariation;
use BaksDev\Products\Product\Entity\Photo\ProductPhoto;
use BaksDev\Products\Product\Entity\Trans\ProductTrans;
use BaksDev\Users\Profile\UserProfile\Type\Id\UserProfileUid;
use BaksDev\Wildberries\Orders\Entity\Event\WbOrdersEvent;
use BaksDev\Wildberries\Orders\Entity\WbOrders;
use BaksDev\Wildberries\Orders\Entity\WbOrdersStatistics;
use BaksDev\Wildberries\Orders\Forms\WbFilterProfile\ProfileFilterInterface;
use BaksDev\Wildberries\Orders\Forms\WbOrdersProductFilter\WbOrdersProductFilterInterface;
use BaksDev\Wildberries\Orders\Type\OrderStatus\Status\WbOrderStatusNew;
use BaksDev\Wildberries\Orders\Type\OrderStatus\WbOrderStatus;
use BaksDev\Wildberries\Products\Entity\Cards\WbProductCardOffer;
use BaksDev\Wildberries\Products\Entity\Cards\WbProductCardVariation;

final class AllWbOrdersGroupRepository implements AllWbOrdersGroupInterface
{

    private PaginatorInterface $paginator;

    private DBALQueryBuilder $DBALQueryBuilder;

    public function __construct(
        DBALQueryBuilder $DBALQueryBuilder,
        PaginatorInterface $paginator
    )
    {

        $this->paginator = $paginator;
        $this->DBALQueryBuilder = $DBALQueryBuilder;
    }

    /**
     * Метод возвращает сгруппированные заказы по артикулам
     */
    public function fetchAllWbOrdersGroupAssociative(
        SearchDTO $search,
        ProfileFilterInterface $profile,
        WbOrdersProductFilterInterface $filter
    ): PaginatorInterface
    {
        $dbal = $this->DBALQueryBuilder->createQueryBuilder(self::class);

        /**
         * Wildberries заказ
         */

        //$dbal->select('wb_order.ord as wb_order_id');
        $dbal->from(WbOrders::class, 'wb_order');

        $dbal->addSelect('MIN(wb_order_event.created) AS wb_order_date');
        $dbal->addSelect('wb_order_event.barcode AS wb_order_barcode');
        $dbal->addSelect('wb_order_event.status AS wb_order_status');
        $dbal->addSelect('wb_order_event.wildberries AS wb_order_wildberries');

        $dbal
            ->join('wb_order',
                WbOrdersEvent::class,
                'wb_order_event',
                'wb_order_event.id = wb_order.event'
            );

        $dbal->andWhere('wb_order_event.profile = :profile');
        $dbal->setParameter('profile', $profile->getProfile(), UserProfileUid::TYPE);

        $dbal->andWhere('wb_order_event.status = :status');
        $dbal->setParameter('status', WbOrderStatusNew::STATUS, WbOrderStatus::TYPE);


        /**
         * Системный заказ
         */

        $dbal->leftJoin('wb_order',
            Order::class,
            'ord',
            'ord.id = wb_order.id'
        );


        $dbal->addSelect('order_product.product AS wb_product_event');
        $dbal->addSelect('order_product.offer AS wb_product_offer');
        $dbal->addSelect('order_product.variation AS wb_product_variation');
        $dbal->addSelect('order_product.modification AS wb_product_modification');

        $dbal->leftJoin('ord',
            OrderProduct::class,
            'order_product',
            'order_product.event = ord.event'
        );

        //$dbal->addSelect('order_price.price AS order_price');
        $dbal->addSelect('SUM(order_price.total) AS order_total');
        $dbal->leftJoin('order_product',
            OrderPrice::class,
            'order_price',
            'order_price.product = order_product.id'
        );


        /**
         * Продукт
         */

        $dbal->leftJoin('order_product',
            ProductEvent::class,
            'product_event',
            'product_event.id = order_product.product'
        );

        $dbal->leftJoin('order_product',
            ProductInfo::class,
            'product_info',
            'product_info.product = product_event.main'
        );

        if($filter->getCategory())
        {

            $dbal->join('order_product',
                ProductCategory::class,
                'product_category',
                'product_category.event = product_event.id AND product_category.category = :category AND product_category.root = true'
            );

            $dbal->setParameter('category', $filter->getCategory(), CategoryProductUid::TYPE);
        }


        $dbal->addSelect('product_trans.name AS product_name');
        $dbal->leftJoin('order_product',
            ProductTrans::class,
            'product_trans',
            'product_trans.event = order_product.product AND product_trans.local = :local'
        );

        $dbal->bindLocal();


        /*
         * Торговое предложение
         */

        $dbal->addSelect('product_offer.value as product_offer_value');
        $dbal->addSelect('product_offer.postfix as product_offer_postfix');

        $dbal->leftJoin('order_product',
            ProductOffer::class,
            'product_offer',
            'product_offer.id = order_product.offer'
        );


        if(!$search->getQuery() && $filter->getOffer())
        {

            $dbal->andWhere('product_offer.value = :offer');
            $dbal->setParameter('offer', $filter->getOffer());
        }

        $dbal->addSelect('category_offer.reference as product_offer_reference');
        $dbal->leftJoin(
            'product_offer',
            CategoryProductOffers::class,
            'category_offer',
            'category_offer.id = product_offer.category_offer'
        );


        /*
        * Множественный вариант
        */

        $dbal->addSelect('product_variation.value as product_variation_value');
        $dbal->addSelect('product_variation.postfix as product_variation_postfix');

        $dbal->leftJoin('order_product',
            ProductVariation::class,
            'product_variation',
            'product_variation.id = order_product.variation'
        );

        if(!$search->getQuery() && $filter->getVariation())
        {
            $dbal->andWhere('product_variation.value = :variation');
            $dbal->setParameter('variation', $filter->getVariation());
        }


        /* Тип множественного вараианта торгового предложения */
        $dbal->addSelect('category_variation.reference as product_variation_reference');
        $dbal->leftJoin(
            'product_variation',
            CategoryProductVariation::class,
            'category_variation',
            'category_variation.id = product_variation.category_variation'
        );


        /** Артикул продукта */

        $dbal->addSelect("
					CASE
					   WHEN product_variation.article IS NOT NULL THEN product_variation.article
					   WHEN product_offer.article IS NOT NULL THEN product_offer.article
					   WHEN product_info.article IS NOT NULL THEN product_info.article
					   ELSE NULL
					END AS product_article
				"
        );


        /** Фото продукта */

        $dbal->leftJoin(
            'order_product',
            ProductPhoto::class,
            'product_photo',
            'product_photo.event = order_product.product AND product_photo.root = true'
        );

        $dbal->leftJoin(
            'product_offer',
            ProductOfferImage::class,
            'product_offer_images',
            'product_offer_images.offer = order_product.offer AND product_offer_images.root = true'
        );

        $dbal->leftJoin(
            'product_offer',
            ProductVariationImage::class,
            'product_variation_image',
            'product_variation_image.variation = order_product.variation AND product_variation_image.root = true'
        );


        $dbal->addSelect("
			CASE
			   WHEN product_variation_image.name IS NOT NULL THEN
					CONCAT ( '/upload/".ProductVariationImage::class."' , '/', product_variation_image.name)
			   WHEN product_offer_images.name IS NOT NULL THEN
					CONCAT ( '/upload/".ProductOfferImage::class."' , '/', product_offer_images.name)
			   WHEN product_photo.name IS NOT NULL THEN
					CONCAT ( '/upload/".ProductPhoto::class."' , '/', product_photo.name)
			   ELSE NULL
			END AS product_image
		"
        );

        /** Флаг загрузки файла CDN */
        $dbal->addSelect("
			CASE
			   WHEN product_variation_image.name IS NOT NULL THEN
					product_variation_image.ext
			   WHEN product_offer_images.name IS NOT NULL THEN
					product_offer_images.ext
			   WHEN product_photo.name IS NOT NULL THEN
					product_photo.ext
			   ELSE NULL
			END AS product_image_ext
		");

        /** Флаг загрузки файла CDN */
        $dbal->addSelect("
			CASE
			   WHEN product_variation_image.name IS NOT NULL THEN
					product_variation_image.cdn
			   WHEN product_offer_images.name IS NOT NULL THEN
					product_offer_images.cdn
			   WHEN product_photo.name IS NOT NULL THEN
					product_photo.cdn
			   ELSE NULL
			END AS product_image_cdn
		");


        /* Карточка Wildberries */


        $dbal->leftJoin('wb_order',
            WbProductCardVariation::class,
            'wb_card_variation',
            'wb_card_variation.barcode = wb_order_event.barcode'
        );

        $dbal->addSelect('wb_card_offer.nomenclature AS wb_order_nomenclature');

        $dbal->leftJoin('wb_card_variation',
            WbProductCardOffer::class,
            'wb_card_offer',
            'wb_card_offer.card = wb_card_variation.card AND wb_card_offer.offer =  product_offer.const'
        );


        $dbal->addSelect('wb_order_stats.analog AS wb_order_analog');
        $dbal->addSelect('wb_order_stats.alarm AS wb_order_alarm');
        $dbal->addSelect('wb_order_stats.old AS wb_order_old');

        $dbal->leftJoin('product_event',
            WbOrdersStatistics::class,
            'wb_order_stats',
            'wb_order_stats.product = product_event.main'
        );


        /* Поиск */
        if($search->getQuery())
        {
            $dbal
                ->createSearchQueryBuilder($search)
                ->addSearchEqualUid('wb_order.id')
                ->addSearchEqualUid('order_product.product')
                ->addSearchEqualUid('order_product.offer')
                ->addSearchEqualUid('order_product.variation')
                ->addSearchLike('product_trans.name')
                ->addSearchLike('wb_order_event.barcode')
                ->addSearchLike('product_variation.article')
                ->addSearchLike('product_offer.article')
                ->addSearchLike('product_info.article');
        }

        $dbal->orderBy('wb_order_date', 'ASC');

        $dbal->allGroupByExclude();

        return $this->paginator->fetchAllAssociative($dbal);

    }
}
