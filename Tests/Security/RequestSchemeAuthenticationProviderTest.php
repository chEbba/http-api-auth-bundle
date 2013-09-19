<?php
/*
 * Copyright (c)
 * Kirill chEbba Chebunin <iam@chebba.org>
 *
 * This source file is subject to the MIT license that is bundled
 * with this package in the file LICENSE.
 */

namespace Che\HttpApiAuth\Bundle\Tests\Security;

use Che\HttpApiAuth\Bundle\Security\BadRequestCredentialsException;
use Che\HttpApiAuth\Bundle\Security\RequestSchemeAuthenticationProvider;
use Che\HttpApiAuth\Bundle\Security\RequestSchemeToken;
use PHPUnit_Framework_TestCase as TestCase;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

/**
 * Class RequestSchemeAuthenticationProviderTest
 *
 * @author Kirill chEbba Chebunin <iam@chebba.org>
 */
class RequestSchemeAuthenticationProviderTest extends TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $userProvider;
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $handler;
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $userChecker;
    /**
     * @var RequestSchemeAuthenticationProvider
     */
    private $provider;

    /**
     * Setup provider and dependency mocks
     */
    protected function setUp()
    {
        $this->userProvider = $this->getMock('Symfony\Component\Security\Core\User\UserProviderInterface');
        $this->handler = $this->getMockBuilder('Che\HttpApiAuth\SchemeHandler')->disableOriginalConstructor()->getMock();
        $this->userChecker = $this->getMock('Symfony\Component\Security\Core\User\UserCheckerInterface');
        $this->provider = new RequestSchemeAuthenticationProvider(
            $this->userProvider,
            $this->handler,
            $this->userChecker,
            'key'
        );
    }

    /**
     * @test __construct with empty provider throws exception
     * @expectedException InvalidArgumentException
     */
    public function providerKeyNotEmpty()
    {
        new RequestSchemeAuthenticationProvider(
            $this->userProvider,
            $this->handler,
            $this->userChecker,
            ''
        );
    }

    /**
     * @test supports only scheme token with same providerKey
     * @dataProvider supportedTokens
     */
    public function supportsRequestSchemeToken(TokenInterface $token, $supported = true)
    {
        $this->assertEquals($supported, $this->provider->supports($token));
    }

    public function supportedTokens()
    {
        return [
            [$this->createToken()],
            [$this->createToken('foo'), false],
            [$this->getMock('Symfony\Component\Security\Core\Authentication\Token\TokenInterface'), false]
        ];
    }

    /**
     * @test if token is not supported by provider exception is thrown
     */
    public function unsupportedTokenAuth()
    {
        try {
            $this->provider->authenticate($this->getMock('Symfony\Component\Security\Core\Authentication\Token\TokenInterface'));
        } catch (AuthenticationException $e) {
            return;
        }

        $this->fail('Exception was not thrown');
    }

    /**
     * @test handler validates request with password as secret key
     */
    public function checkRequest()
    {
        $token = $this->createToken();
        $this->createUser();

        $this->handler
            ->expects($this->once())
            ->method('isRequestValid')
            ->with($token->getRequest(), $token->getData(), 'pass')
            ->will($this->returnValue(true))
        ;

        $token = $this->provider->authenticate($token);
        $this->assertTrue($token->isAuthenticated());
    }

    /**
     * @test userChecker is called before and after request validation
     */
    public function checkUser()
    {
        $token = $this->createToken();
        $user = $this->createUser();

        $call = 0;

        $this->userChecker
            ->expects($this->once())
            ->method('checkPreAuth')
            ->with($user)
            ->will($this->returnCallback(function () use (&$call) {
                $this->assertEquals(0, $call);
                $call++;
            }))
        ;
        $this->handler
            ->expects($this->once())
            ->method('isRequestValid')
            ->with()
            ->will($this->returnCallback(function () use (&$call) {
                $this->assertEquals(1, $call);
                $call++;

                return true;
            }))
        ;
        $this->userChecker
            ->expects($this->once())
            ->method('checkPostAuth')
            ->with($user)
            ->will($this->returnCallback(function () use (&$call) {
                $this->assertEquals(2, $call);
                $call++;
            }))
        ;

        $this->provider->authenticate($token);
    }

    /**
     * @test authenticate exception on invalid request
     */
    public function invalidRequest()
    {
        $token = $this->createToken();
        $this->createUser();

        $this->handler
            ->expects($this->any())
            ->method('isRequestValid')
            ->will($this->returnValue(false))
        ;

        try {
            $this->provider->authenticate($token);
        } catch (BadRequestCredentialsException $e) {
            return;
        }

        $this->fail('Exception was not thrown');
    }

    /**
     * @test if requestValidation throws exception generic auth exception is rethrown
     */
    public function validationException()
    {
        $token = $this->createToken();
        $this->createUser();

        $this->handler
            ->expects($this->any())
            ->method('isRequestValid')
            ->will($this->throwException($previous = new \OutOfBoundsException()))
        ;

        try {
            $this->provider->authenticate($token);
        } catch (AuthenticationException $e) {
            $this->assertSame($previous, $e->getPrevious());

            return;
        }

        $this->fail('Exception was not thrown');
    }

    /**
     * @param string $providerKey
     *
     * @return \PHPUnit_Framework_MockObject_MockObject|RequestSchemeToken
     */
    private function createToken($providerKey = 'key')
    {
        $token = $this->getMockBuilder('Che\HttpApiAuth\Bundle\Security\RequestSchemeToken')
            ->disableOriginalConstructor()
            ->getMock()
        ;
        $token
            ->expects($this->any())
            ->method('getProviderKey')
            ->will($this->returnValue($providerKey))
        ;
        $token->expects($this->any())
            ->method('getUsername')
            ->will($this->returnValue('user_name'))
        ;
        $token
            ->expects($this->any())
            ->method('getRequest')
            ->will($this->returnValue($request = $this->getMock('Che\HttpApiAuth\HttpRequest')))
        ;
        $token
            ->expects($this->any())
            ->method('getData')
            ->will($this->returnValue($data = $this->getMockBuilder('Che\HttpApiAuth\AuthenticationData')->disableOriginalConstructor()->getMock()))
        ;

        return $token;
    }

    private function createUser()
    {
        $user = $this->getMock('Symfony\Component\Security\Core\User\UserInterface');
        $user
            ->expects($this->any())
            ->method('getPassword')
            ->will($this->returnValue('pass'))
        ;
        $user
            ->expects($this->any())
            ->method('getRoles')
            ->will($this->returnValue(['ROLE1']))
        ;
        $this->userProvider
            ->expects($this->once())
            ->method('loadUserByUsername')
            ->with('user_name')
            ->will($this->returnValue($user))
        ;

        return $user;
    }
}
