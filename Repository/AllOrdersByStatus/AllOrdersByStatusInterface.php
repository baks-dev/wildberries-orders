<?php

namespace BaksDev\Wildberries\Orders\Repository\AllOrdersByStatus;

use BaksDev\Users\Profile\UserProfile\Type\Id\UserProfileUid;
use BaksDev\Wildberries\Orders\Type\WildberriesStatus\Status\Collection\WildberriesStatusInterface;
use BaksDev\Wildberries\Orders\Type\WildberriesStatus\WildberriesStatus;

interface AllOrdersByStatusInterface
{

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
    ): ?array;



    /**
     * Метод возвращает всю продукцию в заказах профиля со статусом NEW для обновления статистики
     */
    public function allWildberriesNewOrderProducts(UserProfileUid $profile): ?array;
}