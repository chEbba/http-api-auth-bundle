<?php
/*
 * Copyright (c)
 * Kirill chEbba Chebunin <iam@chebba.org>
 *
 * This source file is subject to the MIT license that is bundled
 * with this package in the file LICENSE.
 */

namespace Che\HttpApiAuth\Bundle\Tests\DependencyInjection;

use Che\HttpApiAuth\Bundle\DependencyInjection\Scheme\RequestSignatureExtension;
use PHPUnit_Framework_TestCase as TestCase;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\Processor;

/**
 * Test for RequestSignatureExtension
 *
 * @author Kirill chEbba Chebunin <iam@chebba.org>
 * @license http://opensource.org/licenses/mit-license.php MIT
 */
class RequestSignatureExtensionTest extends TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    private $algorithmExtension;
    /** @var RequestSignatureExtension */
    private $extension;

    protected function setUp()
    {
        $this->algorithmExtension = $this->getMock(
            'Che\HttpApiAuth\Bundle\DependencyInjection\Scheme\Signature\SignatureAlgorithmExtension'
        );
        $this->algorithmExtension
            ->expects($this->any())
            ->method('getName')
            ->will($this->returnValue('algo'))
        ;

        $this->extension = new RequestSignatureExtension([$this->algorithmExtension]);
    }

    /**
     * @test load algorithm config and convert it to format ['type' => name, 'parameters' => array]
     */
    public function algorithmExtensionConfig()
    {
        $this->algorithmExtension
            ->expects($this->once())
            ->method('addAlgorithmConfig')
            ->with($this->callback(function (ArrayNodeDefinition $node) {
                $this->assertEquals('algo', $node->getNode()->getName());

                return true;
            }))
            ->will($this->returnCallback(function (ArrayNodeDefinition $node) {
                $node->children()->scalarNode('parameter');
            }));
        ;

        $config = $this->processConfig([
            'algorithm' => [
                'algo' => [
                    'parameter' => 'value'
                ]
            ]
        ]);

        $this->assertArrayHasKey('algorithm', $config);
        $this->assertEquals([
            'type' => 'algo',
            'parameters' => [
                'parameter' => 'value'
            ]
        ], $config['algorithm']);
    }

    /**
     * @test full config options
     */
    public function fullConfig()
    {
        $config = $this->processConfig([
            'encoded' => false,
            'lifetime' => 111,
            'algorithm' => [
                'algo' => []
            ]
        ]);

        $this->assertEquals([
            'encoded' => false,
            'lifetime' => 111,
            'algorithm' => [
                'type' => 'algo',
                'parameters' => []
            ]
        ], $config);
    }

    /**
     * @test defaultConfig values are added
     */
    public function defaultConfiguration()
    {
        $config = $this->processConfig([
            'algorithm' => [
                'algo' => []
            ]
        ]);

        $this->assertEquals([
            'encoded' => true,
            'lifetime' => 600, // TODO: use constant
            'algorithm' => [
                'type' => 'algo',
                'parameters' => []
            ]
        ], $config);
    }

    private function processConfig(array $config)
    {
        $builder = new TreeBuilder();
        $this->extension->addSchemeConfig($builder->root('scheme_ext'));

        return (new Processor())->process($builder->buildTree(), ['scheme_ext' => $config]);
    }
}
