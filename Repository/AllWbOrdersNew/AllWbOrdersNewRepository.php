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

namespace BaksDev\Wildberries\Orders\Repository\AllWbOrdersNew;

use BaksDev\Core\Doctrine\DBALQueryBuilder;
use BaksDev\Orders\Order\Entity\Event\OrderEvent;
use BaksDev\Orders\Order\Entity\Invariable\OrderInvariable;
use BaksDev\Orders\Order\Entity\Order;
use BaksDev\Orders\Order\Entity\User\Delivery\OrderDelivery;
use BaksDev\Orders\Order\Entity\User\OrderUser;
use BaksDev\Orders\Order\Type\Id\OrderUid;
use BaksDev\Orders\Order\Type\Status\OrderStatus\OrderStatusNew;
use BaksDev\Orders\Order\Type\Status\OrderStatus\OrderStatusPackage;
use BaksDev\Orders\Order\Type\Status\OrderStatus\OrderStatusUnpaid;
use BaksDev\Users\Profile\UserProfile\Entity\UserProfile;
use BaksDev\Users\Profile\UserProfile\Type\Id\UserProfileUid;
use BaksDev\Wildberries\Orders\Type\DeliveryType\TypeDeliveryDbsWildberries;
use BaksDev\Wildberries\Orders\Type\DeliveryType\TypeDeliveryFbsWildberries;
use Doctrine\DBAL\ArrayParameterType;
use Generator;
use InvalidArgumentException;


final readonly class AllWbOrdersNewRepository implements AllWbOrdersNewInterface
{
    private UserProfileUid|false $profile;

    public function __construct(private DBALQueryBuilder $DBALQueryBuilder) {}

    public function forProfile(UserProfile|UserProfileUid|string $profile): self
    {
        if(is_string($profile))
        {
            $profile = new UserProfileUid($profile);
        }

        if($profile instanceof UserProfile)
        {
            $profile = $profile->getId();
        }

        $this->profile = $profile;

        return $this;
    }

    /**
     * Метод возвращает идентификаторы системных заказов и идентификаторы заказа Wildberries качестве атрибута
     */
    public function findAll(): Generator|false
    {
        if(false === ($this->profile instanceof UserProfileUid))
        {
            throw new InvalidArgumentException('Invalid Argument UserProfile');
        }

        $dbal = $this->DBALQueryBuilder->createQueryBuilder(self::class);

        $dbal
            ->select('ord.id AS value')
            ->from(Order::class, 'ord');

        $dbal
            ->addSelect('invariable.number AS attr')
            ->join(
                'ord',
                OrderInvariable::class,
                'invariable',
                'invariable.main = ord.id AND invariable.profile = :profile',
            )
            ->setParameter(
                key: 'profile',
                value: $this->profile,
                type: UserProfileUid::TYPE
            );

        $dbal
            ->join(
                'ord',
                OrderEvent::class,
                'event',
                'event.id = ord.event AND event.status = :status'
            )
            ->setParameter(
                key: 'status',
                value: [OrderStatusNew::STATUS, OrderStatusUnpaid::STATUS, OrderStatusPackage::STATUS],
                type: ArrayParameterType::STRING
            );

        $dbal
            ->leftJoin(
                'event',
                OrderUser::class,
                'usr',
                'usr.event = event.id'
            );

        $dbal
            ->join(
                'usr',
                OrderDelivery::class,
                'order_delivery',
                'order_delivery.usr = usr.id AND 
                    order_delivery.delivery IN (:delivery)
                '
            )->setParameter(
                key: 'delivery',
                value: [TypeDeliveryDbsWildberries::TYPE, TypeDeliveryFbsWildberries::TYPE],
                type: ArrayParameterType::STRING
            );


        return $dbal->fetchAllHydrate(OrderUid::class);
    }
}