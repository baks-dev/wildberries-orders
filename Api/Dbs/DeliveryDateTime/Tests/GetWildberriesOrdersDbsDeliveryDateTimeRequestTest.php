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

namespace BaksDev\Wildberries\Orders\Api\Dbs\DeliveryDateTime\Tests;

use BaksDev\Users\Profile\UserProfile\Type\Id\UserProfileUid;
use BaksDev\Wildberries\Orders\Api\Dbs\DeliveryDateTime\GetWildberriesOrdersDbsDeliveryDateTimeRequest;
use BaksDev\Wildberries\Orders\Api\Dbs\DeliveryDateTime\WildberriesOrdersDbsDeliveryDateTimeDTO;
use BaksDev\Wildberries\Orders\UseCase\New\WildberriesNewOrderHandler;
use BaksDev\Wildberries\Type\Authorization\WbAuthorizationToken;
use PHPUnit\Framework\Attributes\Group;
use ReflectionClass;
use ReflectionMethod;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DependencyInjection\Attribute\When;

#[Group('wildberries-orders')]
#[When(env: 'test')]
class GetWildberriesOrdersDbsDeliveryDateTimeRequestTest extends KernelTestCase
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

    public function testUseCase(): void
    {
        self::assertTrue(true);

        /** @var GetWildberriesOrdersDbsDeliveryDateTimeRequest $GetWildberriesOrdersDbsDeliveryDateTimeRequest */
        $GetWildberriesOrdersDbsDeliveryDateTimeRequest = self::getContainer()->get(GetWildberriesOrdersDbsDeliveryDateTimeRequest::class);
        $GetWildberriesOrdersDbsDeliveryDateTimeRequest->TokenHttpClient(self::$Authorization);

        /** @var WildberriesNewOrderHandler $WildberriesOrderHandler */

        $WildberriesOrdersDbsDeliveryDateTimeDTO = $GetWildberriesOrdersDbsDeliveryDateTimeRequest
            ->find('W-4367157483');

        if($WildberriesOrdersDbsDeliveryDateTimeDTO instanceof WildberriesOrdersDbsDeliveryDateTimeDTO)
        {
            // Вызываем все геттеры
            $reflectionClass = new ReflectionClass(WildberriesOrdersDbsDeliveryDateTimeDTO::class);
            $methods = $reflectionClass->getMethods(ReflectionMethod::IS_PUBLIC);

            foreach($methods as $method)
            {
                // Методы без аргументов
                if($method->getNumberOfParameters() === 0)
                {
                    // Вызываем метод
                    $data = $method->invoke($WildberriesOrdersDbsDeliveryDateTimeDTO);
                    //dump($data);
                }
            }
        }

    }
}