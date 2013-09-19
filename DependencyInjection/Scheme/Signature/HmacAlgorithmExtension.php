<?php
/*
 * Copyright (c)
 * Kirill chEbba Chebunin <iam@chebba.org>
 *
 * This source file is subject to the MIT license that is bundled
 * with this package in the file LICENSE.
 */

namespace Che\HttpApiAuth\Bundle\DependencyInjection\Scheme\Signature;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\DefinitionDecorator;

/**
 * Class HmacAlgorithmConfiguration
 *
 * @author Kirill chEbba Chebunin <iam@chebba.org>
 */
class HmacAlgorithmExtension implements SignatureAlgorithmExtension
{
    const DEFAULT_HASH_ALGORITHM = 'sha256';

    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return 'hmac';
    }

    /**
     * {@inheritDoc}
     */
    public function addAlgorithmConfig(ArrayNodeDefinition $node)
    {
        $node
            ->children()
                ->scalarNode('hash')->defaultValue(self::DEFAULT_HASH_ALGORITHM)->end()//TODO: use enum, use hmac constant
                ->booleanNode('binary')->defaultValue(true)->end()
            ->end();
    }

    /**
     * {@inheritDoc}
     */
    public function createAlgorithm($id, array $config, ContainerBuilder $container)
    {
        $container
            ->setDefinition($id, new DefinitionDecorator('http_api_auth.scheme.signature.algorithm.hmac_prototype'))
            ->replaceArgument(0, $config['hash'])
            ->replaceArgument(1, $config['binary'])
        ;
    }
}
