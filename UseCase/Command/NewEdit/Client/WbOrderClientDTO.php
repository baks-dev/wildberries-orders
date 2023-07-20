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

namespace BaksDev\Wildberries\Orders\UseCase\Command\NewEdit\Client;

//use App\Module\Wildberries\Orders\Order\Entity\Client\WbOrderClientInterface;
//use App\Module\Wildberries\Orders\Order\Type\Email\ClientEmail;
//use App\Module\Wildberries\Orders\Order\Type\StatusClient\WbClientStatus;
//use App\Module\Wildberries\Orders\Order\Type\StatusClient\WbClientStatusEnum;
use BaksDev\Wildberries\Orders\Entity\Client\WbOrderClientInterface;
use BaksDev\Wildberries\Orders\Type\Email\ClientEmail;

/** @see WbOrderClient */
final class WbOrderClientDTO implements WbOrderClientInterface
{

    /**
     * ФИО клиента
     */
    private ?string $username = null;
    
    /**
     * Контактный телефон
     */
    private ?string $phone = null;
    
    /**
     * Адрес клиента
     */
    private ?string $address = null;
    
    /** 
     * Email клиента
     */
    private ?ClientEmail $email = null;


    /**
     * ФИО клиента
     */
    public function getUsername() : ?string
    {
        return $this->username;
    }

    public function setUsername(?string $username) : void
    {
        $this->username = empty($username) ? null : $username;
    }

    /**
     * Контактный телефон
     */

    public function getPhone() : ?string
    {
        return $this->phone;
    }

    public function setPhone(?string $phone) : void
    {
        $this->phone =  empty($phone) ? null : $phone;
    }

    /**
     * Адрес клиента
     */

    public function getAddress() : ?string
    {
        return $this->address;
    }

    public function setAddress(?string $address) : void
    {
        $this->address = empty($address) ? null : $address;
    }

    /**
     * Email клиента
     */
    public function getEmail() : ?ClientEmail
    {
        return $this->email;
    }

    public function setEmail(?ClientEmail $email) : void
    {
        $this->email = empty($email?->getValue()) ? null : $email;
    }

}

