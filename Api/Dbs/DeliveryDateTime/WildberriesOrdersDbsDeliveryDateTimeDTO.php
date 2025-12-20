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

namespace BaksDev\Wildberries\Orders\Api\Dbs\DeliveryDateTime;

use DateTimeImmutable;
use Symfony\Component\Validator\Constraints as Assert;

/** @see WildberriesOrdersDbsDeliveryDateTime */
final class WildberriesOrdersDbsDeliveryDateTimeDTO
{
    private DateTimeImmutable $date;

    private ?string $time = null;

    public function __construct(
        $dTimeFrom,
        $dTimeTo,
        $dTimeFromOld,
        $dTimeToOld,
        $dDateOld,
        $dDate,
        $id
    )
    {
        $this->date = new DateTimeImmutable($dDate); // "dDate": "2025-02-20",

        if(false === empty($dTimeFrom) && empty($dTimeTo))
        {
            $this->time = 'после '.$dTimeFrom;
        }

        if(empty($dTimeFrom) && false === empty($dTimeTo))
        {
            $this->time = 'до '.$dTimeTo;
        }

        if(false === empty($dTimeFrom) && false === empty($dTimeTo))
        {
            $this->time = 'c '.$dTimeFrom.' до '.$dTimeTo;
        }

    }

    public function getDate(): DateTimeImmutable
    {
        return $this->date;
    }

    public function getTime(): ?string
    {
        return $this->time;
    }
}