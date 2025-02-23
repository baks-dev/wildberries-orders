<?php
/*
 *  Copyright 2024.  Baks.dev <admin@baks.dev>
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