<?php
/*
 * Copyright (c)
 * Kirill chEbba Chebunin <iam@chebba.org>
 *
 * This source file is subject to the MIT license that is bundled
 * with this package in the file LICENSE.
 */

namespace Che\HttpApiAuth\Bundle\Security;

use Symfony\Component\Security\Core\Exception\AuthenticationException;

/**
 * Class BadRequestCredentialsException
 *
 * @author Kirill chEbba Chebunin <iam@chebba.org>
 */
class BadRequestCredentialsException extends AuthenticationException
{
    /**
     * @param RequestSchemeToken $token
     */
    public function __construct(RequestSchemeToken $token)
    {
        parent::__construct($this->getMessageKey().' '. $token);

        $this->setToken($token);
    }

    public function getMessageKey()
    {
        return 'Request is not valid for auth scheme with provided credentials.';
    }
}
