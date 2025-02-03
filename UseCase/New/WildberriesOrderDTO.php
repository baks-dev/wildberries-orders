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

namespace BaksDev\Wildberries\Orders\UseCase\New;

use BaksDev\Delivery\Type\Id\DeliveryUid;
use BaksDev\Orders\Order\Entity\Event\OrderEventInterface;
use BaksDev\Orders\Order\Type\Event\OrderEventUid;
use BaksDev\Orders\Order\Type\Status\OrderStatus;
use BaksDev\Orders\Order\Type\Status\OrderStatus\Collection\OrderStatusInterface;
use BaksDev\Orders\Order\Type\Status\OrderStatus\OrderStatusNew;
use BaksDev\Payment\Type\Id\PaymentUid;
use BaksDev\Reference\Currency\Type\Currency;
use BaksDev\Reference\Money\Type\Money;
use BaksDev\Users\Profile\TypeProfile\Type\Id\TypeProfileUid;
use BaksDev\Users\Profile\UserProfile\Type\Id\UserProfileUid;
use BaksDev\Wildberries\Orders\Type\DeliveryType\TypeDeliveryDbsWildberries;
use BaksDev\Wildberries\Orders\Type\DeliveryType\TypeDeliveryFbsWildberries;
use BaksDev\Wildberries\Orders\Type\PaymentType\TypePaymentDbsWildberries;
use BaksDev\Wildberries\Orders\Type\PaymentType\TypePaymentFbsWildberries;
use BaksDev\Wildberries\Orders\Type\ProfileType\TypeProfileDbsWildberries;
use BaksDev\Wildberries\Orders\Type\ProfileType\TypeProfileFbsWildberries;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Validator\Constraints as Assert;

/** @see OrderEvent */
final class WildberriesOrderDTO implements OrderEventInterface
{
    /** Идентификатор события */
    #[Assert\Uuid]
    private ?OrderEventUid $id = null;

    /** Идентификатор заказа YandexMarket */
    private string $number;

    /** Постоянная величина */
    #[Assert\Valid]
    private Invariable\NewOrderInvariable $invariable;

    /** Дата заказа */
    #[Assert\NotBlank]
    private DateTimeImmutable $created;

    /** Статус заказа */
    private OrderStatus $status;

    /** Коллекция продукции в заказе */
    #[Assert\Valid]
    private ArrayCollection $product;

    /** Пользователь */
    #[Assert\Valid]
    private User\OrderUserDTO $usr;

    /** Комментарий к заказу */
    private ?string $comment = null;

    public function __construct(array $order, UserProfileUid $profile)
    {

        /** Постоянная величина */
        $NewOrderInvariable = new Invariable\NewOrderInvariable();
        $NewOrderInvariable->setCreated(new DateTimeImmutable($order['createdAt'] ?: 'now'));
        $NewOrderInvariable->setProfile($profile);
        $NewOrderInvariable->setNumber('W-'.$order['id']); // помечаем заказ префиксом W
        $this->invariable = $NewOrderInvariable;

        /** @deprecated переносится в Invariable */
        $this->number = 'W-'.$order['id']; // помечаем заказ префиксом Y
        $this->created = new DateTimeImmutable($order['createdAt'] ?: 'now');


        $this->status = new OrderStatus(OrderStatusNew::class);

        $this->product = new ArrayCollection();
        $this->usr = new User\OrderUserDTO();


        $OrderDeliveryDTO = $this->usr->getDelivery();
        $OrderPaymentDTO = $this->usr->getPayment();
        $OrderProfileDTO = $this->usr->getUserProfile();


        // Доставка Wildberries (FBS)
        if($order['deliveryType'] === 'fbs')
        {
            /** Тип профиля FBS Wildberries */
            $Profile = new TypeProfileUid(TypeProfileFbsWildberries::class);
            $OrderProfileDTO?->setType($Profile);

            /** Способ доставки Wildberries (FBS Wildberries) */
            $Delivery = new DeliveryUid(TypeDeliveryFbsWildberries::class);
            $OrderDeliveryDTO->setDelivery($Delivery);

            /** Способ оплаты FBS Wildberries */
            $Payment = new PaymentUid(TypePaymentFbsWildberries::class);
            $OrderPaymentDTO->setPayment($Payment);
        }

        // Доставка Магазином (DBS)
        if($order['deliveryType'] === 'dbs')
        {
            /** Тип профиля DBS Yandex Market */
            $Profile = new TypeProfileUid(TypeProfileDbsWildberries::class);
            $OrderProfileDTO?->setType($Profile);

            /** Способ доставки Магазином (DBS Yandex Market) */
            $Delivery = new DeliveryUid(TypeDeliveryDbsWildberries::class);
            $OrderDeliveryDTO->setDelivery($Delivery);

            /** Способ оплаты DBS Yandex Market  */
            $Payment = new PaymentUid(TypePaymentDbsWildberries::class);
            $OrderPaymentDTO->setPayment($Payment);
        }


        //    fbs - доставка на склад Wildberries (FBS)
        //    dbs - доставка силами продавца (DBS)
        //    edbs - экспресс-доставка силами продавца (EDBS)
        //    wbgo - доставка курьером WB (DBW)

        $deliveryDate = match ($order['deliveryType'])
        {
            'fbs' => $NewOrderInvariable->getCreated()->modify('+1 day'),
            'dbs', 'edbs', 'wbgo' => new DateTimeImmutable($order['ddate'] ?: 'now'),
            default => $NewOrderInvariable->getCreated(),
        };

        $OrderDeliveryDTO->setDeliveryDate($deliveryDate);

        /** Адрес доставки */

        $deliveryAddress[] = $order['address'];
        $OrderDeliveryDTO->setAddress(implode(', ', $deliveryAddress));


        /**
         * Комментарий покупателя
         */

        $deliveryComment[] = null;

        /** Признак заказа, сделанного на нулевой остаток товара. */
        $order['isZeroOrder'] === false ?: $deliveryComment[] = 'Заказ сделан на товар с остатком равным нулю. Заказ можно отменить без штрафа за отмену';

        $this->comment = implode(', ', $deliveryComment);


        /** Продукция */

        $NewOrderProductDTO = new Products\NewOrderProductDTO($order['article'], current($order['skus']));

        $NewOrderPriceDTO = $NewOrderProductDTO->getPrice();

        $Money = new Money($order['convertedPrice'], true); // Стоимость товара в валюте магазина до применения скидок.
        $Currency = new Currency($order['convertedCurrencyCode']);

        $NewOrderPriceDTO->setPrice($Money);
        $NewOrderPriceDTO->setCurrency($Currency);
        $NewOrderPriceDTO->setTotal(1);

        $this->addProduct($NewOrderProductDTO);
    }


    /** @see OrderEvent */
    public function getEvent(): ?OrderEventUid
    {
        return $this->id;
    }

    public function setId(?OrderEventUid $id): self
    {
        $this->id = $id;
        return $this;
    }

    /**
     * Status
     */
    public function getStatus(): OrderStatus
    {
        return $this->status;
    }

    public function getStatusEquals(mixed $status): bool
    {
        return $this->status->equals($status);
    }

    public function setStatus(OrderStatus|OrderStatusInterface|string $status): self
    {
        $this->status = new OrderStatus($status);
        return $this;
    }


    /**
     * Number
     */
    public function getNumber(): string
    {
        return $this->number;
    }


    /**
     * Коллекция продукции в заказе
     * @return ArrayCollection<Products\NewOrderProductDTO>
     */

    public function getProduct(): ArrayCollection
    {
        return $this->product;
    }

    public function setProduct(ArrayCollection $product): void
    {
        $this->product = $product;
    }

    public function addProduct(Products\NewOrderProductDTO $product): void
    {
        $filter = $this->product->filter(function(Products\NewOrderProductDTO $element) use ($product) {
            return $element->getArticle() === $product->getArticle();
        });

        if($filter->isEmpty())
        {
            $this->product->add($product);
        }
    }

    public function removeProduct(Products\NewOrderProductDTO $product): void
    {
        $this->product->removeElement($product);
    }

    /**
     * Usr
     */
    public function getUsr(): User\OrderUserDTO
    {
        return $this->usr;
    }

    /**
     * Comment
     */
    public function getComment(): ?string
    {
        return $this->comment;
    }


    /**
     * Invariable
     */
    public function getInvariable(): Invariable\NewOrderInvariable
    {
        return $this->invariable;
    }

    /**
     * Profile
     */
    public function getProfile(): ?UserProfileUid
    {
        return $this->profile;
    }

    public function resetProfile(?UserProfileUid $profile = null): self
    {
        $this->profile = $profile;
        return $this;
    }

}
