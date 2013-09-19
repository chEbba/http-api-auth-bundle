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
use Che\HttpApiAuth\Bundle\Security\HttpHeaderAuthenticationListener;
use Che\HttpApiAuth\HeaderNotFoundException;
use Che\HttpApiAuth\HttpHeader;
use Che\HttpApiAuth\SchemeHandler;
use Che\HttpApiAuth\WrongSchemeHeaderException;
use PHPUnit_Framework_MockObject_MockObject as MockObject;
use PHPUnit_Framework_TestCase as TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

/**
 * Test for HttpHeaderAuthenticationListener
 *
 * @author Kirill chEbba Chebunin <iam@chebba.org>
 * @license http://opensource.org/licenses/mit-license.php MIT
 */
class HttpHeaderAuthenticationListenerTest extends TestCase
{
    /**
     * @var MockObject
     */
    private $securityContext;
    /**
     * @var MockObject
     */
    private $authManager;
    /**
     * @var MockObject
     */
    private $handler;
    /**
     * @var HttpHeaderAuthenticationListener
     */
    private $listener;

    /**
     * Setup listener and mock dependencies
     */
    protected function setUp()
    {
        $this->securityContext = $this->getMock('Symfony\Component\Security\Core\SecurityContextInterface');
        $this->authManager = $this->getMock('Symfony\Component\Security\Core\Authentication\AuthenticationManagerInterface');
        $this->handler = $this->getMockBuilder('Che\HttpApiAuth\SchemeHandler')->disableOriginalConstructor()->getMock();
        $this->listener = new HttpHeaderAuthenticationListener(
            $this->securityContext,
            $this->authManager,
            $this->handler,
            'key'
        );
    }

    /**
     * @test handle parses request and authenticate token with authManager
     */
    public function authManagerToken()
    {
        $data = new AuthenticationData('Scheme', $this->createRequestToken());
        $this->handler
            ->expects($this->once())
            ->method('parseRequest')
            ->with($this->isInstanceOf('Che\HttpApiAuth\Bundle\FoundationRequest'))
            ->will($this->returnValue($data))
        ;
        $this->authManager
            ->expects($this->once())
            ->method('authenticate')
            ->with($this->isInstanceOf('Che\HttpApiAuth\Bundle\Security\RequestSchemeToken'))
            ->will($this->returnValue($token = $this->getMock('Symfony\Component\Security\Core\Authentication\Token\TokenInterface')))
        ;
        $this->securityContext
            ->expects($this->once())
            ->method('setToken')
            ->with($token)
        ;

        $event = $this->createEvent();
        $this->listener->handle($event);
    }

    /**
     * @test if no auth header was found 401 response with scheme header is created
     */
    public function noHeaderResponse()
    {
        $this->handler
            ->expects($this->once())
            ->method('parseRequest')
            ->will($this->throwException(new HeaderNotFoundException('credentials')))
        ;
        $this->handler
            ->expects($this->once())
            ->method('createDefaultSchemeHeader')
            ->will($this->returnValue(new HttpHeader('foo', 'bar')))
        ;

        $event = $this->createEvent();
        $event
            ->expects($this->once())
            ->method('setResponse')
            ->with($this->callback(function ($response) {
                return $response instanceof Response
                    && $response->getStatusCode() === 401
                    && $response->headers->has('foo')
                    && $response->headers->get('foo') === 'bar'
                ;
            }))
        ;

        $this->listener->handle($event);
    }

    /**
     * @test if handler throws exception, 403 response is generated
     */
    public function requestParseError()
    {
        $this->handler
            ->expects($this->exactly(2))
            ->method('parseRequest')
            ->will($this->onConsecutiveCalls(
                $this->throwException(new \OutOfBoundsException()),
                $this->throwException(new WrongSchemeHeaderException('header', 'reason'))
            ))
        ;

        $event = $this->createEvent();
        $this->expectFail($event, 2);

        $this->listener->handle($event);
        $this->listener->handle($event);
    }

    /**
     * @test if authManager throws exception, 403 response is generated
     */
    public function authErrors()
    {
        $data = new AuthenticationData('Scheme', $this->createRequestToken());
        $this->handler
            ->expects($this->once())
            ->method('parseRequest')
            ->with($this->isInstanceOf('Che\HttpApiAuth\Bundle\FoundationRequest'))
            ->will($this->returnValue($data))
        ;

        $this->authManager
            ->expects($this->once())
            ->method('authenticate')
            ->will($this->throwException(new AuthenticationException()))
        ;

        $event = $this->createEvent();
        $this->expectFail($event);

        $this->listener->handle($event);
    }

    private function createRequestToken()
    {
        $token = $this->getMock('Che\HttpApiAuth\RequestToken');
        $token
            ->expects($this->any())
            ->method('getUsername')
            ->will($this->returnValue('user_name'))
        ;

        return $token;
    }

    private function createEvent()
    {
        $event = $this
            ->getMockBuilder('Symfony\Component\HttpKernel\Event\GetResponseEvent')
            ->disableOriginalConstructor()
            ->getMock()
        ;
        $event
            ->expects($this->any())
            ->method('getRequest')
            ->will($this->returnValue(new Request()))
        ;

        return $event;
    }

    private function expectFail(MockObject $event, $count = 1)
    {
        $this->securityContext
            ->expects($this->exactly($count))
            ->method('setToken')
            ->with(null)
        ;
        $event
            ->expects($this->exactly($count))
            ->method('setResponse')
            ->with($this->callback(function ($response) {
                return $response instanceof Response
                && $response->getStatusCode() === 403;
            }))
        ;
    }
}
