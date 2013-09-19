<?php
/*
 * Copyright (c)
 * Kirill chEbba Chebunin <iam@chebba.org>
 *
 * This source file is subject to the MIT license that is bundled
 * with this package in the file LICENSE.
 */

namespace Che\HttpApiAuth\Bundle\DependencyInjection;

use Che\HttpApiAuth\Bundle\DependencyInjection\Scheme\SchemeExtension;
use Che\HttpApiAuth\SchemeHandler;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * Class Configuration
 *
 * @author Kirill chEbba Chebunin <iam@chebba.org>
 */
class Configuration implements ConfigurationInterface
{
    /**
     * @var SchemeExtension[]
     */
    private $schemeExtensions = [];

    /**
     * @param SchemeExtension[] $schemeExtensions
     */
    public function __construct(array $schemeExtensions)
    {
        foreach ($schemeExtensions as $schemeExtension) {
            $this->addSchemeExtension($schemeExtension);
        }
    }

    private function addSchemeExtension(SchemeExtension $schemeExtension)
    {
        $this->schemeExtensions[] = $schemeExtension;
    }

    /**
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder()
    {
        $builder = new TreeBuilder();
        $root = $builder->root('http_api_auth');

        $schemes = $root->children()
            ->arrayNode('handlers')
                ->useAttributeAsKey('name')
                ->prototype('array')
                    ->children()
                        ->arrayNode('schemes')
                            ->useAttributeAsKey('name')
                            ->prototype('array')
                                ->children()
                                    ->scalarNode('custom_name')->defaultNull()->end()
                                ->end()
                            ->end()
                        ->end()
                        ->scalarNode('credentials_header')->defaultValue(SchemeHandler::DEFAULT_CREDENTIALS_HEADER)->end()
                        ->scalarNode('scheme_header')->defaultValue(SchemeHandler::DEFAULT_SCHEME_HEADER)->end()
                    ->end()
                ->end()
            ->end()
            ->arrayNode('schemes')
                ->useAttributeAsKey('name')
                ->prototype('array')
                    ->validate()
                        ->always()
                        ->then(function ($scheme) {
                            if (count($scheme) !== 1) {
                                throw new \InvalidArgumentException(sprintf(
                                    'Expected exactly 1 scheme type. Got %d: %s',
                                    count($scheme), implode(', ', array_keys($scheme))
                                ));
                            }

                            return ['type' => key($scheme), 'parameters' => current($scheme)];
                        })
                    ->end()
        ;

        foreach ($this->schemeExtensions as $schemeConfiguration) {
            $schemeConfig = new ArrayNodeDefinition($schemeConfiguration->getName());
            $schemeConfiguration->addSchemeConfig($schemeConfig);
            $schemes
                ->append($schemeConfig);
        }

        return $builder;
    }
}
