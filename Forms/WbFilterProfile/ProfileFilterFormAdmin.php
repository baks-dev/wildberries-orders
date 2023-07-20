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

namespace BaksDev\Wildberries\Orders\Forms\WbFilterProfile;

use BaksDev\Products\Category\Repository\CategoryChoice\CategoryChoiceInterface;
use BaksDev\Users\Profile\UserProfile\Type\Id\UserProfileUid;
use BaksDev\Wildberries\Orders\Type\OrderStatus\Status\Collection\WbOrderStatusCollection;
use BaksDev\Wildberries\Orders\Type\WildberriesStatus\Status\Collection\WildberriesStatusCollection;
use BaksDev\Wildberries\Repository\WbTokenChoice\WbTokenChoiceInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class ProfileFilterFormAdmin extends AbstractType
{

    private RequestStack $request;

    private WbTokenChoiceInterface $tokenChoice;


    public function __construct(
        WbTokenChoiceInterface $tokenChoice,
        RequestStack $request,
        WbOrderStatusCollection $wbOrderStatusCollection,
        WildberriesStatusCollection $wildberriesStatusCollection,
        CategoryChoiceInterface $categoryChoice,
    )
    {
        $this->request = $request;
        $this->tokenChoice = $tokenChoice;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /**
         * Профиль пользователя
         */
        $AccessProfileTokenCollection = $this->tokenChoice->getTokenCollection();
        $builder->add('profile', HiddenType::class);


        $builder->addEventListener(
            FormEvents::PRE_SET_DATA,
            function(FormEvent $event) use ($AccessProfileTokenCollection): void {
                /** @var ProfileFilterDTO $data */
                $data = $event->getData();
                $form = $event->getForm();

                if(count($AccessProfileTokenCollection) === 1)
                {
                    $data->setProfile(current($AccessProfileTokenCollection));
                    //return;
                }

                $form->add('profile', ChoiceType::class, [
                    'choices' => $AccessProfileTokenCollection,
                    'choice_value' => function(?UserProfileUid $profile) {
                        return $profile?->getValue();
                    },
                    'choice_label' => function(UserProfileUid $profile) {
                        return $profile->getAttr();
                    },
                    'label' => false,
                ]);

            }
        );


        $builder->addEventListener(
            FormEvents::POST_SUBMIT,
            function(FormEvent $event): void {
                /** @var ProfileFilterDTO $data */
                $data = $event->getData();
                $this->request->getSession()->set(ProfileFilterDTO::profile, $data->getProfile());
            }
        );
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ProfileFilterDTO::class,
            'method' => 'POST',
            'attr' => ['class' => 'w-100'],
        ]);
    }
}