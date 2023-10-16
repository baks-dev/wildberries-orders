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

namespace BaksDev\Wildberries\Orders\Controller\Admin;


use BaksDev\Centrifugo\Server\Publish\CentrifugoPublishInterface;
use BaksDev\Core\Controller\AbstractController;
use BaksDev\Core\Listeners\Event\Security\RoleSecurity;
use BaksDev\Core\Messenger\MessageDispatchInterface;
use BaksDev\Orders\Order\Repository\OrderDetail\OrderDetailInterface;
use BaksDev\Orders\Order\Repository\OrderProducts\OrderProductsInterface;
use BaksDev\Products\Product\Repository\ProductDetail\ProductDetailByUidInterface;
use BaksDev\Products\Product\Type\Event\ProductEventUid;
use BaksDev\Products\Product\Type\Id\ProductUid;
use BaksDev\Products\Product\Type\Offers\Id\ProductOfferUid;
use BaksDev\Products\Product\Type\Offers\Variation\Id\ProductVariationUid;
use BaksDev\Wildberries\Orders\Entity\Sticker\WbOrdersSticker;
use BaksDev\Wildberries\Package\Entity\Package\WbPackage;
use BaksDev\Wildberries\Package\Entity\Supply\Wildberries\WbSupplyWildberries;
use BaksDev\Wildberries\Package\Repository\Package\OrderByPackage\OrderByPackageInterface;
use BaksDev\Wildberries\Package\Type\Package\Event\WbPackageEventUid;
use BaksDev\Wildberries\Package\UseCase\Package\Print\PrintWbPackageDTO;
use BaksDev\Wildberries\Products\Repository\Barcode\WbBarcodeProperty\WbBarcodePropertyByProductEventInterface;
use BaksDev\Wildberries\Products\Repository\Barcode\WbBarcodeSettings\WbBarcodeSettingsInterface;
use chillerlan\QRCode\QRCode;
use Picqer\Barcode\BarcodeGeneratorSVG;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Exception\RouteNotFoundException;

#[AsController]
#[RoleSecurity('ROLE_WB_PACKAGE_PRINT')]
final class PrintController extends AbstractController
{
    /**
     * Печать штрихкодов и QR заказа
     */
    #[Route('/admin/wb/order/print/{id}', name: 'admin.print', methods: ['GET', 'POST'])]
    public function printer(
        #[MapEntity] WbOrdersSticker $WbOrdersSticker,
        OrderProductsInterface $orderProducts,
//        CentrifugoPublishInterface $CentrifugoPublish,
//        OrderByPackageInterface $orderByPackage,
        WbBarcodeSettingsInterface $barcodeSettings,
        WbBarcodePropertyByProductEventInterface $wbBarcodeProperty,
        ProductDetailByUidInterface $productDetail,
//        MessageDispatchInterface $messageDispatch,
    ): Response
    {
        /** Получаем продукцию заказа */
        $products = $orderProducts->fetchAllOrderProducts($WbOrdersSticker->getOrder());

        /* Получаем продукцию для иллюстрации */
        $current = current($products);

        if(!$current)
        {
            throw new RouteNotFoundException('Order Products Not Found');
        }

        $Product = $productDetail->fetchProductDetailByEventAssociative(
            $current['product_event'],
            $current['product_offer'],
            $current['product_variation'],
        );

        if(!$Product)
        {
            throw new RouteNotFoundException('Product Not Found');
        }

        /* Генерируем боковые стикеры */
        $BarcodeGenerator = new BarcodeGeneratorSVG();
        $barcode = $BarcodeGenerator->getBarcode(
            $current['barcode'],
            $BarcodeGenerator::TYPE_CODE_128,
            2,
            60
        );


        /* Получаем настройки бокового стикера */
        $BarcodeSettings = $barcodeSettings->findWbBarcodeSettings($current['product_id']) ?: null;
        $property = $BarcodeSettings ? $wbBarcodeProperty->getPropertyCollection($current['product_event']) : [];

        return $this->render(
            [
                'item' => $products,
                'barcode' => base64_encode($barcode),
                'counter' => $BarcodeSettings['counter'] ?? 1,
                'settings' => $BarcodeSettings,
                'card' => $Product,
                'property' => $property,
                'sticker' => $WbOrdersSticker->getSticker()
            ]
        );
    }
}
