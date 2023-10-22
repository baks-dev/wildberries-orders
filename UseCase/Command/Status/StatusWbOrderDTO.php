<?php
/*
 *  Copyright 2022.  Baks.dev <admin@baks.dev>
 *
 *  Licensed under the Apache License, Version 2.0 (the "License");
 *  you may not use this file except in compliance with the License.
 *  You may obtain a copy of the License at
 *
 *  http://www.apache.org/licenses/LICENSE-2.0
 *
 *  Unless required by applicable law or agreed to in writing, software
 *  distributed under the License is distributed on an "AS IS" BASIS,
 *  WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 *  See the License for the specific language governing permissions and
 *   limitations under the License.
 *
 */

namespace BaksDev\Wildberries\Orders\UseCase\Command\Status;


use BaksDev\Users\Profile\UserProfile\Type\Id\UserProfileUid;
use BaksDev\Wildberries\Orders\Entity\Event\WbOrdersEventInterface;
use BaksDev\Wildberries\Orders\Type\Event\WbOrdersEventUid;
use BaksDev\Wildberries\Orders\Type\OrderStatus\Status\Collection\WbOrderStatusInterface;
use BaksDev\Wildberries\Orders\Type\OrderStatus\Status\WbOrderStatusCancel;
use BaksDev\Wildberries\Orders\Type\OrderStatus\WbOrderStatus;
use BaksDev\Wildberries\Orders\Type\WildberriesStatus\Status\Collection\WildberriesStatusInterface;
use BaksDev\Wildberries\Orders\Type\WildberriesStatus\Status\WildberriesStatusCanceled;
use BaksDev\Wildberries\Orders\Type\WildberriesStatus\Status\WildberriesStatusCanceledClient;
use BaksDev\Wildberries\Orders\Type\WildberriesStatus\WildberriesStatus;
use Symfony\Component\Validator\Constraints as Assert;

/** @see WbOrdersEvent */
final class StatusWbOrderDTO implements WbOrdersEventInterface
{
    /**
     * Идентификатор события
     */
    #[Assert\Uuid]
    #[Assert\NotBlank]
    private readonly WbOrdersEventUid $id;

    /**
     * Профиль пользователя
     */
    #[Assert\NotBlank]
    #[Assert\Uuid]
    private readonly UserProfileUid $profile;

    /**
     * Статус сборочного задания
     */
    #[Assert\NotBlank]
    private WbOrderStatus $status;

    /**
     * Внутренний статус Wildberries
     */
    #[Assert\NotBlank]
    private WildberriesStatus $wildberries;


    /**
     * Идентификатор события
     */

    public function getEvent(): WbOrdersEventUid
    {
        return $this->id;
    }

    /**
     * Профиль пользователя
     */

    public function getProfile(): UserProfileUid
    {
        return $this->profile;
    }



    /**
     * Статус сборочного задания
     */

    public function getStatus(): WbOrderStatus
    {
        return $this->status;
    }

    public function setStatus(mixed $status): void
    {
        $this->status = new WbOrderStatus($status);
    }

    /**
     * Внутренний статус Wildberries
     */
    public function getWildberries(): WildberriesStatus
    {
        return $this->wildberries;
    }

    public function setWildberries(mixed $wildberries): void
    {
        $status = new WildberriesStatus($wildberries);

        $this->wildberries = $status;

        /** Делаем отмену заказа, если отмена клиентом или отмена сборочного задания */
        if(
            $status->getWildberriesStatus() instanceof WildberriesStatusCanceledClient ||
            $status->getWildberriesStatus() instanceof WildberriesStatusCanceled
        )
        {
            $this->setStatus(WbOrderStatusCancel::class);
        }
    }

}