<?php
/*
 *  Copyright 2026.  Baks.dev <admin@baks.dev>
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

namespace BaksDev\Wildberries\Orders\Api\ClientInfo;

use Symfony\Component\Validator\Constraints as Assert;

/** @see ClientWildberriesOrders */
final class ClientWildberriesOrdersDTO
{

    private int $orderID;

    private string|int|null $phone;

    private string|int|null $phoneCode;

    private ?string $firstName;

    private ?string $fullName;

    private ?string $replacementPhone;

    private ?array $additionalPhones;

    private ?array $additionalPhoneCodes;

    public function __construct(array $client)
    {
        $this->orderID = $client['orderID'];

        $this->firstName = $client['firstName'];
        $this->fullName = isset($client['fullName']) ? $client['fullName'] : null;

        $this->phoneCode = $client['phoneCode'];
        $this->phone = $client['phone'];

        $this->replacementPhone = isset($client['replacementPhone']) ? $client['replacementPhone'] : null;

        /** Доп. номера */
        $this->additionalPhones = isset($client['additionalPhones']) ? $client['additionalPhones'] : null;
        $this->additionalPhoneCodes = isset($client['additionalPhoneCodes']) ? $client['additionalPhoneCodes'] : null;

    }

    /**
     * Подменный номер для связи с покупателем.
     * Пустое значение "" указывает, что номер еще не назначен
     */
    public function getPhone(): ?string
    {
        return '+'.str_replace('+', '', $this->phone);
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