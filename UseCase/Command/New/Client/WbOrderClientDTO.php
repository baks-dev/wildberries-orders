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

namespace BaksDev\Wildberries\Orders\UseCase\Command\New\Client;


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
    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(?string $username): void
    {
        $this->username = empty($username) ? null : $username;
    }

    /**
     * Контактный телефон
     */

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(?string $phone): void
    {
        $this->phone = empty($phone) ? null : $phone;
    }

    /**
     * Адрес клиента
     */

    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function setAddress(?string $address): void
    {
        $this->address = empty($address) ? null : $address;
    }

    /**
     * Email клиента
     */
    public function getEmail(): ?ClientEmail
    {
        return $this->email;
    }

    public function setEmail(?ClientEmail $email): void
    {
        $this->email = empty($email?->getValue()) ? null : $email;
    }

}

