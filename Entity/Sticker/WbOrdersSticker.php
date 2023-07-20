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

namespace BaksDev\Wildberries\Orders\Entity\Sticker;

use BaksDev\Core\Entity\EntityEvent;
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
    #[ORM\JoinColumn(name: 'event', referencedColumnName: 'id')]
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