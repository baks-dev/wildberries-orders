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

use BaksDev\Contacts\Region\Repository\PickupByGeolocation\PickupByGeolocationInterface;
use BaksDev\Core\Entity\AbstractHandler;
use BaksDev\Core\Messenger\MessageDispatchInterface;
use BaksDev\Core\Type\Field\InputField;
use BaksDev\Core\Type\Gps\GpsLatitude;
use BaksDev\Core\Type\Gps\GpsLongitude;
use BaksDev\Core\Validator\ValidatorCollectionInterface;
use BaksDev\Delivery\Repository\CurrentDeliveryEvent\CurrentDeliveryEventInterface;
use BaksDev\Files\Resources\Upload\File\FileUploadInterface;
use BaksDev\Files\Resources\Upload\Image\ImageUploadInterface;
use BaksDev\Orders\Order\Entity\Event\OrderEvent;
use BaksDev\Orders\Order\Entity\Order;
use BaksDev\Orders\Order\Messenger\OrderMessage;
use BaksDev\Orders\Order\Repository\ExistsOrderNumber\ExistsOrderNumberInterface;
use BaksDev\Orders\Order\Repository\FieldByDeliveryChoice\FieldByDeliveryChoiceInterface;
use BaksDev\Orders\Order\Type\Status\OrderStatus\Collection\OrderStatusNew;
use BaksDev\Products\Product\Repository\CurrentProductByArticle\ProductConstByBarcodeInterface;
use BaksDev\Users\Address\Services\GeocodeAddressParser;
use BaksDev\Users\Profile\UserProfile\Entity\UserProfile;
use BaksDev\Users\Profile\UserProfile\Repository\UserByUserProfile\UserByUserProfileInterface;
use BaksDev\Users\Profile\UserProfile\Repository\UserProfileGps\UserProfileGpsInterface;
use BaksDev\Users\Profile\UserProfile\UseCase\User\NewEdit\UserProfileHandler;
use BaksDev\Wildberries\Orders\UseCase\New\User\Delivery\Field\OrderDeliveryFieldDTO;
use Doctrine\ORM\EntityManagerInterface;

final class WildberriesOrderHandler extends AbstractHandler
{
    public function __construct(
        private readonly UserProfileHandler $profileHandler,
        private readonly ProductConstByBarcodeInterface $ProductConstByBarcode,
        private readonly FieldByDeliveryChoiceInterface $deliveryFields,
        private readonly CurrentDeliveryEventInterface $currentDeliveryEvent,
        private readonly GeocodeAddressParser $geocodeAddressParser,
        private readonly ExistsOrderNumberInterface $existsOrderNumber,
        private readonly UserByUserProfileInterface $userByUserProfile,
        private readonly PickupByGeolocationInterface $pickupByGeolocation,

        private readonly UserProfileGpsInterface $userProfileGps,

        EntityManagerInterface $entityManager,
        MessageDispatchInterface $messageDispatch,
        ValidatorCollectionInterface $validatorCollection,
        ImageUploadInterface $imageUpload,
        FileUploadInterface $fileUpload,
    )
    {
        parent::__construct($entityManager, $messageDispatch, $validatorCollection, $imageUpload, $fileUpload);
    }

    public function handle(WildberriesOrderDTO $command): Order|string|bool
    {
        if(false === $command->getStatusEquals(OrderStatusNew::class))
        {
            return 'Заказ не является в статусе New «Новый»';
        }

        $isExists = $this->existsOrderNumber->isExists($command->getNumber());

        if($isExists)
        {
            return true;
        }

        /**
         * Присваиваем заказу идентификатор пользователя User
         */

        $NewOrderInvariable = $command->getInvariable();

        $User = $this->userByUserProfile
            ->forProfile($NewOrderInvariable->getProfile())
            ->find();

        if($User === false)
        {
            return 'Профиль магазина не найден';
        }

        $NewOrderInvariable->setUsr($User->getId());

        /**
         * Получаем события продукции
         * @var Products\NewOrderProductDTO $product
         */
        foreach($command->getProduct() as $product)
        {
            $ProductData = $this->ProductConstByBarcode->find($product->getBarcode());

            /** Если по штрихкоду не найден - пробуем найти по артикулу */
            //$ProductData ?: $ProductData = $this->ProductConstByArticle->find($product->getArticle());

            if(!$ProductData)
            {
                $error = sprintf('%s: Артикул товара не найден (%s)', $product->getArticle(), $product->getBarcode());
                //throw new InvalidArgumentException($error);
                return $error;
            }

            $product
                ->setProduct($ProductData->getEvent())
                ->setOffer($ProductData->getOffer())
                ->setVariation($ProductData->getVariation())
                ->setModification($ProductData->getModification());
        }


        /** Присваиваем информацию о покупателе (При доставке DBS) */
        // $this->fillProfile($command);

        /** Присваиваем информацию о доставке */
        $this->fillDelivery($command);

        $OrderUserDTO = $command->getUsr();

        /**
         * Создаем профиль пользователя
         */
        if($OrderUserDTO->getProfile() === null)
        {
            $UserProfileDTO = $OrderUserDTO->getUserProfile();
            $this->validatorCollection->add($UserProfileDTO);

            if($UserProfileDTO === null)
            {
                return $this->validatorCollection->getErrorUniqid();
            }

            /* Присваиваем новому профилю идентификатор пользователя */
            $UserProfileDTO->getInfo()->setUsr($OrderUserDTO->getUsr());
            $UserProfile = $this->profileHandler->handle($UserProfileDTO);

            if(!$UserProfile instanceof UserProfile)
            {
                return $UserProfile;
            }

            $UserProfileEvent = $UserProfile->getEvent();
            $OrderUserDTO->setProfile($UserProfileEvent);
        }

        /** Сохраняем */

        $Order = new Order();
        //$Order->setNumber($command->getNumber());

        $this
            ->setCommand($command)
            ->preEventPersistOrUpdate($Order, OrderEvent::class);

        /** Валидация всех объектов */
        if($this->validatorCollection->isInvalid())
        {
            return $this->validatorCollection->getErrorUniqid();
        }

        $this->flush();

        /* Отправляем сообщение в шину */
        $this->messageDispatch->dispatch(
            message: new OrderMessage($this->main->getId(), $this->main->getEvent(), $command->getEvent()),
            transport: 'orders-order'
        );


        return $this->main;
    }


    public function fillDelivery(WildberriesOrderDTO $command): void
    {
        /* Идентификатор свойства адреса доставки */
        $OrderDeliveryDTO = $command->getUsr()->getDelivery();

        /**
         * Определяем геолокацию по адресу
         */
        if(false === empty($OrderDeliveryDTO->getAddress()))
        {
            $GeocodeAddress = $this->geocodeAddressParser
                ->getGeocode($OrderDeliveryDTO->getAddress());

            if(!empty($GeocodeAddress))
            {
                $OrderDeliveryDTO->setAddress($GeocodeAddress->getAddress());
                $OrderDeliveryDTO->setLatitude($GeocodeAddress->getLatitude());
                $OrderDeliveryDTO->setLongitude($GeocodeAddress->getLongitude());
            }
        }


        /**
         * Если адреса доставки не найдено - определяем адрес геолокации профиля ответственного
         */
        if(true === empty($OrderDeliveryDTO->getAddress()))
        {
            $UserProfileUid = $command->getInvariable()->getProfile();

            $GeocodeAddress = $this->userProfileGps->findUserProfileGps($UserProfileUid);

            if(false === $GeocodeAddress)
            {
                return;
            }

            $OrderDeliveryDTO->setAddress($GeocodeAddress['location']);
            $OrderDeliveryDTO->setLatitude(new GpsLatitude($GeocodeAddress['latitude']));
            $OrderDeliveryDTO->setLongitude(new GpsLongitude($GeocodeAddress['longitude']));
        }


        /**
         * Определяем свойства доставки и присваиваем адрес
         */

        $fields = $this->deliveryFields->fetchDeliveryFields($OrderDeliveryDTO->getDelivery());


        /**
         * Указываем адрес доставки
         */

        $address_field = array_filter($fields, function($v) {
            /** @var InputField $InputField */
            return $v->getType()->getType() === 'address_field';
        });

        $address_field = current($address_field);

        if($address_field)
        {
            $OrderDeliveryFieldDTO = new OrderDeliveryFieldDTO();
            $OrderDeliveryFieldDTO->setField($address_field);
            $OrderDeliveryFieldDTO->setValue($OrderDeliveryDTO->getAddress());
            $OrderDeliveryDTO->addField($OrderDeliveryFieldDTO);
        }

        /**
         * При самовывозе указываем ПВЗ
         */

        $contacts_region = array_filter($fields, function($v) {
            /** @var InputField $InputField */
            return $v->getType()->getType() === 'contacts_region_type';
        });

        $contacts_field = current($contacts_region);

        if($contacts_field)
        {
            $OrderDeliveryFieldDTO = new OrderDeliveryFieldDTO();
            $OrderDeliveryFieldDTO->setField($contacts_field);

            /** Определяем по геолокации ПВЗ */
            $PickupByGeolocationDTO = $this->pickupByGeolocation
                ->latitude($OrderDeliveryDTO->getLatitude())
                ->longitude($OrderDeliveryDTO->getLongitude())
                ->execute();

            if($PickupByGeolocationDTO)
            {
                $OrderDeliveryFieldDTO->setValue((string) $PickupByGeolocationDTO->getId());
            }

            $OrderDeliveryDTO->addField($OrderDeliveryFieldDTO);
        }

        /**
         * Присваиваем активное событие доставки
         */

        $DeliveryEventUid = $this->currentDeliveryEvent
            ->forDelivery($OrderDeliveryDTO->getDelivery())
            ->getId();

        $OrderDeliveryDTO->setEvent($DeliveryEventUid);
    }
}
