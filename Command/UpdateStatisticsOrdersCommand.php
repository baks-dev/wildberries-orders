<?php

declare(strict_types=1);

namespace BaksDev\Wildberries\Orders\Command;


use BaksDev\Products\Product\Entity\Product;
use BaksDev\Wildberries\Orders\Entity\WbOrdersStatistics;
use BaksDev\Wildberries\Orders\Repository\WbOrdersAlarm\WbOrdersAlarmInterface;
use BaksDev\Wildberries\Orders\Repository\WbOrdersAnalog\WbOrdersAnalogInterface;
use BaksDev\Wildberries\Orders\Repository\WbOrdersOld\WbOrdersOldInterface;
use BaksDev\Wildberries\Orders\UseCase\Command\Statistic\WbOrdersStatisticsDTO;
use BaksDev\Wildberries\Orders\UseCase\Command\Statistic\WbOrdersStatisticsHandler;
use Doctrine\ORM\EntityManagerInterface;
use DomainException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/** Получаем "НОВЫЕ" заказы */
#[AsCommand(
    name: 'baks:wb:orders:stats',
    description: 'Обновляем статистику по всем заказам')
]
final class UpdateStatisticsOrdersCommand extends Command
{
    private EntityManagerInterface $entityManager;
    private WbOrdersAlarmInterface $ordersAlarm;
    private WbOrdersAnalogInterface $ordersAnalog;
    private WbOrdersOldInterface $ordersOld;
    private WbOrdersStatisticsHandler $ordersStatisticsHandler;

    public function __construct(
        EntityManagerInterface $entityManager,
        WbOrdersAlarmInterface $ordersAlarm,
        WbOrdersAnalogInterface $ordersAnalog,
        WbOrdersOldInterface $ordersOld,
        WbOrdersStatisticsHandler $ordersStatisticsHandler
    )
    {
        parent::__construct();
        $this->entityManager = $entityManager;
        $this->ordersAlarm = $ordersAlarm;
        $this->ordersAnalog = $ordersAnalog;
        $this->ordersOld = $ordersOld;
        $this->ordersStatisticsHandler = $ordersStatisticsHandler;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $products = $this->entityManager->getRepository(Product::class)->findAll();

        $io = new SymfonyStyle($input, $output);
        $progressBar = new ProgressBar($output);
        $progressBar->start();

        foreach($products as $product)
        {

            $productUid = $product->getId();
            $ProductEventUid = $product->getEvent();

            $this->entityManager->clear();

            /** Получаем объект статистики */
            $WbOrdersProductStats = $this->entityManager->getRepository(WbOrdersStatistics::class)
                ->find($productUid);

            $WbOrdersStatisticsDTO = new WbOrdersStatisticsDTO();
            $WbOrdersStatisticsDTO->setProduct($productUid);
            $WbOrdersProductStats ? $WbOrdersProductStats->getDto($WbOrdersStatisticsDTO) : false;

            /** Получаем обновляем срочные */
            $alarm = $this->ordersAlarm->countOrderAlarmByProduct($ProductEventUid);
            $WbOrdersStatisticsDTO->setAlarm($alarm);

            /** Получаем и обновляем аналоги */
            $analog = $this->ordersAnalog->countOrderAnalogByProduct($ProductEventUid);
            $WbOrdersStatisticsDTO->setAnalog($analog);

            /** Получаем и обновляем дату самого старого невыполненного заказа */
            $old = $this->ordersOld->getOldOrderDateByProduct($ProductEventUid);
            $WbOrdersStatisticsDTO->setOld($old);

            $StatisticsHandler = $this->ordersStatisticsHandler->handle($WbOrdersStatisticsDTO);

            if(!$StatisticsHandler instanceof WbOrdersStatistics)
            {
                throw new DomainException(sprintf(
                    '%s: Ошибка при обновлении статистики продукта %s',
                    $StatisticsHandler,
                    $productUid
                ));
            }

            $progressBar->advance();
        }

        $progressBar->finish();
        $io->success('Команда успешно завершена');

        return Command::SUCCESS;
    }
}