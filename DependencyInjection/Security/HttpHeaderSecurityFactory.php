<?php
/*
 * Copyright (c)
 * Kirill chEbba Chebunin <iam@chebba.org>
 *
 * This source file is subject to the MIT license that is bundled
 * with this package in the file LICENSE.
 */

namespace Che\HttpApiAuth\Bundle\DependencyInjection\Security;

use Che\HttpApiAuth\Scheme\Signature\Algorithm\HmacSignature;
use Che\HttpApiAuth\SchemeHandler;
use Symfony\Component\Config\Definition\Builder\NodeBuilder;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\DefinitionDecorator;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Bundle\SecurityBundle\DependencyInjection\Security\Factory\SecurityFactoryInterface;

/**
 * Security factory for http api authorization
 *
 * @author Kirill chEbba Chebunin <iam@chebba.org>
 * @license http://opensource.org/licenses/mit-license.php MIT
 */
class HttpHeaderSecurityFactory implements SecurityFactoryInterface
{
    /**
     * {@inheritDoc}
     */
    public function getPosition()
    {
        return 'http';
    }

    public function getKey()
    {
        return 'http-header';
    }

    /**
     * {@inheritDoc}
     */
    public function addConfiguration(NodeDefinition $node)
    {
        $schemeChildren =
            $node->children()
                ->scalarNode('provider')->end()
                ->scalarNode('handler')->isRequired()->end()
            ;
    }

    /**
     * {@inheritDoc}
     */
    public function create(ContainerBuilder $container, $id, $config, $userProvider, $defaultEntryPoint)
    {
        $handlerId = sprintf('http_api_auth.handler.%s', $config['handler']);
        $providerId = 'security.authentication.provider.request_scheme.'.$id;
        $container
            ->setDefinition($providerId, new DefinitionDecorator('http_api_auth.security.provider.request_scheme_prototype'))
            ->replaceArgument(0, new Reference($userProvider))
            ->replaceArgument(1, new Reference($handlerId))
            ->replaceArgument(3, $id)
        ;

        $listenerId = 'security.authentication.listener.http_header.'.$id;
        $container
            ->setDefinition($listenerId, new DefinitionDecorator('http_api_auth.security.listener.http_header_prototype'))
            ->replaceArgument(2, new Reference($handlerId))
            ->replaceArgument(3, $id)
        ;

        return [$providerId, $listenerId, $defaultEntryPoint];
    }
}
