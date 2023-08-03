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

namespace BaksDev\Wildberries\Orders\Forms\WbOrdersStatusFilter;

use BaksDev\Wildberries\Orders\Type\OrderStatus\Status\Collection\WbOrderStatusCollection;
use BaksDev\Wildberries\Orders\Type\OrderStatus\Status\Collection\WbOrderStatusInterface;
use BaksDev\Wildberries\Orders\Type\WildberriesStatus\Status\Collection\WildberriesStatusCollection;
use BaksDev\Wildberries\Orders\Type\WildberriesStatus\Status\Collection\WildberriesStatusInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class WbOrdersStatusFilterForm extends AbstractType
{

    private RequestStack $request;

    private WildberriesStatusCollection $wildberriesStatusCollection;
    private WbOrderStatusCollection $wbOrderStatusCollection;

    public function __construct(
        RequestStack $request,
        WbOrderStatusCollection $wbOrderStatusCollection,
        WildberriesStatusCollection $wildberriesStatusCollection,

    )
    {
        $this->request = $request;
        $this->wildberriesStatusCollection = $wildberriesStatusCollection;
        $this->wbOrderStatusCollection = $wbOrderStatusCollection;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /**
         * Статус сборочного задания
         */
        $builder->add('status', ChoiceType::class, [
            'choices' => $this->wbOrderStatusCollection->cases(),
            'choice_value' => function(?WbOrderStatusInterface $status) {
                return $status?->getValue();
            },
            'choice_label' => function(WbOrderStatusInterface $status) {
                return $status->getValue();
            },
            'label' => false,
            'required' => false,
            'translation_domain' => 'admin.wildberries.orders'
        ]);


        /**
         * Внутренний статус Wildberries
         */
        $builder->add('wildberries', ChoiceType::class, [
            'choices' => $this->wildberriesStatusCollection->cases(),
            'choice_value' => function(?WildberriesStatusInterface $wildberries) {
                return $wildberries?->getValue();
            },
            'choice_label' => function(WildberriesStatusInterface $wildberries) {
                return $wildberries->getValue();
            },
            'label' => false,
            'required' => false,
            'translation_domain' => 'admin.wildberries.orders'
        ]);


        $builder->addEventListener(
            FormEvents::POST_SUBMIT,
            function(FormEvent $event): void {
                /** @var WbOrdersStatusFilterDTO $data */
                $data = $event->getData();

                $this->request->getSession()->set(WbOrdersStatusFilterDTO::status, $data->getStatus());
                $this->request->getSession()->set(WbOrdersStatusFilterDTO::wildberries, $data->getWildberries());


            }
        );

    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => WbOrdersStatusFilterDTO::class,
            'validation_groups' => false,
            'method' => 'POST',
            'attr' => ['class' => 'w-100'],
        ]);
    }
}