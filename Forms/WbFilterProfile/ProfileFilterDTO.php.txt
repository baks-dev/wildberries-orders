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
use Symfony\Component\HttpFoundation\Request;

final class ProfileFilterDTO implements ProfileFilterInterface
{
    public const profile = 'tqaXgcfueo';

    private Request $request;

    /**
     * Профиль пользователя
     */
    private ?UserProfileUid $profile;


    public function __construct(Request $request, ?UserProfileUid $profile)
    {
        $this->request = $request;

        if($this->request->isMethod('POST'))
        {
            if($this->request->get('profile_filter_form_admin'))
            {
                if(empty($this->request->get('profile_filter_form_admin')['profile']))
                {
                    $this->request->getSession()->remove(self::profile);
                    $this->profile = $profile;
                    return;
                }
            }

            if($this->request->get('profile_filter_form'))
            {
                if(empty($this->request->get('profile_filter_form')['profile']))
                {
                    $this->request->getSession()->remove(self::profile);
                    $this->profile = $profile;
                    return;
                }
            }
        }

        $this->profile = $this->request->getSession()->get(self::profile) ?: $profile;
    }

    /**
     * Профиль пользователя
     */
    public function getProfile(): ?UserProfileUid
    {
        return $this->profile ?: $this->request->getSession()->get(self::profile);
    }

    public function setProfile(?UserProfileUid $profile): void
    {

        if($profile === null)
        {
            $this->request->getSession()->remove(self::profile);
            return;
        }

        $this->profile = $profile;
    }
}
