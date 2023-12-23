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

namespace BaksDev\Wildberries\Orders\Repository\AllWbOrders;


use BaksDev\Core\Doctrine\DBALQueryBuilder;
use BaksDev\Core\Form\Search\SearchDTO;
use BaksDev\Core\Services\Paginator\PaginatorInterface;
use BaksDev\Orders\Order\Entity\Order;
use BaksDev\Orders\Order\Entity\Products\OrderProduct;
use BaksDev\Orders\Order\Entity\Products\Price\OrderPrice;
use BaksDev\Products\Category\Entity\Offers\ProductCategoryOffers;
use BaksDev\Products\Category\Entity\Offers\Variation\ProductCategoryVariation;
use BaksDev\Products\Category\Type\Id\ProductCategoryUid;
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
use BaksDev\Wildberries\Orders\Entity\Sticker\WbOrdersSticker;
use BaksDev\Wildberries\Orders\Entity\WbOrders;
use BaksDev\Wildberries\Orders\Entity\WbOrdersStatistics;
use BaksDev\Wildberries\Orders\Forms\WbFilterProfile\ProfileFilterInterface;
use BaksDev\Wildberries\Orders\Forms\WbOrdersProductFilter\WbOrdersProductFilterInterface;
use BaksDev\Wildberries\Orders\Forms\WbOrdersStatusFilter\WbOrdersStatusFilterInterface;
use BaksDev\Wildberries\Orders\Type\OrderStatus\WbOrderStatus;
use BaksDev\Wildberries\Orders\Type\WildberriesStatus\WildberriesStatus;
use BaksDev\Wildberries\Products\Entity\Cards\WbProductCardOffer;
use BaksDev\Wildberries\Products\Entity\Cards\WbProductCardVariation;

final class AllWbOrders implements AllWbOrdersInterface
{

    private PaginatorInterface $paginator;

    private DBALQueryBuilder $DBALQueryBuilder;

    public function __construct(
        DBALQueryBuilder $DBALQueryBuilder,
        PaginatorInterface $paginator,
    )
    {

        $this->paginator = $paginator;
        $this->DBALQueryBuilder = $DBALQueryBuilder;
    }

    /** Метод возвращает пагинатор WbOrders */
    public function fetchAllWbOrdersAssociative(
        SearchDTO $search,
        UserProfileUid $profile,
        WbOrdersProductFilterInterface $filter,
        WbOrdersStatusFilterInterface $status,
    ): PaginatorInterface
    {
        $qb = $this->DBALQueryBuilder
            ->createQueryBuilder(self::class)
            ->bindLocal()
        ;

        /**
         * Wildberries заказ
         */

        $qb->select('wb_order.ord as wb_order_id');
        $qb->from(WbOrders::TABLE, 'wb_order');

        $qb->addSelect('wb_order_event.created AS wb_order_date');
        $qb->addSelect('wb_order_event.barcode AS wb_order_barcode');
        $qb->addSelect('wb_order_event.status AS wb_order_status');
        $qb->addSelect('wb_order_event.wildberries AS wb_order_wildberries');

        $qb->join('wb_order',
            WbOrdersEvent::TABLE,
            'wb_order_event',
            'wb_order_event.id = wb_order.event'
        );

        $qb->andWhere('wb_order_event.profile = :profile');
        $qb->setParameter('profile', $profile, UserProfileUid::TYPE);


        if($status->getStatus())
        {
            $qb->andWhere('wb_order_event.status = :status');
            $qb->setParameter('status', $status->getStatus()->getValue(), WbOrderStatus::TYPE);
        }

        if($status->getWildberries())
        {

            $qb->andWhere('wb_order_event.wildberries = :wildberries');
            $qb->setParameter('wildberries', $status->getWildberries()->getValue(), WildberriesStatus::TYPE);
        }


        $qb->addSelect('wb_order_sticker.sticker AS wb_order_sticker');
        $qb->leftJoin('wb_order',
            WbOrdersSticker::TABLE,
            'wb_order_sticker',
            'wb_order_sticker.event = wb_order.event'
        );


        /**
         * Системный заказ
         */

        $qb->leftJoin('wb_order',
            Order::TABLE,
            'ord',
            'ord.id = wb_order.id'
        );


        $qb->addSelect('order_product.product AS wb_product_event');
        $qb->addSelect('order_product.offer AS wb_product_offer');
        $qb->addSelect('order_product.variation AS wb_product_variation');
        $qb->addSelect('order_product.modification AS wb_product_modification');

        $qb->leftJoin('ord',
            OrderProduct::TABLE,
            'order_product',
            'order_product.event = ord.event'
        );

        $qb->addSelect('order_price.price AS order_price');
        $qb->addSelect('order_price.currency AS order_currency');
        $qb->leftJoin('order_product',
            OrderPrice::TABLE,
            'order_price',
            'order_price.product = order_product.id'
        );


        /**
         * Продукт
         */

        $qb->leftJoin('order_product',
            ProductEvent::TABLE,
            'product_event',
            'product_event.id = order_product.product'
        );

        $qb->leftJoin('order_product',
            ProductInfo::TABLE,
            'product_info',
            'product_info.product = product_event.main'
        );

        if($filter->getCategory())
        {

            $qb->join('order_product',
                ProductCategory::TABLE,
                'product_category',
                'product_category.event = product_event.id AND product_category.category = :category AND product_category.root = true'
            );

            $qb->setParameter('category', $filter->getCategory(), ProductCategoryUid::TYPE);
        }


        $qb->addSelect('product_trans.name AS product_name');
        $qb->leftJoin('order_product',
            ProductTrans::TABLE,
            'product_trans',
            'product_trans.event = order_product.product AND product_trans.local = :local'
        );
        


        /*
         * Торговое предложение
         */

        $qb->addSelect('product_offer.value as product_offer_value');
        $qb->addSelect('product_offer.postfix as product_offer_postfix');

        $qb->leftJoin('order_product',
            ProductOffer::TABLE,
            'product_offer',
            'product_offer.id = order_product.offer'
        );


        if(!$search->getQuery() && $filter->getOffer())
        {

            $qb->andWhere('product_offer.value = :offer');
            $qb->setParameter('offer', $filter->getOffer());
        }

        $qb->addSelect('category_offer.reference as product_offer_reference');
        $qb->leftJoin(
            'product_offer',
            ProductCategoryOffers::TABLE,
            'category_offer',
            'category_offer.id = product_offer.category_offer'
        );


        /*
        * Множественный вариант
        */

        $qb->addSelect('product_variation.value as product_variation_value');
        $qb->addSelect('product_variation.postfix as product_variation_postfix');

        $qb->leftJoin('order_product',
            ProductVariation::TABLE,
            'product_variation',
            'product_variation.id = order_product.variation'
        );

        if(!$search->getQuery() && $filter->getVariation())
        {
            $qb->andWhere('product_variation.value = :variation');
            $qb->setParameter('variation', $filter->getVariation());
        }


        /* Тип множественного враианта торгового предложения */
        $qb->addSelect('category_variation.reference as product_variation_reference');
        $qb->leftJoin(
            'product_variation',
            ProductCategoryVariation::TABLE,
            'category_variation',
            'category_variation.id = product_variation.category_variation'
        );


        /** Артикул продукта */

        $qb->addSelect("
					CASE
					   WHEN product_variation.article IS NOT NULL THEN product_variation.article
					   WHEN product_offer.article IS NOT NULL THEN product_offer.article
					   WHEN product_info.article IS NOT NULL THEN product_info.article
					   ELSE NULL
					END AS product_article
				"
        );


        /** Фото продукта */

        $qb->leftJoin(
            'order_product',
            ProductPhoto::TABLE,
            'product_photo',
            'product_photo.event = order_product.product AND product_photo.root = true'
        );

        $qb->leftJoin(
            'product_offer',
            ProductOfferImage::TABLE,
            'product_offer_images',
            'product_offer_images.offer = order_product.offer AND product_offer_images.root = true'
        );

        $qb->leftJoin(
            'product_offer',
            ProductVariationImage::TABLE,
            'product_variation_image',
            'product_variation_image.variation = order_product.variation AND product_variation_image.root = true'
        );


        $qb->addSelect("
			CASE
			   WHEN product_variation_image.name IS NOT NULL THEN
					CONCAT ( '/upload/".ProductVariationImage::TABLE."' , '/', product_variation_image.name)
			   WHEN product_offer_images.name IS NOT NULL THEN
					CONCAT ( '/upload/".ProductOfferImage::TABLE."' , '/', product_offer_images.name)
			   WHEN product_photo.name IS NOT NULL THEN
					CONCAT ( '/upload/".ProductPhoto::TABLE."' , '/', product_photo.name)
			   ELSE NULL
			END AS product_image
		"
        );

        /** Флаг загрузки файла CDN */
        $qb->addSelect("
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
        $qb->addSelect("
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


        $qb->leftJoin('wb_order',
            WbProductCardVariation::TABLE,
            'wb_card_variation',
            'wb_card_variation.barcode = wb_order_event.barcode'
        );

        $qb->addSelect('wb_card_offer.nomenclature AS wb_order_nomenclature');

        $qb->leftJoin('wb_card_variation',
            WbProductCardOffer::TABLE,
            'wb_card_offer',
            'wb_card_offer.card = wb_card_variation.card AND wb_card_offer.offer =  product_offer.const'
        );


        $qb->addSelect('wb_order_stats.analog AS wb_order_analog');
        $qb->addSelect('wb_order_stats.alarm AS wb_order_alarm');
        $qb->addSelect('wb_order_stats.old AS wb_order_old');

        $qb->leftJoin('product_event',
            WbOrdersStatistics::TABLE,
            'wb_order_stats',
            'wb_order_stats.product = product_event.main'
        );


        /* Поиск */
        if($search->getQuery())
        {
            $qb
                ->createSearchQueryBuilder($search)
                ->addSearchEqualUid('wb_order.id')
                ->addSearchEqualUid('order_product.product')
                ->addSearchEqualUid('order_product.offer')
                ->addSearchEqualUid('order_product.variation')
                ->addSearchEqual('wb_order.ord')
                ->addSearchLike('product_trans.name')
                ->addSearchLike('wb_order_event.barcode')
                ->addSearchLike('product_variation.article')
                ->addSearchLike('product_offer.article')
                ->addSearchLike('product_info.article');
        }

        $qb->orderBy('wb_order_event.created', 'ASC');

        return $this->paginator->fetchAllAssociative($qb);

    }
}
