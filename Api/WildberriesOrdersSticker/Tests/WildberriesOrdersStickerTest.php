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

namespace BaksDev\Wildberries\Orders\Api\WildberriesOrdersSticker\Tests;

use BaksDev\Users\Profile\UserProfile\Type\Id\UserProfileUid;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DependencyInjection\Attribute\When;

#[Group('wildberries-orders')]
#[When(env: 'test')]
final class WildberriesOrdersStickerTest extends KernelTestCase
{

    private static $tocken;

    public static function setUpBeforeClass(): void
    {
        self::$tocken = $_SERVER['TEST_WILDBERRIES_TOKEN'];
    }

    public function testUseCase(): void
    {

        self::assertNotNull(self::$tocken);

        //        /** @var WildberriesOrdersSticker $WildberriesOrdersSticker */
        //        $WildberriesOrdersSticker = self::getContainer()->get(WildberriesOrdersSticker::class);
        //
        //        $WildberriesOrdersStickerDTO = $WildberriesOrdersSticker
        //            ->profile(new UserProfileUid())
        //            ->addOrder(1735346346)
        //            ->getOrderSticker();
        //
        //        self::assertNotNull($WildberriesOrdersStickerDTO->getSticker());
        //        self::assertEquals(1735346346, $WildberriesOrdersStickerDTO->getOrder());
        //        self::assertEquals('!uKEtQZVx', $WildberriesOrdersStickerDTO->getBarcode());
        //        self::assertEquals('231648 9753', $WildberriesOrdersStickerDTO->getPart());
    }
}