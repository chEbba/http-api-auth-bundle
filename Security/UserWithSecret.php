<?php
/*
 * Copyright (c)
 * Kirill chEbba Chebunin <iam@chebba.org>
 *
 * This source file is subject to the MIT license that is bundled
 * with this package in the file LICENSE.
 */

namespace Che\HttpApiAuth\Bundle\Security;

use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Description of UserWithSecret
 *
 * @author Kirill chEbba Chebunin <iam@chebba.org>
 */
interface UserWithSecret extends UserInterface
{
    public function getSecretKey();
}
