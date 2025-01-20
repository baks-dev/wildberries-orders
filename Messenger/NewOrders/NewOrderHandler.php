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

namespace BaksDev\Wildberries\Orders\Messenger\NewOrders;

use BaksDev\Core\Messenger\MessageDispatchInterface;
use BaksDev\Orders\Order\Type\Status\OrderStatus\OrderStatusCanceled;
use BaksDev\Orders\Order\UseCase\Admin\Edit\EditOrderDTO;
use BaksDev\Orders\Order\UseCase\Admin\Edit\EditOrderHandler;
use BaksDev\Orders\Order\UseCase\Admin\Edit\Products\OrderProductDTO;
use BaksDev\Orders\Order\UseCase\Admin\Edit\Products\Price\OrderPriceDTO;
use BaksDev\Orders\Order\UseCase\Admin\Status\OrderStatusDTO;
use BaksDev\Orders\Order\UseCase\Admin\Status\OrderStatusHandler;
use BaksDev\Products\Product\Repository\ProductByVariation\ProductByVariationInterface;
use BaksDev\Reference\Money\Type\Money;
use BaksDev\Users\Profile\UserProfile\Repository\UserByUserProfile\UserByUserProfileInterface;
use BaksDev\Users\Profile\UserProfile\Type\Id\UserProfileUid;
use BaksDev\Wildberries\Orders\Api\WildberriesOrdersNew;
use BaksDev\Wildberries\Orders\Entity\WbOrders;
use BaksDev\Wildberries\Orders\Repository\WbOrdersById\WbOrdersByIdInterface;
use BaksDev\Wildberries\Orders\Type\Email\ClientEmail;
use BaksDev\Wildberries\Orders\Type\OrderStatus\Status\WbOrderStatusNew;
use BaksDev\Wildberries\Orders\Type\WildberriesStatus\Status\WildberriesStatusWaiting;
use BaksDev\Wildberries\Orders\UseCase\Command\New\CreateWbOrderDTO;
use BaksDev\Wildberries\Orders\UseCase\Command\New\CreateWbOrderHandler;
use BaksDev\Wildberries\Products\Entity\Cards\WbProductCard;
use BaksDev\Wildberries\Products\Entity\Cards\WbProductCardOffer;
use BaksDev\Wildberries\Products\Entity\Cards\WbProductCardVariation;
use BaksDev\Wildberries\Products\Messenger\WbCardNew\WbCardNewMessage;
use BaksDev\Wildberries\Products\UseCase\Cards\NewEdit\Variation\WbProductCardVariationDTO;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class NewOrderHandler
{
    private UserProfileUid $profile;

    private bool $WbCardUpdate = false;

    private string $barcode;

    public function __construct(
        #[Target('wildberriesOrdersLogger')] private readonly LoggerInterface $logger,
        private readonly WildberriesOrdersNew $wildberriesOrdersNew,
        private readonly WbOrdersByIdInterface $wbOrdersById,
        private readonly ProductByVariationInterface $productByVariation,
        private readonly EditOrderHandler $orderHandler,
        private readonly OrderStatusHandler $orderStatusHandler,
        private readonly CreateWbOrderHandler $WildberriesOrderHandler,
        private readonly EntityManagerInterface $entityManager,
        private readonly UserByUserProfileInterface $userByUserProfile,
        private readonly MessageDispatchInterface $messageDispatch
    ) {}

    public function __invoke(NewOrdersMessage $message): void
    {
        $this->profile = $message->getProfile();

        /* Получить список новых сборочных заданий */
        $orders = $this->wildberriesOrdersNew
            ->profile($this->profile)
            ->request()
            ->getContent();

        if(empty($orders))
        {
            return;
        }

        $this->logger
            ->info(
                sprintf('%s: Добавляем новые заказы Wildberries', $this->profile),
                [self::class.':'.__LINE__]
            );

        foreach($orders as $order)
        {
            if($this->wbOrdersById->isExistWbOrder($order['id']))
            {
                continue; /* Переходим к следующему заказу */
            }

            $this->barcode = (string) current($order['skus']);

            $WbProductCardVariation = $this->entityManager
                ->getRepository(WbProductCardVariation::class)
                ->find($this->barcode);

            if(!$WbProductCardVariation)
            {
                $this->removeWbCardWhereExistNomenclature($order['nmId']);
                $this->WbCardUpdate = true;

                continue;
            }

            /* Применяем множественный вариант Wildberries */
            $WbProductCardVariationDTO = new WbProductCardVariationDTO();
            $WbProductCardVariation->getDto($WbProductCardVariationDTO);

            /* Получаем продукт */
            $ProductVariationConst = $WbProductCardVariationDTO->getVariation();
            $Product = $this->productByVariation->getProductByVariationOrNull($ProductVariationConst);

            if(!$Product)
            {
                $this->removeWbCardWhereExistNomenclature($order['nmId']);
                $this->WbCardUpdate = true;

                continue;
            }


            /**
             * Создаем системный заказ
             */

            $OrderDTO = new EditOrderDTO();
            $OrderDTO->setUsr(null);

            $OrderProductDTO = new OrderProductDTO();
            $OrderProductDTO->setProduct($Product['event_id']);
            $OrderProductDTO->setOffer($Product['offer_id']);
            $OrderProductDTO->setVariation($Product['variation_id']);

            $OrderPriceDTO = new OrderPriceDTO();
            $price = new Money($order['convertedPrice'], true);
            $OrderPriceDTO->setPrice($price);
            $OrderPriceDTO->setTotal(1);
            $OrderProductDTO->setPrice($OrderPriceDTO);
            $OrderDTO->addProduct($OrderProductDTO);


            $Order = $this->orderHandler->handle($OrderDTO); /* Сохраняем системный заказ */


            /**
             * Создаем Wildberries заказ
             */

            $WbOrderDTO = new CreateWbOrderDTO($this->profile, $order['id']);
            $WbOrderDTO->setMain($Order->getId());
            $WbOrderDTO->setBarcode($this->barcode);
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

            $WildberriesOrderHandle = $this->WildberriesOrderHandler->handle($WbOrderDTO);

            if(!$WildberriesOrderHandle instanceof WbOrders)
            {
                $User = $this->userByUserProfile
                    ->forProfile($this->profile)
                    ->findUser();

                $OrderStatusDTO = new OrderStatusDTO(
                    OrderStatusCanceled::class,
                    $Order->getEvent(),
                    $User,
                    $this->profile
                );

                $this->orderStatusHandler->handle($OrderStatusDTO);
            }


            $this->logger
                ->info(
                    sprintf(
                        '%s: Добавили новый заказ ( order : %s )',
                        $this->profile,
                        $order['id']
                    ),
                    [self::class.':'.__LINE__]
                );

        }

        $this->WbCardUpdate();
    }


    /**
     * Добавляет комманду для обновления новыми карточками
     */
    public function WbCardUpdate(): void
    {
        if($this->WbCardUpdate)
        {
            $this->messageDispatch->dispatch(
                message: new WbCardNewMessage($this->profile),
                transport: (string) $this->profile,
            );
        }
    }

    /**
     * Если множественный вариант Wildberries не найден - смотрим, нет ли торгового предложения с данной номенклатурой
     * Если найдено торговое предложение с номенклатурой - следовательно карточка изменилась
     * (могла быть восстановлена из корзины с другом штрихкодом)
     */
    public function removeWbCardWhereExistNomenclature(int $nomenclature): void
    {
        $WbProductCardOfferRemove = $this->entityManager
            ->getRepository(WbProductCardOffer::class)
            ->find($nomenclature);

        if($WbProductCardOfferRemove)
        {
            $WbProductCardRemove = $this->entityManager
                ->getRepository(WbProductCard::class)
                ->find($WbProductCardOfferRemove->getCard());

            $this->entityManager->remove($WbProductCardRemove);
        }
    }
}
