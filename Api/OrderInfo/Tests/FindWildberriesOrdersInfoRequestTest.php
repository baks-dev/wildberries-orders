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

namespace BaksDev\Wildberries\Orders\Api\OrderInfo\Tests;

use BaksDev\Users\Profile\UserProfile\Type\Id\UserProfileUid;
use BaksDev\Wildberries\Orders\Api\OrderInfo\FindWildberriesOrdersInfoRequest;
use BaksDev\Wildberries\Type\Authorization\WbAuthorizationToken;
use PHPUnit\Framework\Attributes\DependsOnClass;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DependencyInjection\Attribute\When;


#[Group('wildberries-orders')]
#[When(env: 'test')]
class FindWildberriesOrdersInfoRequestTest extends KernelTestCase
{
    private static WbAuthorizationToken $Authorization;

    public static function setUpBeforeClass(): void
    {
        /** @see .env.test */
        self::$Authorization = new WbAuthorizationToken(
            profile: new UserProfileUid($_SERVER['TEST_WILDBERRIES_PROFILE']),
            token: $_SERVER['TEST_WILDBERRIES_TOKEN'],
            warehouse: $_SERVER['TEST_WILDBERRIES_WAREHOUSE'] ?? null,
            percent: $_SERVER['TEST_WILDBERRIES_PERCENT'] ?? "0",
            card: $_SERVER['TEST_WILDBERRIES_CARD'] === "true" ?? false,
            stock: $_SERVER['TEST_WILDBERRIES_STOCK'] === "true" ?? false,
        );
    }

    public function testFindWildberriesOrdersInfoRequestRepository(): void
    {
        self::assertTrue(true);

        /** @var FindWildberriesOrdersInfoRequest $FindWildberriesOrdersInfoRequest */
        $FindWildberriesOrdersInfoRequest = self::getContainer()->get(FindWildberriesOrdersInfoRequest::class);
        $FindWildberriesOrdersInfoRequest->TokenHttpClient(self::$Authorization);

        $result = $FindWildberriesOrdersInfoRequest->findAll();

        dd($result); /* TODO: удалить !!! */

        if(false === $result || false === $result->valid())
        {
            return;
        }

        //        foreach($result as $FindWildberriesOrdersInfoRequestResult)
        //        {
        //            // Вызываем все геттеры
        //            $reflectionClass = new \ReflectionClass(FindWildberriesOrdersInfoRequestResult::class);
        //            $methods = $reflectionClass->getMethods(\ReflectionMethod::IS_PUBLIC);
        //
        //            foreach($methods as $method)
        //            {
        //                // Методы без аргументов
        //                if($method->getNumberOfParameters() === 0)
        //                {
        //                    // Вызываем метод
        //                    $data = $method->invoke($FindWildberriesOrdersInfoRequestResult);
        //                    // dump($data);
        //                }
        //            }
        //
        //            break;
        //        }

    }

}