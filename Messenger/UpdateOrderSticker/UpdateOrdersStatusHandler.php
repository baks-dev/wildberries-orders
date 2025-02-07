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

namespace BaksDev\Wildberries\Orders\Messenger\UpdateOrderSticker;

use BaksDev\Wildberries\Orders\Api\WildberriesOrdersSticker\WildberriesOrdersStickerRequest;
use BaksDev\Wildberries\Orders\Entity\WbOrders;
use BaksDev\Wildberries\Orders\Messenger\WbOrderMessage;
use BaksDev\Wildberries\Orders\Repository\WbOrderProfile\WbOrderProfileInterface;
use BaksDev\Wildberries\Orders\Repository\WbOrdersById\WbOrdersByIdInterface;
use BaksDev\Wildberries\Orders\Type\OrderStatus\Status\WbOrderStatusConfirm;
use BaksDev\Wildberries\Orders\UseCase\Command\Sticker\StickerWbOrderDTO;
use BaksDev\Wildberries\Orders\UseCase\Command\Sticker\StickerWbOrderHandler;
use DomainException;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class UpdateOrdersStatusHandler
{
    public function __construct(
        #[Target('wildberriesOrdersLogger')] private LoggerInterface $logger,
        private WbOrdersByIdInterface $wbOrdersById,
        private WbOrderProfileInterface $wbOrderProfile,
        private WildberriesOrdersStickerRequest $wildberriesOrdersSticker,
        private StickerWbOrderHandler $stickerWbOrderHandler,
    ) {}

    /**
     * При обновлении статуса заказа Confirm (Добавлен к поставке, на сборке) получаем и обновляет стикер
     */
    public function __invoke(WbOrderMessage $message): void
    {

        // TODO: Запустить прогрев стикеров заказов
        return;


        $WbOrdersEvent = $this->wbOrdersById->getWbOrderByOrderUidOrNullResult($message->getId());

        if(!$WbOrdersEvent || !$WbOrdersEvent->statusEquals(WbOrderStatusConfirm::class))
        {
            return;
        }

        /* Получаем профиль пользователя и идентификатор заказа в качестве аттрибута */
        $UserProfileUid = $this->wbOrderProfile->findWbOrderProfile($WbOrdersEvent->getMain());

        if(!$UserProfileUid)
        {
            return;
        }

        /** @var StickerWbOrderDTO $StickerWbOrderDTO */
        $StickerWbOrderDTO = $WbOrdersEvent->getDto(StickerWbOrderDTO::class);
        $WbStickerDTO = $StickerWbOrderDTO->getSticker();


        $WildberriesOrdersSticker = $this->wildberriesOrdersSticker
            ->profile($UserProfileUid)
            ->forOrder($UserProfileUid->getAttr())
            ->getOrderSticker();

        $WbStickerDTO->setSticker($WildberriesOrdersSticker->getSticker());
        $WbStickerDTO->setPart($WildberriesOrdersSticker->getPart());


        $this->logger->info(
            'Обновляем стикер заказа Wildberries',
            [
                'order' => $UserProfileUid->getAttr(),
                self::class.':'.__LINE__
            ]);

        $handle = $this->stickerWbOrderHandler->handle($StickerWbOrderDTO);

        if(!$handle instanceof WbOrders)
        {
            $this->logger->critical(
                'Ошибка при обновлении стикера заказа Wildberries',
                [
                    'code' => $handle,
                    'order' => $UserProfileUid->getAttr(),
                    self::class.':'.__LINE__
                ]);

            throw new DomainException();
        }
    }
}