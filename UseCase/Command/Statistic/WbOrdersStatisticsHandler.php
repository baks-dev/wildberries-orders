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

namespace BaksDev\Wildberries\Orders\UseCase\Command\Statistic;


use BaksDev\Wildberries\Orders\Entity\WbOrdersStatistics;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class WbOrdersStatisticsHandler
{
    private EntityManagerInterface $entityManager;

    private ValidatorInterface $validator;

    private LoggerInterface $logger;


    public function __construct(
        EntityManagerInterface $entityManager,
        ValidatorInterface $validator,
        LoggerInterface $logger,

    )
    {
        $this->entityManager = $entityManager;
        $this->validator = $validator;
        $this->logger = $logger;
    }

    /** @see WbOrdersStatistics */
    public function handle(WbOrdersStatisticsDTO $command,): string|WbOrdersStatistics
    {
        /**
         *  Валидация WbOrdersStatisticsDTO
         */
        $errors = $this->validator->validate($command);

        if(count($errors) > 0)
        {
            /** Ошибка валидации */
            $uniqid = uniqid('', false);
            $this->logger->error(sprintf('%s: %s', $uniqid, $errors), [__LINE__ => __FILE__]);

            return $uniqid;
        }


        $this->entityManager->clear();

        $WbOrdersStatistics = $this->entityManager->getRepository(WbOrdersStatistics::class)
            ->find($command->getProduct());

        if(empty($WbOrdersStatistics))
        {
            $WbOrdersStatistics = new WbOrdersStatistics();

        }


        $WbOrdersStatistics->setEntity($command);
        $this->entityManager->persist($WbOrdersStatistics);

        /**
         * Валидация WbOrdersStatistics
         */
        $errors = $this->validator->validate($WbOrdersStatistics);

        if(count($errors) > 0)
        {
            /** Ошибка валидации */
            $uniqid = uniqid('', false);
            $this->logger->error(sprintf('%s: %s', $uniqid, $errors), [__LINE__ => __FILE__]);

            return $uniqid;
        }

        $this->entityManager->flush();

        return $WbOrdersStatistics;
    }
}