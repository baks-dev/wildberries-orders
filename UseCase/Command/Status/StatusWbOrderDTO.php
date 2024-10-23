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

namespace BaksDev\Wildberries\Orders\UseCase\Command\Status;


use BaksDev\Users\Profile\UserProfile\Type\Id\UserProfileUid;
use BaksDev\Wildberries\Orders\Entity\Event\WbOrdersEventInterface;
use BaksDev\Wildberries\Orders\Type\Event\WbOrdersEventUid;
use BaksDev\Wildberries\Orders\Type\OrderStatus\Status\WbOrderStatusCancel;
use BaksDev\Wildberries\Orders\Type\OrderStatus\WbOrderStatus;
use BaksDev\Wildberries\Orders\Type\WildberriesStatus\Status\WildberriesStatusCanceled;
use BaksDev\Wildberries\Orders\Type\WildberriesStatus\Status\WildberriesStatusCanceledClient;
use BaksDev\Wildberries\Orders\Type\WildberriesStatus\Status\WildberriesStatusDeclinedClient;
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
            $status->getWildberriesStatus() instanceof WildberriesStatusCanceled ||
            $status->getWildberriesStatus() instanceof WildberriesStatusDeclinedClient
        )
        {
            $this->setStatus(WbOrderStatusCancel::class);
        }
    }

}