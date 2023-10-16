<?php
/*
 *  Copyright 2022.  Baks.dev <admin@baks.dev>
 *
 *  Licensed under the Apache License, Version 2.0 (the "License");
 *  you may not use this file except in compliance with the License.
 *  You may obtain a copy of the License at
 *
 *  http://www.apache.org/licenses/LICENSE-2.0
 *
 *  Unless required by applicable law or agreed to in writing, software
 *  distributed under the License is distributed on an "AS IS" BASIS,
 *  WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 *  See the License for the specific language governing permissions and
 *   limitations under the License.
 *
 */

namespace BaksDev\Wildberries\Orders\UseCase\Command\Sticker;

//use App\Module\Orders\Order\Entity\Order;
//use App\Module\Orders\Order\Type\Id\OrderUid;
//use App\Module\User\Profile\UserProfile\Type\Id\UserProfileUid;
//use App\Module\Wildberries\Orders\Order\Entity\Client\WbOrderClient;
//use App\Module\Wildberries\Orders\Order\Entity\Event\WbOrdersEventInterface;
//use App\Module\Wildberries\Orders\Order\Type\Event\WbOrdersEventUid;
//use App\Module\Wildberries\Orders\Order\Type\Status\WbOrderStatus;
//use App\Module\Wildberries\Orders\Order\Type\Status\WbOrderStatusEnum;
use BaksDev\Orders\Order\Entity\Order;
use BaksDev\Orders\Order\Type\Id\OrderUid;
use BaksDev\Users\Profile\UserProfile\Type\Id\UserProfileUid;
use BaksDev\Wildberries\Orders\Entity\Event\WbOrdersEventInterface;
use BaksDev\Wildberries\Orders\Type\Event\WbOrdersEventUid;
use BaksDev\Wildberries\Orders\Type\OrderStatus\Status\Collection\WbOrderStatusInterface;
use BaksDev\Wildberries\Orders\Type\OrderStatus\WbOrderStatus;
use BaksDev\Wildberries\Orders\Type\WildberriesStatus\Status\Collection\WildberriesStatusInterface;
use BaksDev\Wildberries\Orders\Type\WildberriesStatus\WildberriesStatus;
use DateTimeImmutable;
use Symfony\Component\Validator\Constraints as Assert;

/** @see WbOrdersEvent */
final class StickerWbOrderDTO implements WbOrdersEventInterface
{
    /**
     * Идентификатор события
     */
    #[Assert\Uuid]
    #[Assert\NotBlank]
    private readonly WbOrdersEventUid $id;

    /**
     * Профиль пользователя
     */
    #[Assert\NotBlank]
    #[Assert\Uuid]
    private readonly UserProfileUid $profile;


    /**
     * Стикер заказа
     */
    #[Assert\Valid]
    private Sticker\WbStickerDTO $sticker;


    public function __construct()
    {
        $this->sticker = new Sticker\WbStickerDTO();
    }

    /**
     * Идентификатор события
     */

    public function getEvent(): WbOrdersEventUid
    {
        return $this->id;
    }

//    public function setId(WbOrdersEventUid $id): void
//    {
//        $this->id = $id;
//    }


    /**
     * Профиль пользователя
     */

    public function getProfile(): UserProfileUid
    {
        return $this->profile;
    }


    /**
     * Стикер заказа
     */

    public function getSticker(): Sticker\WbStickerDTO
    {
        return $this->sticker;
    }

    public function setSticker(Sticker\WbStickerDTO $sticker): void
    {
        $this->sticker = $sticker;
    }

}