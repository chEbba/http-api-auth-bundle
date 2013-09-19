<?php
/*
 * Copyright (c)
 * Kirill chEbba Chebunin <iam@chebba.org>
 *
 * This source file is subject to the MIT license that is bundled
 * with this package in the file LICENSE.
 */

namespace Che\HttpApiAuth\Bundle\Tests\DependencyInjection\Security;

use Che\HttpApiAuth\Bundle\DependencyInjection\HttpApiAuthExtension;
use Che\HttpApiAuth\Bundle\DependencyInjection\Security\HttpHeaderSecurityFactory;
use Che\HttpApiAuth\SchemeHandler;
use PHPUnit_Framework_TestCase as TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Test for HttpHeaderSecurityFactory
 *
 * @author Kirill chEbba Chebunin <iam@chebba.org>
 */
class HttpHeaderSecurityFactoryTest extends TestCase
{
    /**
     * @var HttpHeaderSecurityFactory
     */
    private $factory;
    /**
     * @var ContainerBuilder
     */
    private $container;

    /**
     * Setup factory and container
     */
    protected function setUp()
    {
        $this->factory = new HttpHeaderSecurityFactory();
        $this->container = new ContainerBuilder();
        (new HttpApiAuthExtension())->load(['http_api_auth' => [
            'schemes' => [
                'sign' => [
                    'signature' => ['algorithm' => ['hmac' => []]]
                ]
            ],
            'handlers' => [
                'api' => [
                    'schemes' => [
                        'sign' => []
                    ]
                ]
            ]
        ]], $this->container);

        $this->container->set('user_provider', $this->getMock('Symfony\Component\Security\Core\User\UserProviderInterface'));
        $this->container->set('security.context', $this->getMock('Symfony\Component\Security\Core\SecurityContextInterface'));
        $this->container->set('security.authentication.manager', $this->getMock('Symfony\Component\Security\Core\Authentication\AuthenticationManagerInterface'));
    }

    /**
     * @test create return [providerId, listenerId, defaultEntryPoint]
     */
    public function createIds()
    {
        $ids = $this->createListenerFromConfig(false);

        $this->assertEquals([
            'security.authentication.provider.request_scheme.foo',
            'security.authentication.listener.http_header.foo',
            'entry'
        ], $ids);
    }

    /**
     * @test create HttpHeaderListener
     */
    public function createListener()
    {
        $this->createListenerFromConfig();

        $listener = $this->container->get('security.authentication.listener.http_header.foo');

        $this->assertInstanceOf('Che\HttpApiAuth\Bundle\Security\HttpHeaderAuthenticationListener', $listener);
    }

    private function createListenerFromConfig($compile = true)
    {
        $result = $this->factory->create(
            $this->container, 'foo',
            ['handler' => 'api'],
            'user_provider', 'entry'
        );

        if ($compile) {
            $this->container->getCompilerPassConfig()->setRemovingPasses([]);
            $this->container->compile();
        }

        return $result;
    }
}
