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

declare(strict_types=1);

namespace BaksDev\Wildberries\Orders\Type\OrderStatus\Tests;

use BaksDev\Orders\Order\Type\Id\OrderUid;
use BaksDev\Wildberries\Orders\Type\OrderStatus\Status\Collection\WbOrderStatusCollection;
use BaksDev\Wildberries\Orders\Type\OrderStatus\WbOrderStatus;
use BaksDev\Wildberries\Orders\Type\OrderStatus\WbOrderStatusType;
use BaksDev\Wildberries\Orders\Type\WildberriesStatus\Status\Collection\WildberriesStatusInterface;
use BaksDev\Wildberries\Package\Entity\Package\Event\WbPackageEvent;
use BaksDev\Wildberries\Package\Entity\Package\Orders\WbPackageOrder;
use BaksDev\Wildberries\Package\Entity\Package\WbPackage;
use BaksDev\Wildberries\Package\Entity\Supply\Const\WbSupplyConst;
use BaksDev\Wildberries\Package\Type\Package\Id\WbPackageUid;
use BaksDev\Wildberries\Package\Type\Package\Status\WbPackageStatus\Collection\WbPackageStatusCollection;
use BaksDev\Wildberries\Package\Type\Package\Status\WbPackageStatus\Collection\WbPackageStatusInterface;
use BaksDev\Wildberries\Package\Type\Package\Status\WbPackageStatus\WbPackageStatusAdd;
use BaksDev\Wildberries\Package\Type\Package\Status\WbPackageStatus\WbPackageStatusNew;
use BaksDev\Wildberries\Package\Type\Supply\Id\WbSupplyUid;
use BaksDev\Wildberries\Package\Type\Supply\Status\WbSupplyStatus;
use BaksDev\Wildberries\Package\Type\Supply\Status\WbSupplyStatus\Collection\WbSupplyStatusCollection;
use BaksDev\Wildberries\Package\Type\Supply\Status\WbSupplyStatusType;
use BaksDev\Wildberries\Package\UseCase\Package\OrderStatus\UpdatePackageOrderStatusDTO;
use BaksDev\Wildberries\Package\UseCase\Package\OrderStatus\UpdatePackageOrderStatusHandler;
use BaksDev\Wildberries\Package\UseCase\Package\Pack\Orders\WbPackageOrderDTO;
use BaksDev\Wildberries\Package\UseCase\Package\Pack\Supply\WbPackageSupplyDTO;
use BaksDev\Wildberries\Package\UseCase\Package\Pack\WbPackageDTO;
use BaksDev\Wildberries\Package\UseCase\Package\Pack\WbPackageHandler;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DependencyInjection\Attribute\When;
use function PHPUnit\Framework\assertTrue;

/**
 * @group wildberries-orders
 */
#[When(env: 'test')]
final class WbOrdersStatusTest extends KernelTestCase
{
    public function testUseCase(): void
    {
        /** @var WbOrderStatusCollection $WbOrderStatusCollection */
        $WbOrderStatusCollection = self::getContainer()->get(WbOrderStatusCollection::class);

        /** @var WildberriesStatusInterface $case */
        foreach($WbOrderStatusCollection->cases() as $case)
        {
            $WbOrderStatus = new WbOrderStatus($case->getValue());

            self::assertTrue($WbOrderStatus->equals($case::class)); // немспейс интерфейса
            self::assertTrue($WbOrderStatus->equals($case)); // объект интерфейса
            self::assertTrue($WbOrderStatus->equals($case->getValue())); // срока
            self::assertTrue($WbOrderStatus->equals($WbOrderStatus)); // объект класса


            $WbOrderStatusType = new WbOrderStatusType();
            $platform = $this->getMockForAbstractClass(AbstractPlatform::class);

            $convertToDatabase = $WbOrderStatusType->convertToDatabaseValue($WbOrderStatus, $platform);
            self::assertEquals($WbOrderStatus->getWbOrderStatusValue(), $convertToDatabase);

            $convertToPHP = $WbOrderStatusType->convertToPHPValue($convertToDatabase, $platform);
            self::assertInstanceOf(WbOrderStatus::class, $convertToPHP);
            self::assertEquals($case, $convertToPHP->getWbOrderStatus());

        }

        self::assertTrue(true);

    }
}