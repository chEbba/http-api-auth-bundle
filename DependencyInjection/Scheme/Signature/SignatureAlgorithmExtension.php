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

/**
 * Algorithm configuration for RequestSignature scheme
 *
 * @author Kirill chEbba Chebunin <iam@chebba.org>
 * @license http://opensource.org/licenses/mit-license.php MIT
 */
interface SignatureAlgorithmExtension
{
    /**
     * @return string
     */
    public function getName();

    /**
     * @param ArrayNodeDefinition $node
     */
    public function addAlgorithmConfig(ArrayNodeDefinition $node);

    /**
     * @param string           $id
     * @param array            $config
     * @param ContainerBuilder $container
     */
    public function createAlgorithm($id, array $config, ContainerBuilder $container);
}
