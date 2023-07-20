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

namespace BaksDev\Wildberries\Orders\Entity\Client;

use BaksDev\Core\Entity\EntityEvent;
use BaksDev\Wildberries\Orders\Entity\Event\WbOrdersEvent;
use BaksDev\Wildberries\Orders\Type\Email\ClientEmail;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use InvalidArgumentException;
use Symfony\Component\Validator\Constraints as Assert;

/* WbOrderClient */

#[ORM\Entity]
#[ORM\Table(name: 'wb_orders_client')]
class WbOrderClient extends EntityEvent
{
    public const TABLE = 'wb_orders_client';

    /**
     * Идентификатор события
     */
    #[Assert\NotBlank]
    #[ORM\Id]
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
    }


    public function getDto($dto): mixed
    {
        if($dto instanceof WbOrderClientInterface)
        {
            return parent::getDto($dto);
        }

        throw new InvalidArgumentException(sprintf('Class %s interface error', $dto::class));
    }


    public function setEntity($dto): mixed
    {

        if($dto instanceof WbOrderClientInterface)
        {
            if(
                empty($dto->getUsername()) &&
                empty($dto->getPhone()) &&
                empty($dto->getEmail()) &&
                empty($dto->getAddress())
            )
            {
                return false;
            }


            return parent::setEntity($dto);
        }

        throw new InvalidArgumentException(sprintf('Class %s interface error', $dto::class));
    }
}