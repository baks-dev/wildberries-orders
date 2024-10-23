<?php
/*
 *  Copyright 2024.  Baks.dev <admin@baks.dev>
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

namespace BaksDev\Wildberries\Orders\Entity\Client;

use BaksDev\Core\Entity\EntityEvent;
use BaksDev\Core\Entity\EntityReadonly;
use BaksDev\Orders\Order\Type\Id\OrderUid;
use BaksDev\Wildberries\Orders\Entity\Event\WbOrdersEvent;
use BaksDev\Wildberries\Orders\Type\Email\ClientEmail;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use InvalidArgumentException;
use Symfony\Component\Validator\Constraints as Assert;

/* WbOrderClient */

#[ORM\Entity]
#[ORM\Table(name: 'wb_orders_client')]
class WbOrderClient extends EntityReadonly
{
    public const TABLE = 'wb_orders_client';

    /**
     * Идентификатор WbSupply
     */
    #[Assert\NotBlank]
    #[Assert\Uuid]
    #[ORM\Id]
    #[ORM\Column(type: OrderUid::TYPE)]
    private OrderUid $main;

    /**
     * Идентификатор события
     */
    #[Assert\NotBlank]
    #[Assert\Uuid]
    #[ORM\OneToOne(inversedBy: 'client', targetEntity: WbOrdersEvent::class)]
    #[ORM\JoinColumn(name: 'event', referencedColumnName: 'id')]
    private WbOrdersEvent $event;

    /**
     * ФИО клиента
     */
    #[ORM\Column(type: Types::STRING, nullable: true)]
    private ?string $username;

    /**
     * Контактный телефон
     */
    #[ORM\Column(type: Types::STRING, nullable: true)]
    private ?string $phone;

    /**
     * Email клиента
     */
    #[ORM\Column(type: ClientEmail::TYPE, nullable: true)]
    private ?ClientEmail $email;

    /**
     * Адрес клиента
     */
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $address;


    public function __construct(WbOrdersEvent $event)
    {
        $this->event = $event;
        $this->main = $event->getMain();

    }

    public function __toString(): string
    {
        return (string) $this->main;
    }


    public function getDto($dto): mixed
    {
        $dto = is_string($dto) && class_exists($dto) ? new $dto() : $dto;

        if($dto instanceof WbOrderClientInterface)
        {
            return parent::getDto($dto);
        }

        throw new InvalidArgumentException(sprintf('Class %s interface error', $dto::class));
    }


    public function setEntity($dto): mixed
    {

        if($dto instanceof WbOrderClientInterface || $dto instanceof self)
        {
            if($dto instanceof WbOrderClientInterface)
            {
                if(
                    empty($dto->getUsername()) &&
                    empty($dto->getPhone()) &&
                    empty($dto->getEmail()?->getValue()) &&
                    empty($dto->getAddress())
                )
                {
                    return false;
                }
            }

            return parent::setEntity($dto);
        }

        throw new InvalidArgumentException(sprintf('Class %s interface error', $dto::class));
    }


}