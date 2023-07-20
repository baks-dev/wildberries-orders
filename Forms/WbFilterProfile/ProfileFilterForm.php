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

use BaksDev\Users\Profile\UserProfile\Type\Id\UserProfileUid;
use BaksDev\Wildberries\Repository\WbTokenChoice\WbTokenChoiceInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class ProfileFilterForm extends AbstractType
{

    private RequestStack $request;

    private WbTokenChoiceInterface $tokenChoice;

    public function __construct(
        WbTokenChoiceInterface $tokenChoice,
        RequestStack $request,
    )
    {
        $this->request = $request;
        $this->tokenChoice = $tokenChoice;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {

        $AccessProfileTokenCollection = $this->tokenChoice->getAccessProfileTokenCollection();

        if($AccessProfileTokenCollection)
        {
            $builder->add('profile', ChoiceType::class, [
                'choices' => $this->tokenChoice->getAccessProfileTokenCollection(),
                'choice_value' => function(?UserProfileUid $profile) {
                    return $profile?->getValue();
                },
                'choice_label' => function(UserProfileUid $profile) {
                    return $profile->getAttr();
                },
                'label' => false,
            ]);
        }
        else
        {
            $builder->add('profile', HiddenType::class);
        }


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