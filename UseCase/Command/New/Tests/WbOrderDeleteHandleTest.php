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
use BaksDev\Wildberries\Orders\Entity\Event\WbOrdersEvent;
use BaksDev\Wildberries\Orders\Entity\WbOrders;
use BaksDev\Wildberries\Orders\UseCase\Command\Sticker\Tests\StickerWbOrderHandleTest;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DependencyInjection\Attribute\When;

/**
 * @group wildberries-orders
 *
 * @depends BaksDev\Wildberries\Orders\UseCase\Command\Sticker\Tests\StickerWbOrderHandleTest::class
 *
 * @see     StickerWbOrderHandleTest
 */
#[When(env: 'test')]
final class WbOrderDeleteHandleTest extends KernelTestCase
{

    public static function tearDownAfterClass(): void
    {
        /** @var EntityManagerInterface $em */
        $em = self::getContainer()->get(EntityManagerInterface::class);

        $WbOrders = $em->getRepository(WbOrders::class)
            ->findOneBy(['id' => OrderUid::TEST]);

        if($WbOrders)
        {
            $em->remove($WbOrders);
        }

        $WbSupplyEventCollection = $em->getRepository(WbOrdersEvent::class)
            ->findBy(['main' => OrderUid::TEST]);

        foreach($WbSupplyEventCollection as $remove)
        {
            $em->remove($remove);
        }

        $em->flush();
        $em->clear();
        //$em->close();
    }


    public function testComplete(): void
    {
        self::assertTrue(true);
    }
}