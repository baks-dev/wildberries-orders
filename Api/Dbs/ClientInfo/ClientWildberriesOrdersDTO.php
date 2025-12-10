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

namespace BaksDev\Wildberries\Orders\Api\Dbs\ClientInfo;

use Symfony\Component\Validator\Constraints as Assert;

/** @see ClientWildberriesOrders */
final class ClientWildberriesOrdersDTO
{

    public function __construct(
        $orderID, // " => 4309055910
        private readonly int|string|null $replacementPhone, // " => "79295413398"
        private readonly int|string|null $phone, // " => "+74957755501"
        private readonly int|string|null $phoneCode, // " => 1491153

        private readonly ?string $firstName, // " => "Константин"
        private readonly ?string $fullName, // " => ""
        private readonly ?array $additionalPhones, // " => []
        private readonly ?array $additionalPhoneCodes, // " => []

    ) {}

    /**
     * Подменный номер для связи с покупателем.
     * Пустое значение "" указывает, что номер еще не назначен
     */
    public function getPhone(): ?string
    {
        return '+'.str_replace('+', '', $this->replacementPhone);
    }

    /** Имя покупателя */
    public function getContactName(): ?string
    {
        return empty($this->fullName) ? $this->firstName : $this->fullName;
    }

    /** Резервный подменный номер телефона для связи с покупателем + добавочный код  */
    public function getPhoneReserve(): ?string
    {
        return $this->phone.($this->phoneCode ? ' доб. '.$this->phoneCode : '');
    }


    /** Дополнительные добавочные номера телефонов для связи с покупателем и проверочные коды  */

    public function _getAdditionalPhones(): ?array
    {
        return $this->additionalPhones;
    }

    public function _getAdditionalPhoneCodes(): ?array
    {
        return $this->additionalPhoneCodes;
    }

}