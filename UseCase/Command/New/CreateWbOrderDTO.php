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

namespace BaksDev\Wildberries\Orders\UseCase\Command\New;

use BaksDev\Orders\Order\Entity\Order;
use BaksDev\Orders\Order\Type\Id\OrderUid;
use BaksDev\Users\Profile\UserProfile\Type\Id\UserProfileUid;
use BaksDev\Wildberries\Orders\Entity\Event\WbOrdersEventInterface;
use BaksDev\Wildberries\Orders\Type\Event\WbOrdersEventUid;
use BaksDev\Wildberries\Orders\Type\OrderStatus\Status\Collection\WbOrderStatusInterface;
use BaksDev\Wildberries\Orders\Type\OrderStatus\WbOrderStatus;
use BaksDev\Wildberries\Orders\Type\WildberriesStatus\Status\Collection\WildberriesStatusInterface;
use BaksDev\Wildberries\Orders\Type\WildberriesStatus\WildberriesStatus;
use DateTimeImmutable;
use Symfony\Component\Validator\Constraints as Assert;

/** @see WbOrdersEvent */
final class CreateWbOrderDTO implements WbOrdersEventInterface
{
    /**
     * Идентификатор события
     */
    #[Assert\Uuid]
    #[Assert\isNull]
    private ?WbOrdersEventUid $id = null;

    /**
     * Профиль пользователя
     */
    #[Assert\NotBlank]
    #[Assert\Uuid]
    private readonly UserProfileUid $profile;

    /**
     * Идентификатор системного заказа
     */
    #[Assert\NotBlank]
    #[Assert\Uuid]
    private readonly OrderUid $main;

    /**
     * Идентификатор заказа Wildberries
     * @see WbOrders
     */
    #[Assert\NotBlank]
    private readonly int $wbOrder;

    /**
     * Штрихкод
     */
    private string $barcode;

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
     * Дата создания заказа на Wildberries
     */
    #[Assert\NotBlank]
    private DateTimeImmutable $created;

    /**
     * Клиент
     */
    #[Assert\Valid]
    private Client\WbOrderClientDTO $client;


    public function __construct(UserProfileUid $profile, int $wbOrder)
    {
        $this->client = new Client\WbOrderClientDTO();
        $this->wbOrder = $wbOrder;
        $this->profile = $profile;
    }

    /**
     * Идентификатор события
     */

    public function getEvent(): ?WbOrdersEventUid
    {
        return $this->id;
    }

    public function setId(WbOrdersEventUid $id): void
    {
        $this->id = $id;
    }

    /**
     * Идентификатор заказа Wildberries
     * @see WbOrders
     */

    public function getWbOrder(): int
    {
        return $this->wbOrder;
    }

    /**
     * Статус сборочного задания
     */

    public function getStatus(): WbOrderStatus
    {
        return $this->status;
    }

    public function setStatus(WbOrderStatus|WbOrderStatusInterface $status): void
    {
        $this->status = $status instanceof WbOrderStatusInterface ? new WbOrderStatus($status) : $status;
    }

    /**
     * Внутренний статус Wildberries
     */
    public function getWildberries(): WildberriesStatus
    {
        return $this->wildberries;
    }

    public function setWildberries(WildberriesStatus|WildberriesStatusInterface $wildberries): void
    {
        $this->wildberries = $wildberries instanceof WildberriesStatusInterface ? new WildberriesStatus($wildberries) : $wildberries;
    }


    /**
     * Дата создания заказа на Wildberries
     */

    public function getCreated(): DateTimeImmutable
    {
        return $this->created;
    }

    public function setCreated(DateTimeImmutable $created): void
    {
        $this->created = $created;
    }


    /**
     * Клиент
     */
    public function getClient(): Client\WbOrderClientDTO
    {
        return $this->client;
    }


    public function setClient(Client\WbOrderClientDTO $client): void
    {
        $this->client = $client;
    }


    /**
     * Штрихкод
     */
    public function getBarcode(): string
    {
        return $this->barcode;
    }

    public function setBarcode(string $barcode): void
    {
        $this->barcode = $barcode;
    }


    /**
     * Идентификатор системного заказа
     */

    public function getMain(): ?OrderUid
    {
        return $this->main;
    }


    public function setMain(OrderUid|Order $order): void
    {
        $this->main = $order instanceof Order ? $order->getId() : $order;
    }


    /**
     * Профиль пользователя
     */

    public function getProfile(): UserProfileUid
    {
        return $this->profile;
    }

}