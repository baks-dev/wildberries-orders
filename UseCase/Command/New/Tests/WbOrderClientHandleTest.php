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

namespace BaksDev\Wildberries\Orders\UseCase\Command\New\Tests;

use BaksDev\Orders\Order\Type\Id\OrderUid;
use BaksDev\Users\Profile\UserProfile\Type\Id\UserProfileUid;
use BaksDev\Wildberries\Orders\Entity\Client\WbOrderClient;
use BaksDev\Wildberries\Orders\Entity\Event\WbOrdersEvent;
use BaksDev\Wildberries\Orders\Entity\WbOrders;
use BaksDev\Wildberries\Orders\Type\Email\ClientEmail;
use BaksDev\Wildberries\Orders\Type\OrderStatus\Status\WbOrderStatusNew;
use BaksDev\Wildberries\Orders\Type\OrderStatus\WbOrderStatus;
use BaksDev\Wildberries\Orders\Type\WildberriesStatus\Status\WildberriesStatusWaiting;
use BaksDev\Wildberries\Orders\Type\WildberriesStatus\WildberriesStatus;
use BaksDev\Wildberries\Orders\UseCase\Command\New\CreateWbOrderDTO;
use BaksDev\Wildberries\Orders\UseCase\Command\New\CreateWbOrderHandler;
use BaksDev\Wildberries\Package\Entity\Supply\Event\WbSupplyEvent;
use BaksDev\Wildberries\Package\Entity\Supply\WbSupply;
use BaksDev\Wildberries\Package\Type\Supply\Id\WbSupplyUid;
use BaksDev\Wildberries\Package\Type\Supply\Status\WbSupplyStatus\Collection\WbSupplyStatusCollection;
use BaksDev\Wildberries\Package\Type\Supply\Status\WbSupplyStatus\WbSupplyStatusNew;
use BaksDev\Wildberries\Package\UseCase\Supply\New\Const\WbSupplyConstDTO;
use BaksDev\Wildberries\Package\UseCase\Supply\New\WbSupplyNewDTO;
use BaksDev\Wildberries\Package\UseCase\Supply\New\WbSupplyNewHandler;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DependencyInjection\Attribute\When;
use BaksDev\Wildberries\Orders\UseCase\Command\New\Tests\WbOrderNewHandleTest;

/**
 * @group wildberries-orders
 *
 * @depends BaksDev\Wildberries\Orders\UseCase\Command\New\Tests\WbOrderNewHandleTest::class
 *
 * @see     WbOrderNewHandleTest
 */
#[When(env: 'test')]
final class WbOrderClientHandleTest extends KernelTestCase
{
    public static function setUpBeforeClass(): void
    {
        /** @var EntityManagerInterface $em */
        $em = self::getContainer()->get(EntityManagerInterface::class);

        $WbOrders = $em->getRepository(WbOrders::class)
            ->findOneBy(['id' => OrderUid::TEST]);

        if($WbOrders)
        {
            $em->remove($WbOrders);
        }

        /* WbBarcodeEvent */

        $WbSupplyEventCollection = $em->getRepository(WbOrdersEvent::class)
            ->findBy(['main' => OrderUid::TEST]);

        foreach($WbSupplyEventCollection as $remove)
        {
            $em->remove($remove);
        }

        $em->flush();
    }


    public function testUseCase(): void
    {
        $UserProfileUid = new UserProfileUid();
        $WbOrderDTO = new CreateWbOrderDTO(profile: $UserProfileUid, wbOrder: 12345);
        self::assertSame($UserProfileUid, $WbOrderDTO->getProfile());
        self::assertEquals(12345, $WbOrderDTO->getWbOrder());


        $OrderUid = new OrderUid();
        $WbOrderDTO->setMain($OrderUid);
        self::assertSame($OrderUid, $WbOrderDTO->getMain());


        $WbOrderDTO->setStatus(new WbOrderStatus(WbOrderStatusNew::class));
        self::assertEquals(WbOrderStatusNew::STATUS, (string) $WbOrderDTO->getStatus());

        $WbOrderDTO->setBarcode('zkddxXEZZU');
        self::assertEquals('zkddxXEZZU', $WbOrderDTO->getBarcode());


        $WbOrderDTO->setWildberries(new WildberriesStatus(WildberriesStatusWaiting::class));
        self::assertEquals(WildberriesStatusWaiting::STATUS, (string) $WbOrderDTO->getWildberries());

        $WbOrderDTO->setCreated(new DateTimeImmutable());


        $WbOrderClientDTO = $WbOrderDTO->getClient();

        $ClientEmail = new ClientEmail('test@test.test');
        $WbOrderClientDTO->setEmail($ClientEmail);
        self::assertSame($ClientEmail, $WbOrderClientDTO->getEmail());


        $WbOrderClientDTO->setUsername('username');
        self::assertEquals('username', $WbOrderClientDTO->getUsername());

        $WbOrderClientDTO->setPhone('phone');
        self::assertEquals('phone', $WbOrderClientDTO->getPhone());

        $WbOrderClientDTO->setAddress('address');
        self::assertEquals('address', $WbOrderClientDTO->getAddress());


        /** @var CreateWbOrderHandler $WbOrderHandler */
        $WbOrderHandler = self::getContainer()->get(CreateWbOrderHandler::class);
        $handle = $WbOrderHandler->handle($WbOrderDTO);

        self::assertTrue(($handle instanceof WbOrders), $handle.': Ошибка WbOrders');

    }

    public function testComplete(): void
    {
        /** @var EntityManagerInterface $em */
        $em = self::getContainer()->get(EntityManagerInterface::class);

        $WbSupply = $em->getRepository(WbOrders::class)
            ->find(OrderUid::TEST);
        self::assertNotNull($WbSupply);

        $WbOrderClient = $em->getRepository(WbOrderClient::class)
            ->find(OrderUid::TEST);
        self::assertNotNull($WbOrderClient);

    }
}