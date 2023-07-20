<?php

declare(strict_types=1);

namespace BaksDev\Wildberries\Orders\Repository\AllOrdersByStatus;

//use App\Module\User\Profile\UserProfile\Type\Id\UserProfileUid;
//use App\Module\Wildberries\Orders\Order\Entity as EntityWbOrders;
//use App\Module\Wildberries\Orders\Order\Type\Status\WbOrderStatus;
//use App\Module\Wildberries\Orders\Order\Type\Status\WbOrderStatusEnum;
use BaksDev\Users\Profile\UserProfile\Type\Id\UserProfileUid;
use BaksDev\Wildberries\Orders\Entity\Event\WbOrdersEvent;
use BaksDev\Wildberries\Orders\Entity\WbOrders;
use BaksDev\Wildberries\Orders\Type\OrderStatus\Status\Collection\WbOrderStatusInterface;
use BaksDev\Wildberries\Orders\Type\OrderStatus\WbOrderStatus;
use BaksDev\Wildberries\Orders\Type\WildberriesStatus\Status\Collection\WildberriesStatusInterface;
use BaksDev\Wildberries\Orders\Type\WildberriesStatus\WildberriesStatus;
use Doctrine\ORM\EntityManagerInterface;

final class AllOrdersByStatus implements AllOrdersByStatusInterface
{
    private EntityManagerInterface $entityManager;


    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }


    /**
     * Возвращает массив идентификаторов событий, в качестве ключа которого выступает идентификатор заказа Wildberries <br>
     * Пример: [ 1234567890 => af8f8777-5a9b-408c-ae45-387f1cf5666c ]
     */
    public function getWbOrdersEventResult(UserProfileUid $profile, WbOrderStatus|WbOrderStatusInterface $status): mixed
    {
        $qb = $this->entityManager->createQueryBuilder();
        $qb->select('orders.ord AS order_wb');
        $qb->addSelect('event');

        $qb->from(WbOrders::class, 'orders', 'orders.ord');
        $qb->join(
            WbOrdersEvent::class,
            'event',
            'WITH',
            'event.id = orders.event AND event.status = :status AND event.profile = :profile'
        );
        $qb->setParameter('status', new WbOrderStatus($status), WbOrderStatus::TYPE);
        $qb->setParameter('profile', $profile, UserProfileUid::TYPE);

        return $qb->getQuery()->getResult();
    }


    /**
     * Возвращает ассоциативный массив с ключами в качестве ключа которого выступает идентификатор заказа Wildberries <br>
     *
     * 1234567890 => [
     *  "order_id" => "55bb533e-c91f-4c7b-8d36-0c2d1bb4bfb2" <br>
     *  "order_event" => "af8f8777-5a9b-408c-ae45-387f1cf5666c" <br>
     *  "order_wb" => 1234567890 <br>
     *  "event_profile" => "02c729dc-9bf1-4736-9c18-9e4665d55b71" <br>
     *  "event_barcode" => "02c729dc-9bf1-4736-9c18-9e4665d55b71" <br>
     *  "event_created" => "02c729dc-9bf1-4736-9c18-9e4665d55b71" <br>
     *  "event_status" => "02c729dc-9bf1-4736-9c18-9e4665d55b71" <br>
     * ]
     */

    public function fetchAllOrdersByWildberriesStatusAssociativeIndexed(
        UserProfileUid $profile,
        WildberriesStatus|WildberriesStatusInterface $status
    ): ?array
    {
        $qb = $this->entityManager->getConnection()->createQueryBuilder();

        $qb->addSelect('orders.ord');

        $qb->addSelect('orders.id AS order_id');
        $qb->addSelect('orders.event AS order_event');
        $qb->addSelect('orders.ord AS order_wb');

        $qb->from(WbOrders::TABLE, 'orders');


        $qb->addSelect('event.profile AS event_profile');
        $qb->addSelect('event.barcode AS event_barcode');
        $qb->addSelect('event.created AS event_created');
        $qb->addSelect('event.status AS event_status');
        $qb->addSelect('event.wildberries AS event_wildberries');

        $qb->join('orders',
            WbOrdersEvent::TABLE,
            'event',
            'event.id = orders.event AND event.wildberries = :status AND event.profile = :profile'
        );

        $status = $status instanceof WildberriesStatusInterface ? new WildberriesStatus($status) : $status;

        $qb->setParameter('status', $status, WildberriesStatus::TYPE);
        $qb->setParameter('profile', $profile, UserProfileUid::TYPE);

        return $qb->fetchAllAssociativeIndexed();
    }


}