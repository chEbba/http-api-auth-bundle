<?php
/*
 * Copyright (c)
 * Kirill chEbba Chebunin <iam@chebba.org>
 *
 * This source file is subject to the MIT license that is bundled
 * with this package in the file LICENSE.
 */

namespace Che\HttpApiAuth\Bundle\Tests\Security;

use Che\HttpApiAuth\AuthenticationData;
use Che\HttpApiAuth\Bundle\Security\RequestSchemeToken;
use Che\HttpApiAuth\HttpRequest;
use Che\HttpApiAuth\RequestToken;
use PHPUnit_Framework_TestCase as TestCase;
use Symfony\Component\Security\Core\Role\Role;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Class RequestSchemeTokenTest
 *
 * @author Kirill chEbba Chebunin <iam@chebba.org>
 */
class RequestSchemeTokenTest extends TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|HttpRequest
     */
    private $request;
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|RequestToken
     */
    private $requestToken;
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|UserInterface
     */
    private $user;

    /**
     * Setup request, requestToken and user
     */
    protected function setUp()
    {
        $this->request = $this->getMock('Che\HttpApiAuth\HttpRequest');
        foreach (['host', 'uri', 'method', 'body'] as $property) {
            $this->request
                ->expects($this->any())
                ->method('get'.ucfirst($property))
                ->will($this->returnValue($property))
            ;
        }
        $this->request
            ->expects($this->any())
            ->method('getHeaders')
            ->will($this->returnValue(['header' => 'value']))
        ;

        $this->requestToken = $this->getMock('Che\HttpApiAuth\RequestToken');
        $this->requestToken
            ->expects($this->any())
            ->method('getUsername')
            ->will($this->returnValue('user_name'))
        ;

        $this->user = $this->getMock('Symfony\Component\Security\Core\User\UserInterface');
    }

    /**
     * @test __construct without user for unauthenticated token
     */
    public function unauthenticatedToken()
    {
        $token = $this->createToken();

        $this->assertSame($this->request, $token->getRequest());
        $this->assertEquals('Scheme', $token->getData()->getScheme());
        $this->assertSame($this->requestToken, $token->getData()->getToken());
        $this->assertEquals('pKey', $token->getProviderKey());
        $this->assertEquals('user_name', $token->getUser());

        $this->assertEmpty($token->getRoles());
        $this->assertFalse($token->isAuthenticated());
    }

    /**
     * @test __construct authenticated token with user instance
     */
    public function authenticatedToken()
    {
        $this->user
            ->expects($this->any())
            ->method('getRoles')
            ->will($this->returnValue($roles = [new Role('ROLE1'), new Role('ROLE2')]))
        ;

        $token = $this->createToken(true);

        $this->assertSame($this->user, $token->getUser());
        $this->assertEquals($roles, $token->getRoles());
        $this->assertTrue($token->isAuthenticated());
    }

    /**
     * @test serialize data, providerKey and parent properties
     */
    public function serializeSaveProperties()
    {
        $token = $this->createToken();
        $token->setAttributes(['foo' => 'bar']);


        /** @var RequestSchemeToken $newToken */
        $newToken = unserialize(serialize($token));

        $this->assertEquals($token->getData(), $newToken->getData());
        $this->assertEquals($token->getProviderKey(), $newToken->getProviderKey());
        $this->assertEquals($token->getAttributes(), $newToken->getAttributes());
    }

    /**
     * @test serialize copy request to serializable CustomRequest
     */
    public function serializeCopyRequest()
    {
        $token = $this->createToken();

        /** @var RequestSchemeToken $newToken */
        $newToken = unserialize(serialize($token));

        $this->assertInstanceOf('Che\HttpApiAuth\CustomRequest', $newToken->getRequest());
    }

    /**
     * @test __toString has scheme
     */
    public function schemeString()
    {
        $this->assertContains('Scheme', $this->createToken()->__toString());
    }

    /**
     * Create token
     *
     * @param bool $user
     *
     * @return RequestSchemeToken
     */
    private function createToken($user = false)
    {
        return new RequestSchemeToken(
            $this->request,
            new AuthenticationData('Scheme', $this->requestToken),
            'pKey',
            $user ? $this->user : null
        );
    }
}
