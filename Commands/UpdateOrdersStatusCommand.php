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

use BaksDev\Core\Messenger\MessageDispatchInterface;
use BaksDev\Users\Profile\UserProfile\Type\Id\UserProfileUid;
use BaksDev\Wildberries\Orders\Messenger\UpdateOrdersStatus\UpdateOrdersStatusMessage;
use BaksDev\Wildberries\Repository\AllProfileToken\AllProfileTokenInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Получаем все заказы, и обновляем статус заказов которые изменились
 */
#[AsCommand(
    name: 'baks:wildberries-orders:status',
    description: 'Получаем все заказы, и обновляем статус заказов которые изменились'
)
]
class UpdateOrdersStatusCommand extends Command
{
    private AllProfileTokenInterface $allProfileToken;

    private MessageDispatchInterface $messageDispatch;

    public function __construct(
        AllProfileTokenInterface $allProfileToken,
        MessageDispatchInterface $messageDispatch,
    )
    {
        parent::__construct();

        $this->allProfileToken = $allProfileToken;
        $this->messageDispatch = $messageDispatch;
    }


    protected function configure(): void
    {
        $this->addArgument('profile', InputArgument::OPTIONAL, 'Идентификатор профиля');
    }


    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $profile = $input->getArgument('profile');

        if($profile)
        {
            /** Если требуется выбрать профиль из списка */
            if($profile === 'choice')
            {
                $helper = $this->getHelper('question');

                $profiles = $this->allProfileToken
                    ->onlyActiveToken()
                    ->findAll();

                $questions = null;

                foreach($profiles as $quest)
                {
                    $questions[] = $quest->getAttr();
                }

                $question = new Question('Профиль пользователя: ');
                $question->setAutocompleterValues($questions);

                $profileName = $helper->ask($input, $output, $question);

                foreach($profiles as $profile)
                {
                    if($profile->getAttr() === $questions[$profileName])
                    {
                        break;
                    }
                }
            }

            /* Присваиваем профиль пользователя */
            $profile = new UserProfileUid($profile);

            /* Отправляем сообщение в шину профиля */
            $this->messageDispatch->dispatch(
                message: new UpdateOrdersStatusMessage($profile),
                transport: (string) $profile,
            );
        }
        else
        {
            $profiles = $this->allProfileToken
                ->onlyActiveToken()
                ->findAll();

            foreach($profiles as $profile)
            {
                /* Отправляем сообщение в шину профиля */
                $this->messageDispatch->dispatch(
                    message: new UpdateOrdersStatusMessage($profile),
                    transport: (string) $profile,
                );
            }
        }

        $io->success('Новые заказы успешно добавлены в очередь');

        return Command::SUCCESS;

    }

}
