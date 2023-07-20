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

namespace BaksDev\Wildberries\Orders\UseCase\Command\NewEdit;

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
final class WbOrderDTO implements WbOrdersEventInterface
{
    private bool $update = false;

    /**
     * Идентификатор события
     */
    #[Assert\Uuid]
    private ?WbOrdersEventUid $id = null;

    /**
     * Профиль пользователя
     */
    /**
     * Идентификатор события
     */
    #[Assert\NotBlank]
    #[Assert\Uuid]
    private readonly UserProfileUid $profile;

    /**
     * Идентификатор системного заказа
     */
    #[Assert\NotBlank]
    #[Assert\Uuid]
    private readonly OrderUid $ord;

    /**
     * Идентификатор заказа Wildberries
     * @see WbOrders
     */
    #[Assert\NotBlank]
    private readonly int $wbOrder;

    /**
     * Штрихкод
     */
    private string $barcode;

    /**
     * Статус сборочного задания
     */
    #[Assert\NotBlank]
    private WbOrderStatus $status;

    /**
     * Внутренний статус Wildberries
     */
    #[Assert\NotBlank]
    private WildberriesStatus $wildberries;

    /**
     * Дата создания заказа на Wildberries
     */
    #[Assert\NotBlank]
    private DateTimeImmutable $created;

    /**
     * Клиент
     */
    #[Assert\Valid]
    private Client\WbOrderClientDTO $client;

    /**
     * Стикер заказа
     */
    #[Assert\Valid]
    private Sticker\WbStickerDTO $sticker;


    public function __construct(UserProfileUid $profile, int $wbOrder)
    {
        $this->client = new Client\WbOrderClientDTO();
        $this->sticker = new Sticker\WbStickerDTO();
        $this->wbOrder = $wbOrder;
        $this->profile = $profile;
    }

    /**
     * Идентификатор события
     */

    public function getEvent(): ?WbOrdersEventUid
    {
        return $this->id;
    }

    public function setId(WbOrdersEventUid $id): void
    {
        $this->id = $id;
    }

    /**
     * Идентификатор заказа Wildberries
     * @see WbOrders
     */

    public function getWbOrder(): int
    {
        return $this->wbOrder;
    }

    /**
     * Статус сборочного задания
     */

    public function getStatus(): WbOrderStatus
    {
        return $this->status;
    }

    public function setStatus(WbOrderStatus|WbOrderStatusInterface $status): void
    {
        $this->status = $status instanceof WbOrderStatusInterface ? new WbOrderStatus($status) : $status;
    }

    /**
     * Внутренний статус Wildberries
     */
    public function getWildberries(): WildberriesStatus
    {
        return $this->wildberries;
    }

    public function setWildberries(WildberriesStatus|WildberriesStatusInterface $wildberries): void
    {
        $this->wildberries = $wildberries instanceof WildberriesStatusInterface ? new WildberriesStatus($wildberries) : $wildberries;
    }


    /**
     * Дата создания заказа на Wildberries
     */

    public function getCreated(): DateTimeImmutable
    {
        return $this->created;
    }

    public function setCreated(DateTimeImmutable $created): void
    {
        $this->created = $created;
    }



    /**
     * Клиент
     */
    public function getClient(): Client\WbOrderClientDTO
    {
        return $this->client;
    }


    public function setClient(Client\WbOrderClientDTO $client): void
    {
        $this->client = $client;
    }


    /**
     * Штрихкод
     */
    public function getBarcode(): string
    {
        return $this->barcode;
    }

    public function setBarcode(string $barcode): void
    {
        $this->barcode = $barcode;
    }



    /**
     * Идентификатор системного заказа
     */

    public function getOrd(): ?OrderUid
    {
        return $this->ord;
    }


    public function setOrd(OrderUid|Order $order): void
    {
        $this->ord = $order instanceof Order ? $order->getId() : $order;
    }


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








//    public function update(): void
//    {
//        $this->update = true;
//    }
//
//    public function isUpdate(): bool
//    {
//        return $this->update;
//    }


}