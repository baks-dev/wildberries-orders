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

namespace BaksDev\Wildberries\Orders\Entity\Event;

use BaksDev\Core\Entity\EntityEvent;
use BaksDev\Orders\Order\Entity\Order;
use BaksDev\Orders\Order\Type\Id\OrderUid;
use BaksDev\Users\Profile\UserProfile\Type\Id\UserProfileUid;
use BaksDev\Wildberries\Orders\Entity\Client\WbOrderClient;
use BaksDev\Wildberries\Orders\Entity\Modify\WbOrdersModify;
use BaksDev\Wildberries\Orders\Entity\Sticker\WbOrdersSticker;
use BaksDev\Wildberries\Orders\Entity\WbOrders;
use BaksDev\Wildberries\Orders\Type\Event\WbOrdersEventUid;
use BaksDev\Wildberries\Orders\Type\OrderStatus\Status\WbOrderStatusNew;
use BaksDev\Wildberries\Orders\Type\OrderStatus\WbOrderStatus;
use BaksDev\Wildberries\Orders\Type\WildberriesStatus\Status\WildberriesStatusWaiting;
use BaksDev\Wildberries\Orders\Type\WildberriesStatus\WildberriesStatus;
use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use InvalidArgumentException;
use Symfony\Component\Validator\Constraints as Assert;

/* WbOrderEvent */

#[ORM\Entity]
#[ORM\Table(name: 'wb_orders_event')]
#[ORM\Index(columns: ['main'])]
#[ORM\Index(columns: ['barcode'])]
#[ORM\Index(columns: ['profile'])]
#[ORM\Index(columns: ['status'])]
#[ORM\Index(columns: ['created', 'status'])]
class WbOrdersEvent extends EntityEvent
{
    public const TABLE = 'wb_orders_event';

    /**
     * Идентификатор события
     */
    #[Assert\NotBlank]
    #[Assert\Uuid]
    #[ORM\Id]
    #[ORM\Column(type: WbOrdersEventUid::TYPE)]
    private WbOrdersEventUid $id;

    /**
     * Профиль пользователя
     */
    #[ORM\Column(type: UserProfileUid::TYPE)]
    private UserProfileUid $profile;

    /**
     * Идентификатор системного заказа
     */
    #[Assert\NotBlank]
    #[Assert\Uuid]
    #[ORM\Column(type: OrderUid::TYPE)]
    private ?OrderUid $main = null;

    /**
     * Штрихкод
     */
    #[Assert\NotBlank]
    #[ORM\Column(type: Types::STRING)]
    private string $barcode;

    /**
     * Дата создания заказа на Wildberries
     */
    #[Assert\NotBlank]
    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private DateTimeImmutable $created;

    /**
     * Статус сборочного задания
     */
    #[Assert\NotBlank]
    #[ORM\Column(type: WbOrderStatus::TYPE)]
    private WbOrderStatus $status;

    /**
     * Внутренний статус Wildberries
     */
    #[Assert\NotBlank]
    #[ORM\Column(type: WildberriesStatus::TYPE)]
    private WildberriesStatus $wildberries;


    /**
     * Модификатор
     */
    #[ORM\OneToOne(targetEntity: WbOrdersModify::class, mappedBy: 'event', cascade: ['all'])]
    private WbOrdersModify $modify;

    /**
     * Стикер заказа
     */
    #[ORM\OneToOne(targetEntity: WbOrdersSticker::class, mappedBy: 'event', cascade: ['all'])]
    private ?WbOrdersSticker $sticker = null;

    /**
     * Клиент
     */
    #[ORM\OneToOne(targetEntity: WbOrderClient::class, mappedBy: 'event', cascade: ['all'])]
    private ?WbOrderClient $client = null;


    public function __construct()
    {
        $this->id = new WbOrdersEventUid();
        $this->modify = new WbOrdersModify($this);

        $this->status = new WbOrderStatus(new WbOrderStatusNew());
        $this->wildberries = new WildberriesStatus(new WildberriesStatusWaiting());
    }

    public function __clone()
    {
        $this->id = clone $this->id;
    }

    public function __toString(): string
    {
        return (string) $this->id;
    }

    public function getDto($dto): mixed
    {
        $dto = is_string($dto) && class_exists($dto) ? new $dto() : $dto;

        if($dto instanceof WbOrdersEventInterface)
        {
            return parent::getDto($dto);
        }

        throw new InvalidArgumentException(sprintf('Class %s interface error', $dto::class));
    }


    public function setEntity($dto): mixed
    {
        if($dto instanceof WbOrdersEventInterface || $dto instanceof self)
        {
            return parent::setEntity($dto);
        }

        throw new InvalidArgumentException(sprintf('Class %s interface error', $dto::class));
    }

    /**
     * Id
     */
    public function getId(): WbOrdersEventUid
    {
        return $this->id;
    }


    public function getMain(): ?OrderUid
    {
        return $this->main;
    }

    public function setMain(OrderUid|WbOrders $ord): void
    {
        $this->main = $ord instanceof WbOrders ? $ord->getId() : $ord;
    }


    public function statusEquals(mixed $status): bool
    {
        return $this->status->equals($status);
    }
}