<?php
/*
 * Copyright (c)
 * Kirill chEbba Chebunin <iam@chebba.org>
 *
 * This source file is subject to the MIT license that is bundled
 * with this package in the file LICENSE.
 */

namespace Che\HttpApiAuth\Bundle\DependencyInjection\Scheme;

use Che\HttpApiAuth\Bundle\DependencyInjection\Scheme\Signature\SignatureAlgorithmExtension;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\DefinitionDecorator;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Configuration for RequestSignatureScheme
 *
 * @see RequestSignatureScheme
 *
 * @author Kirill chEbba Chebunin <iam@chebba.org>
 * @license http://opensource.org/licenses/mit-license.php MIT
 */
class RequestSignatureExtension implements SchemeExtension
{
    /** @var SignatureAlgorithmExtension[] */
    private $algorithmExtensions = [];

    public function getName()
    {
        return 'signature';
    }

    /**
     * @param SignatureAlgorithmExtension[] $algorithmExtensions
     */
    public function __construct(array $algorithmExtensions)
    {
        foreach ($algorithmExtensions as $algorithmExtension) {
            $this->addAlgorithmExtension($algorithmExtension);
        }
    }

    private function addAlgorithmExtension(SignatureAlgorithmExtension $algorithmConfiguration)
    {
        $this->algorithmExtensions[$algorithmConfiguration->getName()] = $algorithmConfiguration;
    }

    /**
     * {@inheritDoc}
     */
    public function addSchemeConfig(ArrayNodeDefinition $node)
    {
        /** @var ArrayNodeDefinition $algorithms */
        $algorithms = $node
            ->children()
                ->booleanNode('encoded')->defaultValue(true)->end()
                ->scalarNode('lifetime')->defaultValue(600)->end()
                ->arrayNode('algorithm')
                    ->cannotBeEmpty()
                    ->isRequired()
                    ->validate()
                        ->always(function ($algorithm) {
                            if (count($algorithm) !== 1) {
                                throw new \InvalidArgumentException(sprintf(
                                    'Expected exactly 1 algorithm type. Got %d: %s',
                                    count($algorithm), implode(', ', array_keys($algorithm))
                                ));
                            }

                            return ['type' => key($algorithm), 'parameters' => current($algorithm)];
                        })
                    ->end()
        ;

        foreach ($this->algorithmExtensions as $algorithmExtension) {
            $algorithmConfig = new ArrayNodeDefinition($algorithmExtension->getName());
            $algorithmExtension->addAlgorithmConfig($algorithmConfig);
            $algorithms->append($algorithmConfig);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function createScheme($id, array $config, ContainerBuilder $container)
    {
        $algorithmType = $config['algorithm']['type'];
        $algorithmId = sprintf('%s.algorithm.%s', $id, $algorithmType);

        $this->algorithmExtensions[$algorithmType]->createAlgorithm(
            $algorithmId,
            $config['algorithm']['parameters'],
            $container
        );

        $container
            ->setDefinition($id, new DefinitionDecorator('http_api_auth.scheme.signature_prototype'))
            ->replaceArgument(0, new Reference($algorithmId))
            ->replaceArgument(1, $config['encoded'])
            ->replaceArgument(2, $config['lifetime'])
        ;
    }
}
