<?php
/*
 * Copyright (c)
 * Kirill chEbba Chebunin <iam@chebba.org>
 *
 * This source file is subject to the MIT license that is bundled
 * with this package in the file LICENSE.
 */

namespace Che\HttpApiAuth\Bundle\Security;

use Che\HttpApiAuth\AuthenticationData;
use Che\HttpApiAuth\CustomRequest;
use Che\HttpApiAuth\HttpRequest;
use Symfony\Component\Security\Core\Authentication\Token\AbstractToken;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Token for api requests
 *
 * @author Kirill chEbba Chebunin <iam@chebba.org>
 * @license http://opensource.org/licenses/mit-license.php MIT
 */
class RequestSchemeToken extends AbstractToken
{
    private $request;
    private $data;
    private $providerKey;

    public function __construct(HttpRequest $request, AuthenticationData $data, $providerKey, UserInterface $user = null)
    {
        $this->request = $request;
        $this->data = $data;
        $this->providerKey = $providerKey;

        if ($user) {
            parent::__construct($user->getRoles());
            $this->setUser($user);
            $this->setAuthenticated(count($user->getRoles()));
        } else {
            parent::__construct();
            $this->setUser($data->getToken()->getUsername());
        }
    }

    /**
     * @return HttpRequest
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * @return AuthenticationData
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * {@inheritDoc}
     */
    public function getCredentials()
    {
        return $this->data->getToken()->getCredentials();
    }

    /**
     * @return string
     */
    public function getProviderKey()
    {
        return $this->providerKey;
    }

    /**
     * {@inheritdoc}
     */
    public function serialize()
    {
        $request = $this->request instanceof \Serializable ? $this->request : CustomRequest::copy($this->request);

        return serialize([
            'request' => $request,
            'data' => $this->data,
            'providerKey' => $this->providerKey,
            'properties' => parent::serialize()
        ]);
    }

    /**
     * {@inheritDoc}
     */
    public function unserialize($serialized)
    {
        $data = unserialize($serialized);
        $this->request = $data['request'];
        $this->data = $data['data'];
        $this->providerKey = $data['providerKey'];

        parent::unserialize($data['properties']);
    }

    /**
     * {@inheritDoc}
     */
    public function __toString()
    {
        return sprintf('%s: %s', $this->data->getScheme(), parent::__toString());
    }
}
