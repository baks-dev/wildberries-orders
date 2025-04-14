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

namespace BaksDev\Wildberries\Orders\Commands\Upgrade\FBO;

use BaksDev\Core\Type\Field\InputField;
use BaksDev\Delivery\Entity\Delivery;
use BaksDev\Delivery\Repository\ExistTypeDelivery\ExistTypeDeliveryInterface;
use BaksDev\Delivery\Type\Id\DeliveryUid;
use BaksDev\Delivery\UseCase\Admin\NewEdit\DeliveryDTO;
use BaksDev\Delivery\UseCase\Admin\NewEdit\DeliveryHandler;
use BaksDev\Delivery\UseCase\Admin\NewEdit\Fields\DeliveryFieldDTO;
use BaksDev\Delivery\UseCase\Admin\NewEdit\Fields\Trans\DeliveryFieldTransDTO;
use BaksDev\Delivery\UseCase\Admin\NewEdit\Trans\DeliveryTransDTO;
use BaksDev\Reference\Currency\Type\Currency;
use BaksDev\Reference\Money\Type\Money;
use BaksDev\Users\Profile\TypeProfile\Type\Id\TypeProfileUid;
use BaksDev\Wildberries\Orders\Type\DeliveryType\TypeDeliveryFboWildberries;
use BaksDev\Wildberries\Orders\Type\ProfileType\TypeProfileFboWildberries;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Contracts\Translation\TranslatorInterface;

#[AsCommand(
    name: 'baks:delivery:wildberries-dbs',
    description: 'Добавляет собственную доставку клиенту Wildberries'
)]
class UpgradeDeliveryTypeFboWildberriesCommand extends Command
{
    public function __construct(
        private readonly ExistTypeDeliveryInterface $existTypeDelivery,
        private readonly TranslatorInterface $translator,
        private readonly DeliveryHandler $deliveryHandler
    )
    {
        parent::__construct();
    }

    /** Добавляет доставку Wildberries */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $DeliveryUid = new DeliveryUid(TypeDeliveryFboWildberries::class);

        /** Проверяем наличие доставки Wildberries */
        $exists = $this->existTypeDelivery->isExists($DeliveryUid);

        if($exists)
        {
            return Command::SUCCESS;
        }


        $io = new SymfonyStyle($input, $output);
        $io->text('Добавляем доставку продукции FBO Wildberries');

        $DeliveryDTO = new DeliveryDTO($DeliveryUid);

        $DeliveryDTO->setType(new TypeProfileUid(TypeProfileFboWildberries::class));
        $DeliveryDTO->setSort(TypeDeliveryFboWildberries::priority());


        /** Бесплатная доставка */
        $Money = new Money(0);
        $DeliveryPriceDTO = $DeliveryDTO->getPrice();
        $DeliveryPriceDTO->setPrice($Money);
        $DeliveryPriceDTO->setExcess($Money);
        $DeliveryPriceDTO->setCurrency(new Currency());


        $DeliveryTransDTO = $DeliveryDTO->getTranslate();

        /**
         * Присваиваем настройки локали типа профиля
         *
         * @var DeliveryTransDTO $DeliveryTrans
         */
        foreach($DeliveryTransDTO as $DeliveryTrans)
        {
            $name = $this->translator->trans('wildberries.fbo.name', domain: 'delivery.type', locale: $DeliveryTrans->getLocal()->getLocalValue());
            $desc = $this->translator->trans('wildberries.fbo.desc', domain: 'delivery.type', locale: $DeliveryTrans->getLocal()->getLocalValue());

            $DeliveryTrans->setName($name);
            $DeliveryTrans->setDescription($desc);
        }


        /**
         * Создаем пользовательское поле с адресом доставки
         */
        $DeliveryFieldDTO = new DeliveryFieldDTO();
        $DeliveryFieldDTO->setSort(100);
        $DeliveryFieldDTO->setType(new InputField('address_field'));

        /** @var DeliveryFieldTransDTO $DeliveryFieldTrans */
        foreach($DeliveryFieldDTO->getTranslate() as $DeliveryFieldTrans)
        {
            $name = $this->translator->trans('wildberries.fbo.address.name', domain: 'delivery.type', locale: $DeliveryFieldTrans->getLocal()->getLocalValue());
            $desc = $this->translator->trans('wildberries.fbo.address.desc', domain: 'delivery.type', locale: $DeliveryFieldTrans->getLocal()->getLocalValue());

            $DeliveryFieldTrans->setName($name);
            $DeliveryFieldTrans->setDescription($desc);
        }

        $DeliveryDTO->addField($DeliveryFieldDTO);

        $handle = $this->deliveryHandler->handle($DeliveryDTO);

        if(!$handle instanceof Delivery)
        {
            $io->error(sprintf('Ошибка %s при добавлении способа доставки', $handle));
            return Command::FAILURE;
        }


        return Command::SUCCESS;
    }

    /** Чам выше число - тем первым в итерации будет значение */
    public static function priority(): int
    {
        return 99;
    }

}
