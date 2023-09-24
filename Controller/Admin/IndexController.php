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

use BaksDev\Core\Controller\AbstractController;
use BaksDev\Core\Form\Search\SearchDTO;
use BaksDev\Core\Form\Search\SearchForm;
use BaksDev\Core\Listeners\Event\Security\RoleSecurity;
use BaksDev\Wildberries\Orders\Forms\WbOrdersProductFilter\WbOrdersProductFilterDTO;
use BaksDev\Wildberries\Orders\Forms\WbOrdersProductFilter\WbOrdersProductFilterForm;
use BaksDev\Wildberries\Orders\Forms\WbOrdersStatusFilter\WbOrdersStatusFilterDTO;
use BaksDev\Wildberries\Orders\Forms\WbOrdersStatusFilter\WbOrdersStatusFilterForm;
use BaksDev\Wildberries\Orders\Repository\AllWbOrders\AllWbOrdersInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Annotation\Route;

#[AsController]
#[RoleSecurity('ROLE_WB_ORDERS')]
final class IndexController extends AbstractController
{
    #[Route('/admin/wb/orders/{page<\d+>}', name: 'admin.index', methods: ['GET', 'POST'])]
    public function index(
        Request $request,
        AllWbOrdersInterface $allWbOrders,
        int $page = 0,
    ): Response
    {
        /**
         * Поиск
         */

        $search = new SearchDTO($request);
        $searchForm = $this->createForm(
            SearchForm::class, $search, [
                'action' => $this->generateUrl('WildberriesOrders:admin.index'),
            ]
        );
        $searchForm->handleRequest($request);


        /**
         * Фильтр профиля пользователя
         */

//        $profile = new ProfileFilterDTO($request, $this->getProfileUid());
//        $ROLE_ADMIN = $this->isGranted('ROLE_ADMIN');
//
//        if($ROLE_ADMIN)
//        {
//            $profileForm = $this->createForm(ProfileFilterFormAdmin::class, $profile, [
//                'action' => $this->generateUrl('WildberriesOrders:admin.index'),
//            ]);
//        }
//        else
//        {
//            $profileForm = $this->createForm(ProfileFilterForm::class, $profile, [
//                'action' => $this->generateUrl('WildberriesOrders:admin.index'),
//            ]);
//        }

//        $profileForm->handleRequest($request);
//        !$profileForm->isSubmitted()?:$this->redirectToReferer();



        /**
         * Фильтр товаров
         */

        $filter = new WbOrdersProductFilterDTO($request);
        $filterForm = $this->createForm(WbOrdersProductFilterForm::class, $filter, [
            'action' => $this->generateUrl('WildberriesOrders:admin.index'),
        ]);
        $filterForm->handleRequest($request);
        !$filterForm->isSubmitted()?:$this->redirectToReferer();



        /**
         * Фильтр товаров
         */

        $status = new WbOrdersStatusFilterDTO($request);
        $statusForm = $this->createForm(WbOrdersStatusFilterForm::class, $status, [
            'action' => $this->generateUrl('WildberriesOrders:admin.index'),
        ]);
        $statusForm->handleRequest($request);
        !$statusForm->isSubmitted()?:$this->redirectToReferer();


        

        /**
         * Получаем список
         */

        $WbOrders = $allWbOrders->fetchAllWbOrdersAssociative($search, $this->getProfileUid(), $filter, $status);

        return $this->render(
            [
                'query' => $WbOrders,
                'search' => $searchForm->createView(),
                //'profile' => $profileForm->createView(),
                'filter' => $filterForm->createView(),
                'status' => $statusForm->createView(),
            ]
        );
    }
}
