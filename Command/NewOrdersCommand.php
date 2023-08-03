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

use BaksDev\Orders\Order\Type\Status\OrderStatus\OrderStatusCanceled;
use BaksDev\Orders\Order\UseCase\Admin\NewEdit\OrderDTO;
use BaksDev\Orders\Order\UseCase\Admin\NewEdit\OrderHandler;
use BaksDev\Orders\Order\UseCase\Admin\NewEdit\Products\OrderProductDTO;
use BaksDev\Orders\Order\UseCase\Admin\NewEdit\Products\Price\OrderPriceDTO;
use BaksDev\Orders\Order\UseCase\Admin\Status\OrderStatusDTO;
use BaksDev\Orders\Order\UseCase\Admin\Status\OrderStatusHandler;
use BaksDev\Products\Product\Repository\ProductByVariation\ProductByVariationInterface;
use BaksDev\Reference\Money\Type\Money;
use BaksDev\Wildberries\Api\Token\Orders\WildberriesOrdersNew;
use BaksDev\Wildberries\Orders\Entity\Event\WbOrdersEvent;
use BaksDev\Wildberries\Orders\Entity\WbOrders;
use BaksDev\Wildberries\Orders\Repository\WbOrdersById\WbOrdersByIdInterface;
use BaksDev\Wildberries\Orders\Type\Email\ClientEmail;
use BaksDev\Wildberries\Orders\Type\OrderStatus\Status\WbOrderStatusNew;
use BaksDev\Wildberries\Orders\Type\WildberriesStatus\Status\WildberriesStatusWaiting;
use BaksDev\Wildberries\Orders\UseCase\Command\NewEdit\WbOrderDTO;
use BaksDev\Wildberries\Orders\UseCase\Command\NewEdit\WbOrderHandler;
use BaksDev\Wildberries\Products\Entity\Cards\WbProductCard;
use BaksDev\Wildberries\Products\Entity\Cards\WbProductCardOffer;
use BaksDev\Wildberries\Products\Entity\Cards\WbProductCardVariation;
use BaksDev\Wildberries\Products\UseCase\Cards\NewEdit\Variation\WbProductCardVariationDTO;
use BaksDev\Wildberries\Repository\AllProfileToken\AllProfileTokenInterface;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'baks:wb:orders:new',
    description: 'Получаем "НОВЫЕ" заказы')
]
class NewOrdersCommand extends Command
{

    //	private AllShopSettingsTokenInterface $settingsTokens;
    //
    //	private EntityManagerInterface $entityManager;
    //
    //	private ProductByOfferInterface $productByOffer;
    //
    //	private OrderAggregate $orderAggregate;
    //
    //	private WbOrdersAggregate $wbOrdersAggregate;
    //
    //	private WbOrdersByIdInterface $wbOrdersById;
    //
    //	private WildberriesAuthorizationInterface $wildberriesAuthorization;
    //
    //	private LoggerInterface $logger;


    private AllProfileTokenInterface $allProfileToken;
    private WildberriesOrdersNew $wildberriesOrdersNew;
    private WbOrdersByIdInterface $wbOrdersById;
    private EntityManagerInterface $entityManager;
    private ProductByVariationInterface $productByVariation;
    private OrderHandler $orderHandler;
    private WbOrderHandler $WildberriesOrderHandler;
    private OrderStatusHandler $orderStatusHandler;

    public function __construct(
        EntityManagerInterface $entityManager,
        AllProfileTokenInterface $allProfileToken,
        WildberriesOrdersNew $wildberriesOrdersNew,
        WbOrdersByIdInterface $wbOrdersById,
        ProductByVariationInterface $productByVariation,
        OrderHandler $orderHandler,
        OrderStatusHandler $orderStatusHandler,
        WbOrderHandler $WildberriesOrderHandler


        //		AllShopSettingsTokenInterface $settingsTokens,
        //		EntityManagerInterface $entityManager,
        //		ProductByOfferInterface $productByOffer,
        //		OrderAggregate $orderAggregate,
        //		WbOrdersAggregate $wbOrdersAggregate,
        //		WbOrdersByIdInterface $wbOrdersById,
        //		WildberriesAuthorizationInterface $wildberriesAuthorization,
        //		LoggerInterface $logger,
    )
    {
        parent::__construct();

        //		$this->settingsTokens = $settingsTokens;
        //		$this->entityManager = $entityManager;
        //		$this->productByOffer = $productByOffer;
        //		$this->orderAggregate = $orderAggregate;
        //		$this->wbOrdersAggregate = $wbOrdersAggregate;
        //		$this->wbOrdersById = $wbOrdersById;
        //		$this->wildberriesAuthorization = $wildberriesAuthorization;
        //		$this->logger = $logger;


        $this->allProfileToken = $allProfileToken;
        $this->wildberriesOrdersNew = $wildberriesOrdersNew;
        $this->wbOrdersById = $wbOrdersById;
        $this->entityManager = $entityManager;
        $this->productByVariation = $productByVariation;
        $this->orderHandler = $orderHandler;
        $this->WildberriesOrderHandler = $WildberriesOrderHandler;
        $this->orderStatusHandler = $orderStatusHandler;
    }


    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        //return Command::SUCCESS;

        $io = new SymfonyStyle($input, $output);
        //$this->logger->error('Запустили комманду app:wb:orders:update:new (Получаем НОВЫЕ заказы)');


        foreach($this->allProfileToken->fetchAllWbTokenProfileAssociative() as $profile)
        {

            ProgressBar::setFormatDefinition('custom', ' %current%/%max% -- %message%');
            $progressBar = new ProgressBar($output);
            $progressBar->setFormat('custom');
            $progressBar->setMessage(sprintf('Token: %s', $profile));
            $progressBar->start();


            $this->wildberriesOrdersNew->profile($profile)->request();


            sleep(5);// Делаем задержку между запросами
            $orders = $this->wildberriesOrdersNew->request()->getContent();


            if($orders === null || empty($orders))
            {
                continue;
            }

            foreach($orders as $order)
            {
                $progressBar->advance();

                /* Проверяем, имеется ли в системе заказ WB с указанным идентификатором */
                /** @var WbOrdersEvent $WbOrder */
                $WbOrder = $this->wbOrdersById->getWbOrderOrNullResult($order['id']);

                /* Если заказ существует - пропускам */
                if(!empty($WbOrder))
                {
                    continue;
                }

                /* Получаем карточку по штрихкоду */
                $barcode = current($order['skus']);

                $WbProductCardVariation = $this->entityManager
                    ->getRepository(WbProductCardVariation::class)->find($barcode);


                /* Если множественный вариант Wildberries не найден - смотрим, нет ли торгового предложения с данной номенклатурой */
                if(empty($WbProductCardVariation))
                {
                    $WbProductCardOfferRemove = $this->entityManager
                        ->getRepository(WbProductCardOffer::class)->find($order['nmId']);

                    /* Если найдена номенклатура - следовательно карточка изменилась (восстановлена с другом штрихкодом)*/
                    if($WbProductCardOfferRemove)
                    {
                        $WbProductCardRemove = $this->entityManager->getRepository(WbProductCard::class)
                            ->find($WbProductCardOfferRemove->getCard());

                        $this->entityManager->remove($WbProductCardRemove);
                    }

                    $io->error(sprintf('Отсутствует карточка товара Wildberries barcode: %s', $barcode));
                    continue;
                }


                /* Применяем множественный вариант Wildberries */
                $WbProductCardVariationDTO = new WbProductCardVariationDTO();
                $WbProductCardVariation->getDto($WbProductCardVariationDTO);


                /* Получаем продукт */
                $ProductVariationConst = $WbProductCardVariationDTO->getVariation();
                $Product = $this->productByVariation->getProductByVariationConstOrNull($ProductVariationConst);


                if(!$Product)
                {
                    $io->error(sprintf('Отсутствует продукция barcode: %s', $barcode));
                    continue;
                }


                /* Создаем системный заказ */

                $OrderDTO = new OrderDTO();
                $OrderDTO->setUsers(null);

                $OrderProductDTO = new OrderProductDTO();
                $OrderProductDTO->setProduct($Product['event_id']);
                $OrderProductDTO->setOffer($Product['offer_id']);
                $OrderProductDTO->setVariation($Product['variation_id']);

                $OrderPriceDTO = new OrderPriceDTO();
                $price = new Money(($order['convertedPrice'] / 100));
                $OrderPriceDTO->setPrice($price);
                $OrderPriceDTO->setTotal(1);
                $OrderProductDTO->setPrice($OrderPriceDTO);
                $OrderDTO->addProduct($OrderProductDTO);

                $Order = $this->orderHandler->handle($OrderDTO);


                /* Создаем Wildberries заказ */

                $WbOrderDTO = new WbOrderDTO($profile, $order['id']);
                $WbOrderDTO->setOrd($Order->getId());
                $WbOrderDTO->setBarcode($barcode);
                $dateCreated = new DateTimeImmutable($order['createdAt']);
                $WbOrderDTO->setCreated($dateCreated);
                $WbOrderDTO->setStatus(new WbOrderStatusNew());
                $WbOrderDTO->setWildberries(new WildberriesStatusWaiting());

                if($order['user'])
                {
                    $WbOrderClientDTO = $WbOrderDTO->getClient();
                    $WbOrderClientDTO->setUsername($order['user']['fio']);
                    $WbOrderClientDTO->setPhone($order['user']['phone']);
                    $WbOrderClientDTO->setEmail(new ClientEmail($order['userInfo']['email']));

                    if($order['address'])
                    {
                        $address = implode(', ', $order['address']);
                        $WbOrderClientDTO->setAddress($address);
                    }
                }

                /* Сохраняем */
                $WildberriesOrderResult = $this->WildberriesOrderHandler->handle($WbOrderDTO);

                if(!$WildberriesOrderResult instanceof WbOrders)
                {
                    $OrderStatusDTO = new OrderStatusDTO(
                        new OrderStatusCanceled(),
                        $Order->getEvent(),
                        $profile
                    );

                    $this->orderStatusHandler->handle($OrderStatusDTO);
                }


                $io->text(sprintf('Добавили новый заказ %s', $order['id']));

            }
        }

        $io->success('Заказы успешно обновлены');
        return Command::SUCCESS;


        /**
         * Делаем перебор всех токенов профилей
         *
         * @var UserProfileUid $token
         */
        foreach($this->settingsTokens->get() as $token)
        {
            //$httpClient = new WbTokenAuth($token->getName());
            //$wb = new WbOrders($httpClient);

            //			ProgressBar::setFormatDefinition('custom', ' %current%/%max% -- %message%');
            //			$progressBar = new ProgressBar($output);
            //			$progressBar->setFormat('custom');
            //			$progressBar->setMessage(sprintf('Token: %s', $token->getValue()));
            //			$progressBar->start();


            //            /** Авторизация Wildberries */
            //            $wildberriesAuthorization = $this->wildberriesAuthorization->auth($token);


            //			sleep(5); /** Делаем задержку между запросами */
            //			$newOrders = (new GetOrderNew($wildberriesAuthorization))->orders();
            //dd('newOrders');

            /* Метод получает все новые сборочные задания */
            //$orders = $wb->getOrdersNew();

            //            if($newOrders->isError())
            //            {
            //                $io->error($newOrders->getMessage());
            //                continue;
            //            }

            //            $orders = $newOrders->getContent();
            //
            //            if($orders === null || empty($orders))
            //            {
            //                continue;
            //            }

            //foreach($orders as $order)
            //{
            //$progressBar->advance();

            /* Проверяем, имеется ли в системе заказ WB с указанным идентификатором */
            //                /** @var WbOrdersEvent $WbOrder */
            //                $WbOrder = $this->wbOrdersById->get($order['id']);
            //
            //                /* Если заказ существует - пропускам */
            //                if(!empty($WbOrder))
            //                {
            //                    continue;
            //                }

            //                /* Получаем карточку по штрихкоду */
            //                $barcode = current($order['skus']);
            //                $card = $this->entityManager->getRepository(WbProductCardVariation::class)->find($barcode);

            //                if(empty($card))
            //                {
            //
            //                    $WbProductCardOfferRemove = $this->entityManager->getRepository(WbProductCardOffer::class)->find($order['nmId']);
            //
            //                    if($WbProductCardOfferRemove)
            //                    {
            //                        $WbProductCardRemove = $this->entityManager->getRepository(WbProductCard::class)->find($WbProductCardOfferRemove->getCard());
            //                        $this->entityManager->remove($WbProductCardRemove);
            //                    }
            //
            //                    $io->error(sprintf('Отсутствует карточка товара Wildberries barcode: %s', $barcode));
            //                    dump($order);
            //                    continue;
            //                }

            //                /* Получаем продукт по ТП */
            //                $offerConst = $card->getOfferConst();
            //                $product = $this->productByOffer->get($offerConst);

            //                if(!$product)
            //                {
            //                    /* TODO: добавить уведомление администраторам */
            //                    $io->error(sprintf('Отсутствует продукция barcode: %s', $barcode));
            //                    continue;
            //                }

            //                $dateCreated = new DateTimeImmutable($order['createdAt']);
            //
            //                /* Создаем системный заказ */
            //                $ProductOrderDTO = new ProductOrderDTO($token, $product, $offerConst);
            //                $ProductOrderDTO->setCreated($dateCreated);
            //
            //                $ProductPriceDTO = $ProductOrderDTO->getPrice();
            //                $ProductPriceDTO->setPrice(($order['convertedPrice'] / 100));
            //                $ProductPriceDTO->setTotal(1); /* Всегда количество 1 */
            //
            //                /* Сохраняем */
            //                $OrderEntity = $this->orderAggregate->handle($ProductOrderDTO);

            //                /* Создаем Wildberries заказ */
            //                $WbOrderDTO = new WbOrderDTO($token, $order['id']);
            //                $WbOrderDTO->setOrders($OrderEntity);
            //                $WbOrderDTO->setBarcode($barcode);
            //                $WbOrderDTO->setCreated($dateCreated);
            //                $WbOrderDTO->setStatus(WbOrderStatusEnum::NEW);

            //                if($order['user'])
            //                {
            //                    $WbOrderClientDTO = $WbOrderDTO->getClient();
            //                    $WbOrderClientDTO->setUsername($order['user']['fio']);
            //                    $WbOrderClientDTO->setPhone($order['user']['phone']);
            //                    $WbOrderClientDTO->setEmail(new ClientEmail($order['userInfo']['email']));
            //                    $WbOrderClientDTO->setStatus(WbClientStatusEnum::NEW);
            //
            //                    if($order['address'])
            //                    {
            //                        $address = implode(', ', $order['address']);
            //                        $WbOrderClientDTO->setAddress($address);
            //                    }
            //                }

            //            /* Сохраняем */
            //            $WbOrderEntity = $this->wbOrdersAggregate->handle($WbOrderDTO);
            //
            //            $io->text(sprintf('Добавили новый заказ %s', $order['id']));


            //}

            $progressBar->finish();

        }

        $io->success('Заказы успешно обновлены');

        return 0;
    }

}
