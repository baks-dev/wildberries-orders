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

namespace BaksDev\Wildberries\Orders\UseCase\Command\Sticker;

//use App\Module\Orders\Order\Entity\Order;
//use App\Module\Orders\Order\Type\Id\OrderUid;
//use App\Module\User\Profile\UserProfile\Type\Id\UserProfileUid;
//use App\Module\Wildberries\Orders\Order\Entity\Client\WbOrderClient;
//use App\Module\Wildberries\Orders\Order\Entity\Event\WbOrdersEventInterface;
//use App\Module\Wildberries\Orders\Order\Type\Event\WbOrdersEventUid;
//use App\Module\Wildberries\Orders\Order\Type\Status\WbOrderStatus;
//use App\Module\Wildberries\Orders\Order\Type\Status\WbOrderStatusEnum;
use BaksDev\Orders\Order\Entity\Order;
use BaksDev\Orders\Order\Type\Id\OrderUid;
use BaksDev\Users\Profile\UserProfile\Type\Id\UserProfileUid;
use BaksDev\Wildberries\Orders\Entity\Event\WbOrdersEventInterface;
use BaksDev\Wildberries\Orders\Type\Event\WbOrdersEventUid;
use Symfony\Component\Validator\Constraints as Assert;

/** @see WbOrdersEvent */
final class StickerWbOrderDTO implements WbOrdersEventInterface
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
     * Стикер заказа
     */
    #[Assert\Valid]
    private Sticker\WbStickerDTO $sticker;


    public function __construct()
    {
        $this->sticker = new Sticker\WbStickerDTO();
    }

    /**
     * Идентификатор события
     */

    public function getEvent(): WbOrdersEventUid
    {
        return $this->id;
    }

    //    public function setId(WbOrdersEventUid $id): void
    //    {
    //        $this->id = $id;
    //    }


    /**
     * Профиль пользователя
     */

    public function getProfile(): UserProfileUid
    {
        return $this->profile;
    }


    /**
     * Стикер заказа
     */

    public function getSticker(): Sticker\WbStickerDTO
    {
        return $this->sticker;
    }

    public function setSticker(Sticker\WbStickerDTO $sticker): void
    {
        $this->sticker = $sticker;
    }

}