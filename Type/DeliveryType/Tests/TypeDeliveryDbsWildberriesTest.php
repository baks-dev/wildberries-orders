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

namespace BaksDev\Wildberries\Orders\Type\DeliveryType\Tests;

use BaksDev\Core\Doctrine\DBALQueryBuilder;
use BaksDev\Delivery\Type\Id\DeliveryUid;
use BaksDev\Wildberries\Orders\Type\DeliveryType\TypeDeliveryDbsWildberries;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DependencyInjection\Attribute\When;


/**
 * @group wildberries-orders
 * @group wildberries-orders-type-delivery
 */
#[When(env: 'test')]
class TypeDeliveryDbsWildberriesTest extends KernelTestCase
{
    public function testUseCase(): void
    {
        $DeliveryUid = new DeliveryUid(TypeDeliveryDbsWildberries::TYPE);
        self::assertTrue($DeliveryUid->equals(new DeliveryUid(TypeDeliveryDbsWildberries::TYPE)));

        self::assertTrue($DeliveryUid->equals(TypeDeliveryDbsWildberries::class));
        self::assertTrue($DeliveryUid->equals(TypeDeliveryDbsWildberries::TYPE));

        self::assertTrue($DeliveryUid->equals(new DeliveryUid(TypeDeliveryDbsWildberries::class)));
        self::assertTrue($DeliveryUid->equals(new DeliveryUid(TypeDeliveryDbsWildberries::TYPE)));

    }
}