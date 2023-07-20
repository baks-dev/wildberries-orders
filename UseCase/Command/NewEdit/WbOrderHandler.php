<?php
/*
 *  Copyright 2023.  Baks.dev <admin@baks.dev>
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

namespace BaksDev\Wildberries\Orders\UseCase\Command\NewEdit;


use BaksDev\Core\Services\Messenger\MessageDispatchInterface;
use BaksDev\Wildberries\Orders\Entity\Event\WbOrdersEvent;
use BaksDev\Wildberries\Orders\Entity\WbOrders;
use BaksDev\Wildberries\Orders\Messenger\WbOrderMessage;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class WbOrderHandler
{
    private EntityManagerInterface $entityManager;

    private ValidatorInterface $validator;

    private LoggerInterface $logger;

    private MessageDispatchInterface $messageDispatch;

    public function __construct(
        EntityManagerInterface $entityManager,
        ValidatorInterface $validator,
        LoggerInterface $logger,
        MessageDispatchInterface $messageDispatch
    )
    {
        $this->entityManager = $entityManager;
        $this->validator = $validator;
        $this->logger = $logger;
        $this->messageDispatch = $messageDispatch;

    }

    /** @see WbOrder */
    public function handle(
        WbOrderDTO $command,
        //?UploadedFile $cover = null
    ): string|WbOrders
    {
        /**
         *  Валидация WbOrderDTO
         */
        $errors = $this->validator->validate($command);

        if(count($errors) > 0)
        {
            $uniqid = uniqid('', false);
            $errorsString = (string)$errors;
            $this->logger->error($uniqid.': '.$errorsString);
            return $uniqid;
        }


        if($command->getEvent())
        {
            $EventRepo = $this->entityManager->getRepository(WbOrdersEvent::class)->find(
                $command->getEvent()
            );

            if($EventRepo === null)
            {
                $uniqid = uniqid('', false);
                $errorsString = sprintf(
                    'Not found %s by id: %s',
                    WbOrdersEvent::class,
                    $command->getEvent()
                );
                $this->logger->error($uniqid.': '.$errorsString);

                return $uniqid;
            }

            $Event = $EventRepo->cloneEntity();

        }
        else
        {
            $Event = new WbOrdersEvent();
            $this->entityManager->persist($Event);
        }

        $this->entityManager->clear();

        /** @var WbOrders $Main */
        if($Event->getOrd())
        {
            $Main = $this->entityManager->getRepository(WbOrders::class)->findOneBy(
                ['event' => $command->getEvent()]
            );

            if(empty($Main))
            {
                $uniqid = uniqid('', false);
                $errorsString = sprintf(
                    'Not found %s by event: %s',
                    WbOrders::class,
                    $command->getEvent()
                );
                $this->logger->error($uniqid.': '.$errorsString);

                return $uniqid;
            }

        }
        else
        {

            $Main = new WbOrders($command->getOrd(), $command->getWbOrder());
            $this->entityManager->persist($Main);
            $Event->setOrd($Main->getId());
        }


        $Event->setEntity($command);
        $this->entityManager->persist($Event);


        /**
         * Валидация Event
         */
        $errors = $this->validator->validate($Event);

        if(count($errors) > 0)
        {
            $uniqid = uniqid('', false);
            $errorsString = (string)$errors;
            $this->logger->error($uniqid.': '.$errorsString);
            return $uniqid;
        }


        /* присваиваем событие корню */
        $Main->setEvent($Event);

        /**
         * Валидация Main
         */
        $errors = $this->validator->validate($Main);

        if(count($errors) > 0)
        {
            $uniqid = uniqid('', false);
            $errorsString = (string)$errors;
            $this->logger->error($uniqid.': '.$errorsString);
            return $uniqid;
        }


        $this->entityManager->flush();

        /* Отправляем сообщение в шину */
        $this->messageDispatch->dispatch(
            message: new WbOrderMessage($Main->getId(), $Main->getEvent(), $command->getEvent()),
            transport: 'wb_orders'
        );

        // 'wb_order_high'
        return $Main;
    }
}