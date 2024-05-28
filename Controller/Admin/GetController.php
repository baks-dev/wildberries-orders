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

namespace BaksDev\Wildberries\Orders\Controller\Admin;

use BaksDev\Core\Cache\AppCacheInterface;
use BaksDev\Core\Controller\AbstractController;
use BaksDev\Core\Listeners\Event\Security\RoleSecurity;
use BaksDev\Core\Messenger\MessageDispatchInterface;
use BaksDev\Wildberries\Orders\Forms\Get\WbOrdersGetForm;
use BaksDev\Wildberries\Orders\Messenger\NewOrders\NewOrdersMessage;
use BaksDev\Wildberries\Products\Forms\Get\WbProductCardGetForm;
use DateInterval;
use Psr\Cache\CacheItemInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Cache\CacheInterface;

#[AsController]
#[RoleSecurity('ROLE_WB_ORDERS_GET')]
final class GetController extends AbstractController
{
    #[Route('/admin/wb/order/get', name: 'admin.get', methods: ['GET', 'POST'])]
    public function Update(
        Request $request,
        AppCacheInterface $cache,
        MessageDispatchInterface $messageDispatch
    ): Response
    {
        $form = $this->createForm(WbOrdersGetForm::class, null, [
            'action' => $this->generateUrl('wildberries-orders:admin.get'),
        ]);
        $form->handleRequest($request);

        if($form->isSubmitted() && $form->has('wb_orders_get'))
        {
            $this->refreshTokenForm($form);

            /**
             * Предотвращаем обновление чаще раз в 5 минут
             * @var CacheInterface $AppCache
             */
            $AppCache = $cache->init('WildberriesOrdersUpgrade');
            /** @var CacheItemInterface $item */
            $item = $AppCache->getItem((string) $this->getProfileUid());

            if(!$item->isHit())
            {
                $item->set(true);
                $item->expiresAfter(DateInterval::createFromDateString('5 minutes'));
                $AppCache->save($item);

                /* Отправляем сообщение в шину профиля */
                $messageDispatch->dispatch(
                    message: new NewOrdersMessage($this->getProfileUid()),
                    transport: (string) $this->getProfileUid(),
                );

                $this->addFlash
                (
                    'admin.page.get',
                    'admin.success.get',
                    'admin.wb.orders',
                );

            }
            else
            {
                $this->addFlash
                (
                    'admin.page.get',
                    'admin.danger.get',
                    'admin.wb.orders'
                );
            }


            return $this->redirectToRoute('wildberries-orders:admin.index');
        }

        return $this->render(['form' => $form->createView()]);
    }
}
