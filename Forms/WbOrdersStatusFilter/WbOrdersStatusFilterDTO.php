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

use BaksDev\Wildberries\Orders\Type\OrderStatus\Status\Collection\WbOrderStatusInterface;
use BaksDev\Wildberries\Orders\Type\WildberriesStatus\Status\Collection\WildberriesStatusInterface;
use Symfony\Component\HttpFoundation\Request;

final class WbOrdersStatusFilterDTO implements WbOrdersStatusFilterInterface
{
    public const status = 'YlbCMGkeVb';
    public const wildberries = 'XTLedgbcTp';

    private Request $request;


    /**
     * Статус сборочного задания
     */
    private ?WbOrderStatusInterface $status = null;

    /**
     * Внутренний статус Wildberries
     */
    private ?WildberriesStatusInterface $wildberries = null;


    public function __construct(Request $request)
    {
        $this->request = $request;

    }


    /**
     * Status
     */
    public function getStatus(): ?WbOrderStatusInterface
    {
        return $this->status ?: $this->request->getSession()->get(self::status);

    }

    public function setStatus(?WbOrderStatusInterface $status): void
    {
        if($status === null)
        {
            $this->request->getSession()->remove(self::status);
        }

        $this->status = $status;
    }


    /**
     * Wildberries
     */
    public function getWildberries(): ?WildberriesStatusInterface
    {
        return $this->wildberries ?: $this->request->getSession()->get(self::wildberries);
    }

    public function setWildberries(?WildberriesStatusInterface $wildberries): void
    {
        if($wildberries === null)
        {
            $this->request->getSession()->remove(self::wildberries);
        }

        $this->wildberries = $wildberries;
    }


}
