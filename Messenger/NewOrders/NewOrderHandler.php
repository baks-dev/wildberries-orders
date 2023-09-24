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

namespace BaksDev\Wildberries\Orders\Messenger\NewOrders;

use BaksDev\Orders\Order\Type\Status\OrderStatus\OrderStatusCanceled;
use BaksDev\Orders\Order\UseCase\Admin\NewEdit\OrderDTO;
use BaksDev\Orders\Order\UseCase\Admin\NewEdit\OrderHandler;
use BaksDev\Orders\Order\UseCase\Admin\NewEdit\Products\OrderProductDTO;
use BaksDev\Orders\Order\UseCase\Admin\NewEdit\Products\Price\OrderPriceDTO;
use BaksDev\Orders\Order\UseCase\Admin\Status\OrderStatusDTO;
use BaksDev\Orders\Order\UseCase\Admin\Status\OrderStatusHandler;
use BaksDev\Products\Product\Repository\ProductByVariation\ProductByVariationInterface;
use BaksDev\Reference\Money\Type\Money;
use BaksDev\Wildberries\Api\Token\Orders\WildberriesOrdersNew;
use BaksDev\Wildberries\Orders\Entity\WbOrders;
use BaksDev\Wildberries\Orders\Repository\WbOrdersById\WbOrdersByIdInterface;
use BaksDev\Wildberries\Orders\Type\Email\ClientEmail;
use BaksDev\Wildberries\Orders\Type\OrderStatus\Status\WbOrderStatusNew;
use BaksDev\Wildberries\Orders\Type\WildberriesStatus\Status\WildberriesStatusWaiting;
use BaksDev\Wildberries\Orders\UseCase\Command\NewEdit\WbOrderDTO;
use BaksDev\Wildberries\Orders\UseCase\Command\NewEdit\WbOrderHandler;
use BaksDev\Wildberries\Products\Entity\Cards\WbProductCard;
use BaksDev\Wildberries\Products\Entity\Cards\WbProductCardOffer;
use BaksDev\Wildberries\Products\Entity\Cards\WbProductCardVariation;
use BaksDev\Wildberries\Products\UseCase\Cards\NewEdit\Variation\WbProductCardVariationDTO;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class NewOrderHandler
{

    private WildberriesOrdersNew $wildberriesOrdersNew;
    private WbOrdersByIdInterface $wbOrdersById;
    private ProductByVariationInterface $productByVariation;
    private OrderHandler $orderHandler;
    private OrderStatusHandler $orderStatusHandler;
    private WbOrderHandler $WildberriesOrderHandler;
    private EntityManagerInterface $entityManager;
    private LoggerInterface $messageDispatchLogger;

    public function __construct(
        WildberriesOrdersNew $wildberriesOrdersNew,
        WbOrdersByIdInterface $wbOrdersById,
        ProductByVariationInterface $productByVariation,
        OrderHandler $orderHandler,
        OrderStatusHandler $orderStatusHandler,
        WbOrderHandler $WildberriesOrderHandler,
        EntityManagerInterface $entityManager,
        LoggerInterface $messageDispatchLogger,
    )
    {
        $this->wildberriesOrdersNew = $wildberriesOrdersNew;
        $this->wbOrdersById = $wbOrdersById;
        $this->productByVariation = $productByVariation;
        $this->orderHandler = $orderHandler;
        $this->orderStatusHandler = $orderStatusHandler;
        $this->WildberriesOrderHandler = $WildberriesOrderHandler;
        $this->entityManager = $entityManager;
        $this->messageDispatchLogger = $messageDispatchLogger;
    }

    public function __invoke(NewOrdersMessage $message): void
    {

        $profile = $message->getProfile();

        $orders = $this->wildberriesOrdersNew
            ->profile($profile)
            ->request()
            ->getContent()
        ;

        if(empty($orders))
        {
            return;
        }

        $this->messageDispatchLogger
            ->info(
                sprintf('%s: Добавляем новые заказы Wildberries', $profile),
                [__LINE__ => __FILE__]
            );

        foreach($orders as $order)
        {
            /* Проверяем, имеется ли в системе заказ WB с указанным идентификатором */
            $WbOrder = $this->wbOrdersById->isExistWbOrder($order['id']);

            /* Если заказ существует - пропускам */
            if($WbOrder)
            {
                continue;
            }

            /* Получаем карточку по штрихкод */
            $barcode = current($order['skus']);

            $WbProductCardVariation = $this->entityManager
                ->getRepository(WbProductCardVariation::class)->find($barcode);


            /* Если множественный вариант Wildberries не найден - смотрим, нет ли торгового предложения с данной номенклатурой */
            if(empty($WbProductCardVariation))
            {
                $WbProductCardOfferRemove = $this->entityManager
                    ->getRepository(WbProductCardOffer::class)->find($order['nmId']);

                /* Если найдена номенклатура - следовательно карточка изменилась (восстановлена с другом штрихкодом)*/
                if($WbProductCardOfferRemove)
                {
                    $WbProductCardRemove = $this->entityManager->getRepository(WbProductCard::class)
                        ->find($WbProductCardOfferRemove->getCard());

                    $this->entityManager->remove($WbProductCardRemove);
                }

                $this->messageDispatchLogger
                    ->warning(
                        sprintf('%s: Отсутствует карточка товара Wildberries (article: %s; barcode : %s) ', $profile, $order['article'], $barcode),
                        [__LINE__ => __FILE__]
                    );

                continue;
            }


            /* Применяем множественный вариант Wildberries */
            $WbProductCardVariationDTO = new WbProductCardVariationDTO();
            $WbProductCardVariation->getDto($WbProductCardVariationDTO);


            /* Получаем продукт */
            $ProductVariationConst = $WbProductCardVariationDTO->getVariation();
            $Product = $this->productByVariation->getProductByVariationConstOrNull($ProductVariationConst);


            if(!$Product)
            {

                $this->messageDispatchLogger
                    ->warning(
                        sprintf('%s: Ошибка при добавлении заказа Wildberries ( ProductVariationConst : %s) ', $profile, $ProductVariationConst),
                        [__LINE__ => __FILE__]
                    );

                continue;
            }


            /* Создаем системный заказ */

            $OrderDTO = new OrderDTO();
            $OrderDTO->setUsers(null);

            $OrderProductDTO = new OrderProductDTO();
            $OrderProductDTO->setProduct($Product['event_id']);
            $OrderProductDTO->setOffer($Product['offer_id']);
            $OrderProductDTO->setVariation($Product['variation_id']);

            $OrderPriceDTO = new OrderPriceDTO();
            $price = new Money(($order['convertedPrice'] / 100));
            $OrderPriceDTO->setPrice($price);
            $OrderPriceDTO->setTotal(1);
            $OrderProductDTO->setPrice($OrderPriceDTO);
            $OrderDTO->addProduct($OrderProductDTO);

            $Order = $this->orderHandler->handle($OrderDTO);


            /* Создаем Wildberries заказ */

            $WbOrderDTO = new WbOrderDTO($profile, $order['id']);
            $WbOrderDTO->setOrd($Order->getId());
            $WbOrderDTO->setBarcode((string) $barcode);
            $dateCreated = new DateTimeImmutable($order['createdAt']);
            $WbOrderDTO->setCreated($dateCreated);
            $WbOrderDTO->setStatus(new WbOrderStatusNew());
            $WbOrderDTO->setWildberries(new WildberriesStatusWaiting());

            if($order['user'])
            {
                $WbOrderClientDTO = $WbOrderDTO->getClient();
                $WbOrderClientDTO->setUsername($order['user']['fio']);
                $WbOrderClientDTO->setPhone($order['user']['phone']);
                $WbOrderClientDTO->setEmail(new ClientEmail($order['userInfo']['email']));

                if($order['address'])
                {
                    $address = implode(', ', $order['address']);
                    $WbOrderClientDTO->setAddress($address);
                }
            }

            /* Сохраняем */
            $WildberriesOrderResult = $this->WildberriesOrderHandler->handle($WbOrderDTO);

            if(!$WildberriesOrderResult instanceof WbOrders)
            {
                $OrderStatusDTO = new OrderStatusDTO(
                    new OrderStatusCanceled(),
                    $Order->getEvent(),
                    $profile
                );

                $this->orderStatusHandler->handle($OrderStatusDTO);
            }


            $this->messageDispatchLogger
                ->info(
                    sprintf('%s: Добавили новый заказ ( order : %s) ', $profile, $order['id']),
                    [__LINE__ => __FILE__]
                );

        }
    }
}