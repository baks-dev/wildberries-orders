<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace BaksDev\Wildberries\Orders\Command;

//use App\Module\User\Profile\UserProfile\Type\Id\UserProfileUid;
//use App\Module\Wildberries\Orders\Order\Entity\Event\WbOrdersEvent;
//use App\Module\Wildberries\Orders\Order\Repository\AllOrdersByStatus\AllOrdersByStatusInterface;
//use App\Module\Wildberries\Orders\Order\Repository\WbOrdersById\WbOrdersByIdInterface;
//use App\Module\Wildberries\Orders\Order\Type\Status\WbOrderStatusEnum;
//use App\Module\Wildberries\Orders\Order\Type\StatusClient\WbClientStatusEnum;
//use App\Module\Wildberries\Orders\Order\UseCase\Command\NewEdit\Wildberries\WbOrderDTO;
//use App\Module\Wildberries\Orders\Order\UseCase\Command\UpdateStatus\WbUpdateOrderStatusDTO;
//use App\Module\Wildberries\Orders\Order\UseCase\WbOrdersAggregate;
//use App\Module\Wildberries\Orders\Supplys\Package\Entity\Orders\WbPackageOrder;
//use App\Module\Wildberries\Rest\Api\Orders\Orders\WbOrders;
//use App\Module\Wildberries\Rest\Auth\WbTokenAuth;
//use App\Module\Wildberries\Rest\Authorization\WildberriesAuthorizationInterface;
//use App\Module\Wildberries\Rest\OpenApi\Orders\PostOrderStatus;
//use App\Module\Wildberries\Settings\Repository\AllShopSettingsToken\AllShopSettingsTokenInterface;
//use App\Module\Wildberries\Rest\OpenApi\Orders\PostOrderStatus;
use BaksDev\Wildberries\Api\Token\Orders\WildberriesOrdersStatus;
use BaksDev\Wildberries\Orders\Entity\Event\WbOrdersEvent;
use BaksDev\Wildberries\Orders\Repository\AllOrdersByStatus\AllOrdersByStatusInterface;
use BaksDev\Wildberries\Orders\Type\OrderStatus\WbOrderStatus;
use BaksDev\Wildberries\Orders\Type\WildberriesStatus\WildberriesStatus;
use BaksDev\Wildberries\Orders\UseCase\Command\NewEdit\WbOrderDTO;
use BaksDev\Wildberries\Orders\UseCase\Command\NewEdit\WbOrderHandler;
use BaksDev\Wildberries\Repository\AllProfileToken\AllProfileTokenInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\TaggedIterator;

#[AsCommand(
    name: 'baks:wb:orders:status',
    description: 'Получаем все заказы, и обновляем статус заказов которые изменились')
]
class UpdateOrdersStatusCommand extends Command
{
    //
    //	private AllShopSettingsTokenInterface $settingsTokens;
    //
    //	private AllOrdersByStatusInterface $allOrdersByStatus;
    //
    //	private WbOrdersByIdInterface $wbOrdersById;
    //
    //	private WbOrdersAggregate $wbOrdersAggregate;
    //
    //	private WildberriesAuthorizationInterface $wildberriesAuthorization;
    //
    //	private LoggerInterface $logger;
    //
    private EntityManagerInterface $entityManager;
    private AllProfileTokenInterface $allProfileToken;
    private iterable $WildberriesStatus;
    private AllOrdersByStatusInterface $allOrdersByStatus;
    private WildberriesOrdersStatus $wildberriesOrdersStatus;
    private WbOrderHandler $WildberriesOrderHandler;


    public function __construct(
        //		AllShopSettingsTokenInterface $settingsTokens,
        //		AllOrdersByStatusInterface $allOrdersByStatus,
        //		WbOrdersByIdInterface $wbOrdersById,
        //		WbOrdersAggregate $wbOrdersAggregate,
        //		WildberriesAuthorizationInterface $wildberriesAuthorization,
        //		LoggerInterface $logger,
        EntityManagerInterface $entityManager,
        AllProfileTokenInterface $allProfileToken,
        AllOrdersByStatusInterface $allOrdersByStatus,
        WildberriesOrdersStatus $wildberriesOrdersStatus,
        WbOrderHandler $WildberriesOrderHandler,
        #[TaggedIterator('baks.wb.status')] iterable $WildberriesStatus,

    )
    {
        parent::__construct();

        //		$this->settingsTokens = $settingsTokens;
        //		$this->allOrdersByStatus = $allOrdersByStatus;
        //		$this->wbOrdersById = $wbOrdersById;
        //		$this->wbOrdersAggregate = $wbOrdersAggregate;
        //		$this->wildberriesAuthorization = $wildberriesAuthorization;
        //		$this->logger = $logger;
        $this->entityManager = $entityManager;
        $this->allProfileToken = $allProfileToken;
        $this->WildberriesStatus = $WildberriesStatus;
        $this->allOrdersByStatus = $allOrdersByStatus;
        $this->wildberriesOrdersStatus = $wildberriesOrdersStatus;
        $this->WildberriesOrderHandler = $WildberriesOrderHandler;
    }


    protected function execute(InputInterface $input, OutputInterface $output): int
    {

        //return Command::SUCCESS;

        $io = new SymfonyStyle($input, $output);

        foreach($this->allProfileToken->fetchAllWbTokenProfileAssociative() as $profile)
        {
            ProgressBar::setFormatDefinition('custom', ' %current%/%max% -- %message%');
            $progressBar = new ProgressBar($output);
            $progressBar->setFormat('custom');
            $progressBar->setMessage(sprintf('Token: %s', $profile));
            $progressBar->start();

            $this->wildberriesOrdersStatus->profile($profile);

            foreach($this->WildberriesStatus as $wbStatus)
            {
                /* Пропускаем заказы, которые уже завершились */
                //                if(
                //                    $wbStatus instanceof WildberriesStatusSold || // сборочное задание получено покупателем
                //                    $wbStatus instanceof WildberriesStatusCanceled || // отмена сборочного задания
                //                    $wbStatus instanceof WildberriesStatusCanceledClient || //  отмена сборочного задания покупателем
                //                    $wbStatus instanceof WildberriesStatusDefect // отмена сборочного задания по причине брака
                //                )
                //                {
                //                    continue;
                //                }


                /* Получаем все заказы по статусу */

                $orders = $this->allOrdersByStatus
                    ->fetchAllOrdersByWildberriesStatusAssociativeIndexed($profile, $wbStatus);

                if(!$orders)
                {
                    continue;
                }

                /** Делим все заказы по 1000 items */
                $chunkedOrders = array_chunk($orders, 1000);

                foreach($chunkedOrders as $chunkedOrder)
                {
                    sleep(1); /* Делаем задержку между запросами */
                    $wbOrdersAll = array_column($chunkedOrder, 'order_wb');

                    /** Получаем все статусы Wildberries API */
                    $this->wildberriesOrdersStatus->setOrders($wbOrdersAll)->request();
                    $apiWbOrdersStatus = $this->wildberriesOrdersStatus->getContent();

                    foreach($apiWbOrdersStatus as $apiStatus)
                    {
                        /** Не обновляем заказы со статусом Новый */
                        if($apiStatus['supplierStatus'] === 'new' && $apiStatus['wbStatus'] === 'waiting')
                        {
                            continue;
                        }


                        $progressBar->advance($apiStatus['id']);

                        $currentOrder = $orders[$apiStatus['id']];

                        /** Если статус был изменен - обновляем заказ Wildberries */
                        if(
                            $currentOrder['event_status'] !== $apiStatus['supplierStatus'] ||
                            $currentOrder['event_wildberries'] !== $apiStatus['wbStatus']
                        )
                        {
                            $WbOrdersEvent = $this->entityManager->getRepository(WbOrdersEvent::class)->find($currentOrder['order_event']);
                            $WbOrderDTO = new WbOrderDTO($profile, $apiStatus['id']);
                            $WbOrdersEvent->getDto($WbOrderDTO);

                            $WbOrderDTO->setStatus(new WbOrderStatus($apiStatus['supplierStatus']));
                            $WbOrderDTO->setWildberries(new WildberriesStatus($apiStatus['wbStatus']));

                            $this->WildberriesOrderHandler->handle($WbOrderDTO);

                            $io->warning(sprintf('Обновили заказ %s', $apiStatus['id']));

                        }
                    }
                }
            }
        }

        $io->success('Заказы успешно обновлены');

        return Command::SUCCESS;

    }

}
