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

declare(strict_types=1);

namespace BaksDev\Wildberries\Orders\UseCase\Command\Status\Tests;

use BaksDev\Orders\Order\Type\Id\OrderUid;
use BaksDev\Users\Profile\UserProfile\Type\Id\UserProfileUid;
use BaksDev\Wildberries\Orders\Entity\WbOrders;
use BaksDev\Wildberries\Orders\Repository\WbOrdersById\WbOrdersByIdInterface;
use BaksDev\Wildberries\Orders\Type\Event\WbOrdersEventUid;
use BaksDev\Wildberries\Orders\Type\OrderStatus\Status\WbOrderStatusConfirm;
use BaksDev\Wildberries\Orders\Type\OrderStatus\Status\WbOrderStatusNew;
use BaksDev\Wildberries\Orders\Type\OrderStatus\WbOrderStatus;
use BaksDev\Wildberries\Orders\Type\WildberriesStatus\Status\WildberriesStatusPickup;
use BaksDev\Wildberries\Orders\Type\WildberriesStatus\Status\WildberriesStatusWaiting;
use BaksDev\Wildberries\Orders\Type\WildberriesStatus\WildberriesStatus;
use BaksDev\Wildberries\Orders\UseCase\Command\New\CreateWbOrderHandler;
use BaksDev\Wildberries\Orders\UseCase\Command\Status\StatusWbOrderDTO;
use BaksDev\Wildberries\Orders\UseCase\Command\Status\StatusWbOrderHandler;
use BaksDev\Wildberries\Orders\UseCase\Command\Status\WbOrderDTO;
use BaksDev\Wildberries\Package\Entity\Supply\Event\WbSupplyEvent;
use BaksDev\Wildberries\Package\Entity\Supply\WbSupply;
use BaksDev\Wildberries\Package\Type\Supply\Id\WbSupplyUid;
use BaksDev\Wildberries\Package\Type\Supply\Status\WbSupplyStatus\Collection\WbSupplyStatusCollection;
use BaksDev\Wildberries\Package\Type\Supply\Status\WbSupplyStatus\WbSupplyStatusNew;
use BaksDev\Wildberries\Package\UseCase\Supply\New\Const\WbSupplyConstDTO;
use BaksDev\Wildberries\Package\UseCase\Supply\New\WbSupplyNewDTO;
use BaksDev\Wildberries\Package\UseCase\Supply\New\WbSupplyNewHandler;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DependencyInjection\Attribute\When;
use BaksDev\Wildberries\Orders\UseCase\Command\New\Tests\WbOrderClientHandleTest;

/**
 * @group wildberries-orders
 *
 * @depends BaksDev\Wildberries\Orders\UseCase\Command\New\Tests\WbOrderClientHandleTest::class
 *
 * @see     WbOrderClientHandleTest
 */
#[When(env: 'test')]
final class StatusWbOrderHandleTest extends KernelTestCase
{

    public function testUseCase(): void
    {

        /** @var WbOrdersByIdInterface $WbOrdersByIdInterface */
        $WbOrdersByIdInterface = self::getContainer()->get(WbOrdersByIdInterface::class);
        $WbOrdersEvent = $WbOrdersByIdInterface->getWbOrderByOrderUidOrNullResult(new  OrderUid());

        /** @var StatusWbOrderDTO $WbOrderDTO */
        $WbOrderDTO = $WbOrdersEvent->getDto(StatusWbOrderDTO::class);
        self::assertEquals( WbOrdersEventUid::TEST, (string) $WbOrderDTO->getEvent());
        self::assertEquals( UserProfileUid::TEST, (string) $WbOrderDTO->getProfile());

        self::assertEquals(WbOrderStatusNew::STATUS, (string) $WbOrderDTO->getStatus());
        $WbOrderDTO->setStatus(new WbOrderStatus(WbOrderStatusConfirm::class));

        self::assertEquals(WildberriesStatusWaiting::STATUS, (string) $WbOrderDTO->getWildberries());
        $WbOrderDTO->setWildberries(new WildberriesStatus(WildberriesStatusPickup::class));


        /** @var StatusWbOrderHandler $StatusWbOrderHandler */
        $StatusWbOrderHandler = self::getContainer()->get(StatusWbOrderHandler::class);
        $handle = $StatusWbOrderHandler->handle($WbOrderDTO);

        self::assertTrue(($handle instanceof WbOrders), $handle.': Ошибка WbOrders');

    }

}