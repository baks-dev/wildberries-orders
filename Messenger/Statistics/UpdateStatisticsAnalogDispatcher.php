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

namespace BaksDev\Wildberries\Orders\Messenger\Statistics;

use BaksDev\Wildberries\Orders\Entity\Analog\WbOrdersStatisticsAnalog;
use BaksDev\Wildberries\Orders\Repository\WbOrdersAnalog\WbOrdersAnalogInterface;
use BaksDev\Wildberries\Orders\UseCase\Analog\WbOrdersAnalogDTO;
use BaksDev\Wildberries\Orders\UseCase\Analog\WbOrdersAnalogHandler;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * Обновляем статистику аналогичных заказов Wildberries
 */
#[AsMessageHandler(priority: 10)]
final readonly class UpdateStatisticsAnalogDispatcher
{

    public function __construct(
        #[Target('wildberriesOrdersLogger')] private LoggerInterface $logger,
        private WbOrdersAnalogInterface $ordersAnalog,
        private WbOrdersAnalogHandler $ordersAnalogHandler
    ) {}

    /**
     * Обновляем статистику аналогичных заказов Wildberries
     */
    public function __invoke(UpdateStatisticMessage $message): void
    {
        $this->logger->info(
            'Обновляем статистику аналогичных заказов Wildberries',
            [
                'invariable' => $message->getProductInvariable(),
                self::class.':'.__LINE__,
            ],
        );

        /* Получить значение */
        $analog = $this->ordersAnalog->countOrderAnalogByProduct($message->getProductEvent());

        /* Обработка данных */
        $WbOrdersAnalogDTO = new WbOrdersAnalogDTO(
            $message->getProductInvariable(),
            $analog,
        );

        $WbOrdersStatisticsAnalog = $this->ordersAnalogHandler->handle($WbOrdersAnalogDTO);

        if(false === ($WbOrdersStatisticsAnalog instanceof WbOrdersStatisticsAnalog))
        {
            $this->logger->critical(
                sprintf('wildberries-orders: Ошибка %s при обновлении статистики аналогичных заказов Wildberries', $WbOrdersStatisticsAnalog),
                [
                    'invariable' => $message->getProductInvariable(),
                    self::class.':'.__LINE__,
                ]);
        }

    }
}