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

namespace BaksDev\Wildberries\Orders\Entity\Sticker;

use App\System\Entity\EntityEvent;
use BaksDev\Wildberries\Orders\Entity\Event\WbOrdersEvent;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use InvalidArgumentException;

/* Barcode */

#[ORM\Entity]
#[ORM\Table(name: 'wb_orders_sticker')]
#[ORM\Index(columns: ['part'])]
class WbOrdersSticker extends EntityEvent
{
    public const TABLE = 'wb_orders_sticker';

    /** ID события */
    #[ORM\Id]
    #[ORM\OneToOne(inversedBy: 'sticker', targetEntity: WbOrdersEvent::class)]
    #[ORM\JoinColumn(name: 'event_id', referencedColumnName: 'id')]
    private WbOrdersEvent $event;

    /** Стикер */
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $sticker = null;


    /** Номер штрихкод заказа */
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $part = null;


    public function __construct(WbOrdersEvent $event)
    {
        $this->event = $event;
    }

    public function getDto($dto): mixed
    {
        if($dto instanceof WbOrdersStickerInterface)
        {
            return parent::getDto($dto);
        }

        throw new InvalidArgumentException(sprintf('Class %s interface error', $dto::class));
    }

    public function setEntity($dto): mixed
    {
        if($dto instanceof WbOrdersStickerInterface)
        {

            if(empty($dto->getSticker()) && empty($dto->getPart()))
            {
                return false;
            }

            return parent::setEntity($dto);
        }

        throw new InvalidArgumentException(sprintf('Class %s interface error', $dto::class));
    }


}