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

namespace BaksDev\Wildberries\Orders\Commands;

use BaksDev\Core\Deduplicator\DeduplicatorInterface;
use BaksDev\Core\Messenger\MessageDispatchInterface;
use BaksDev\Users\Profile\UserProfile\Type\Id\UserProfileUid;
use BaksDev\Wildberries\Orders\Messenger\CompletedOrders\WildberriesOrderCompletedMessage;
use BaksDev\Wildberries\Orders\Repository\AllWbOrdersMarketplace\AllWbOrdersMarketplaceInterface;
use BaksDev\Wildberries\Orders\Repository\AllWbOrdersMarketplace\AllWbOrdersMarketplaceResult;
use BaksDev\Wildberries\Repository\AllProfileToken\AllProfileTokenInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Style\SymfonyStyle;


#[AsCommand(
    name: 'baks:wildberries-orders:completed',
    description: 'Получаем все заказы, и обновляем выполненные'
)
]
class UpdateWildberriesOrdersCompletedCommand extends Command
{
    private SymfonyStyle $io;

    public function __construct(
        private readonly AllProfileTokenInterface $allProfileToken,
        private readonly AllWbOrdersMarketplaceInterface $AllWbOrdersMarketplace,
        private readonly MessageDispatchInterface $messageDispatch,
        private readonly DeduplicatorInterface $deduplicator
    )
    {
        parent::__construct();
    }


    protected function configure(): void
    {
        $this->addArgument('profile', InputArgument::OPTIONAL, 'Идентификатор профиля');
    }


    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->io = new SymfonyStyle($input, $output);

        /**
         * Получаем активные токены авторизации профилей Wildberries
         */
        $profiles = $this->allProfileToken
            ->onlyActiveToken()
            ->findAll();

        $profiles = iterator_to_array($profiles);

        $helper = $this->getHelper('question');

        /**
         * Интерактивная форма списка профилей
         */

        $questions[] = 'Все';

        foreach($profiles as $quest)
        {
            $questions[] = $quest->getAttr();
        }

        $questions['+'] = 'Выполнить все асинхронно';
        $questions['-'] = 'Выйти';

        $question = new ChoiceQuestion(
            'Профиль пользователя (Ctrl+C чтобы выйти)',
            $questions,
            '0'
        );

        $key = $helper->ask($input, $output, $question);

        /**
         *  Выходим без выполненного запроса
         */

        if($key === '-' || $key === 'Выйти')
        {
            return Command::SUCCESS;
        }


        /**
         * Выполняем все с возможностью асинхронно в очереди
         */

        if($key === '+' || $key === '0' || $key === 'Все')
        {
            /** @var UserProfileUid $profile */
            foreach($profiles as $profile)
            {
                $this->update($profile, $key === '+');
            }

            $this->io->success('Заказы успешно обновлены');
            return Command::SUCCESS;
        }


        /**
         * Выполняем определенный профиль
         */

        $UserProfileUid = null;

        foreach($profiles as $profile)
        {
            if($profile->getAttr() === $questions[$key])
            {
                /* Присваиваем профиль пользователя */
                $UserProfileUid = $profile;
                break;
            }
        }

        if($UserProfileUid)
        {
            $this->update($UserProfileUid);

            $this->io->success('Заказы успешно обновлены');
            return Command::SUCCESS;
        }

        $this->io->success('Профиль пользователя не найден');
        return Command::SUCCESS;

    }

    public function update(UserProfileUid $profile, bool $async = false): void
    {
        $this->io->note(sprintf('Обновляем выполненные заказы профиля %s', $profile->getAttr()));

        /** Получаем список всех заказов со статусом Marketplace «Передан службе маркетплейса Wildberries» */

        $orders = $this->AllWbOrdersMarketplace
            ->forProfile($profile)
            ->findAll();


        if(false === $orders || $orders->valid() === false)
        {
            return;
        }

        /** @var AllWbOrdersMarketplaceResult $order */
        foreach($orders as $order)
        {
            /**
             * Пропускаем, если заказ был добавлен при упаковке
             * @see WildberriesOrderCompletedDispatcher
             */
            $Deduplicator = $this->deduplicator
                ->namespace('wildberries-orders')
                ->deduplication([$order->getNumber(), self::class]);

            /** Пропускаем, если заказ был добавлен в очередь на проверку */
            if($Deduplicator->isExecuted())
            {
                continue;
            }

            if(false === $async)
            {
                /** Делаем задержку от блока */
                usleep(500000);
            }

            /* Отправляем сообщение в шину профиля */
            $this->messageDispatch->dispatch(
                message: new WildberriesOrderCompletedMessage(
                    $profile,
                    $order->getId(),
                    $order->getNumber(),
                    true
                ),
                transport: $async === true ? (string) $profile : null
            );

            $this->io->writeln(sprintf('<fg=green>Проверили заказ %s</>', $order->getNumber()));

            $Deduplicator->save();

        }
    }
}
