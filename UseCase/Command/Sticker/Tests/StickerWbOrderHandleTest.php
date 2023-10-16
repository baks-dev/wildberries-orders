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

namespace BaksDev\Wildberries\Orders\UseCase\Command\Sticker\Tests;

use BaksDev\Orders\Order\Type\Id\OrderUid;
use BaksDev\Users\Profile\UserProfile\Type\Id\UserProfileUid;
use BaksDev\Wildberries\Orders\Entity\WbOrders;
use BaksDev\Wildberries\Orders\Repository\WbOrdersById\WbOrdersByIdInterface;
use BaksDev\Wildberries\Orders\Type\Event\WbOrdersEventUid;
use BaksDev\Wildberries\Orders\UseCase\Command\Status\Tests\StatusWbOrderHandleTest;
use BaksDev\Wildberries\Orders\UseCase\Command\Sticker\StickerWbOrderDTO;
use BaksDev\Wildberries\Orders\UseCase\Command\Sticker\StickerWbOrderHandler;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DependencyInjection\Attribute\When;

/**
 * @group wildberries-orders
 *
 * @depends BaksDev\Wildberries\Orders\UseCase\Command\Status\Tests\StatusWbOrderHandleTest::class
 *
 * @see     StatusWbOrderHandleTest
 */
#[When(env: 'test')]
final class StickerWbOrderHandleTest extends KernelTestCase
{

    public function testUseCase(): void
    {
        /** @var WbOrdersByIdInterface $WbOrdersByIdInterface */
        $WbOrdersByIdInterface = self::getContainer()->get(WbOrdersByIdInterface::class);
        $WbOrdersEvent = $WbOrdersByIdInterface->getWbOrderByOrderUidOrNullResult(new  OrderUid());

        /** @var StickerWbOrderDTO $WbOrderDTO */
        $WbOrderDTO = $WbOrdersEvent->getDto(StickerWbOrderDTO::class);
        self::assertNotEquals(WbOrdersEventUid::TEST, (string) $WbOrderDTO->getEvent());
        self::assertEquals(UserProfileUid::TEST, (string) $WbOrderDTO->getProfile());


        $StickerWbOrderDTO = $WbOrderDTO->getSticker();

        $StickerWbOrderDTO->setSticker('LEWxOnXtpO');
        self::assertEquals('LEWxOnXtpO',  $StickerWbOrderDTO->getSticker());

        $StickerWbOrderDTO->setPart('FlmqVMgBKx');
        self::assertEquals('FlmqVMgBKx',  $StickerWbOrderDTO->getPart());


        /** @var StickerWbOrderHandler $StickerWbOrderHandler */
        $StickerWbOrderHandler = self::getContainer()->get(StickerWbOrderHandler::class);
        $handle = $StickerWbOrderHandler->handle($WbOrderDTO);

        self::assertTrue(($handle instanceof WbOrders), $handle.': Ошибка WbOrders');

    }

}