<?php
/*
 * Copyright (c)
 * Kirill chEbba Chebunin <iam@chebba.org>
 *
 * This source file is subject to the MIT license that is bundled
 * with this package in the file LICENSE.
 */

namespace Che\HttpApiAuth\Bundle\Security;

use Che\HttpApiAuth\SchemeHandler;
use Symfony\Component\Security\Core\Authentication\Provider\AuthenticationProviderInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

/**
 * Class RequestSchemeAuthenticationProvider
 *
 * @author Kirill chEbba Chebunin <iam@chebba.org>
 */
class RequestSchemeAuthenticationProvider implements AuthenticationProviderInterface
{
    private $userProvider;
    private $schemeHandler;

    public function __construct(UserProviderInterface $userProvider, SchemeHandler $schemeHandler,
                                UserCheckerInterface $userChecker, $providerKey)
    {
        $this->userProvider = $userProvider;
        $this->schemeHandler = $schemeHandler;

        if (empty($providerKey)) {
            throw new \InvalidArgumentException('$providerKey must not be empty.');
        }

        $this->userChecker = $userChecker;
        $this->providerKey = $providerKey;
    }

    /**
     * {@inheritDoc}
     */
    public function supports(TokenInterface $token)
    {
        return $token instanceof RequestSchemeToken && $this->providerKey === $token->getProviderKey();
    }

    /**
     * {@inheritDoc}
     */
    public function authenticate(TokenInterface $token)
    {
        if (!$this->supports($token)) {
            throw new AuthenticationException(sprintf('Unsupported token "%s(%s)"', get_class($token), $token->serialize()));
        }
        /** @var RequestSchemeToken $token */

        $username = $token->getUsername();
        $user = $this->userProvider->loadUserByUsername($username);

        $this->userChecker->checkPreAuth($user);

        $secretKey = $user instanceof UserWithSecret ? $user->getSecretKey() : $user->getPassword();
        try {
            $valid = $this->schemeHandler->isRequestValid($token->getRequest(), $token->getData(), $secretKey);
        } catch (\RuntimeException $e) {
            throw new AuthenticationException('Request validation failed with an exception', 0, $e);
        }
        if (!$valid) {
            throw new BadRequestCredentialsException($token);
        }

        $this->userChecker->checkPostAuth($user);

        return new RequestSchemeToken($token->getRequest(), $token->getData(), $token->getProviderKey(), $user);
    }
}
