<?php
/*
 * Copyright (c)
 * Kirill chEbba Chebunin <iam@chebba.org>
 *
 * This source file is subject to the MIT license that is bundled
 * with this package in the file LICENSE.
 */

namespace Che\HttpApiAuth\Bundle\DependencyInjection;

use Che\HttpApiAuth\Bundle\DependencyInjection\Scheme\RequestSignatureExtension;
use Che\HttpApiAuth\Bundle\DependencyInjection\Scheme\SchemeExtension;
use Che\HttpApiAuth\Bundle\DependencyInjection\Scheme\Signature\HmacAlgorithmExtension;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Config\Resource\FileResource;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\DefinitionDecorator;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\DependencyInjection\ConfigurableExtension;

/**
 * Class HttpApiAuthExtension
 *
 * @author Kirill chEbba Chebunin <iam@chebba.org>
 */
class HttpApiAuthExtension extends ConfigurableExtension
{
    /** @var SchemeExtension[] */
    private $schemeExtensions;

    public function __construct()
    {
        $this
            ->addSchemeExtension(new RequestSignatureExtension([
                new HmacAlgorithmExtension()
            ]))
        ;
    }

    private function addSchemeExtension(SchemeExtension $schemeExtension)
    {
        $this->schemeExtensions[$schemeExtension->getName()] = $schemeExtension;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    protected function loadInternal(array $mergedConfig, ContainerBuilder $container)
    {
        $loader = new XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.xml');

        foreach ($mergedConfig['schemes'] as $name => $schemeConfig) {
            $id = $this->getSchemeId($name);
            $this->schemeExtensions[$schemeConfig['type']]->createScheme($id, $schemeConfig['parameters'], $container);
        }

        foreach ($mergedConfig['handlers'] as $name => $handlerConfig) {
            $handlerId = sprintf('%s.handler.%s', $this->getAlias(), $name);
            $handler = $container
                ->setDefinition($handlerId, new DefinitionDecorator('http_api_auth.scheme_handler_prototype'))
                ->replaceArgument(0, $handlerConfig['credentials_header'])
                ->replaceArgument(1, $handlerConfig['scheme_header'])
            ;

            foreach ($handlerConfig['schemes'] as $schemeName => $schemeOptions) {
                $handler->addMethodCall('registerScheme', [new Reference($this->getSchemeId($schemeName)), $schemeOptions['custom_name']]);
            }
        }
    }

    public function getConfiguration(array $config, ContainerBuilder $container)
    {
        $container->addResource(new FileResource(__DIR__ . '/Configuration.php'));

        return new Configuration($this->schemeExtensions);
    }

    private function getSchemeId($schemeName)
    {
        return sprintf('%s.scheme.%s', $this->getAlias(), $schemeName);
    }
}
