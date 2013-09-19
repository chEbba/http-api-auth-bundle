<?php
/*
 * Copyright (c)
 * Kirill chEbba Chebunin <iam@chebba.org>
 *
 * This source file is subject to the MIT license that is bundled
 * with this package in the file LICENSE.
 */

namespace Che\HttpApiAuth\Bundle\Tests\DependencyInjection;

use Che\HttpApiAuth\Bundle\DependencyInjection\Configuration;
use Che\HttpApiAuth\SchemeHandler;
use PHPUnit_Framework_TestCase as TestCase;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Processor;

/**
 * Class ConfigurationTest
 *
 * @author Kirill chEbba Chebunin <iam@chebba.org>
 */
class ConfigurationTest extends TestCase
{
    /**
     * @var Configuration
     */
    private $config;
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    private $schemeExtension;

    /**
     * Setup configuration
     */
    protected function setUp()
    {
        $this->schemeExtension = $this->getMock('Che\HttpApiAuth\Bundle\DependencyInjection\Scheme\SchemeExtension');
        $this->schemeExtension
            ->expects($this->any())
            ->method('getName')
            ->will($this->returnValue('foo'))
        ;
        $this->config = new Configuration([$this->schemeExtension]);
    }

    /**
     * @test full configuration
     */
    public function fullConfiguration()
    {
        $this->schemeExtension
            ->expects($this->once())
            ->method('addSchemeConfig')
            ->with($this->callback(function(ArrayNodeDefinition $node) {
                $this->assertEquals('foo', $node->getNode()->getName());

                return true;
            }))
            ->will($this->returnCallback(function(ArrayNodeDefinition $node) {
                $node->children()->scalarNode('bar');
            }))
        ;

        $config = $this->processConfiguration([
            'schemes' => [
                'protocol' => [
                    'foo' => [
                        'bar' => 'baz'
                    ]
                ]
            ],
            'handlers' => [
                'handler1' => [
                    'schemes' => [
                        'protocol' => [
                            'custom_name' => 'custom'
                        ]
                    ],
                    'credentials_header' => 'credentials',
                    'scheme_header' => 'scheme'
                ]
            ]
        ]);

        $this->assertEquals($config, [
            'schemes' => [
                'protocol' => [
                    'type' => 'foo',
                    'parameters' => [
                        'bar' => 'baz'
                    ]
                ]
            ],
            'handlers' => [
                'handler1' => [
                    'schemes' => [
                        'protocol' => [
                            'custom_name' => 'custom'
                        ]
                    ],
                    'credentials_header' => 'credentials',
                    'scheme_header' => 'scheme'
                ]
            ]
        ]);
    }

    /**
     * @test default parameters from scheme are added when none specified
     */
    public function defaultSchemeParameters()
    {
        $this->schemeExtension
            ->expects($this->once())
            ->method('addSchemeConfig')
            ->will($this->returnCallback(function(ArrayNodeDefinition $node) {
                $node->children()->scalarNode('bar')->defaultValue('baz');
            }))
        ;

        $config = $this->processConfiguration([
            'schemes' => [
                'protocol' => [
                    'foo' => []
                ]
            ],
            'handlers' => [
                'handler1' => [
                    'schemes' => [
                        'protocol' => [
                            'custom_name' => 'custom'
                        ]
                    ],
                    'credentials_header' => 'credentials',
                    'scheme_header' => 'scheme'
                ]
            ]
        ]);

        $this->assertEquals($config, [
            'schemes' => [
                'protocol' => [
                    'type' => 'foo',
                    'parameters' => [
                        'bar' => 'baz'
                    ]
                ]
            ],
            'handlers' => [
                'handler1' => [
                    'schemes' => [
                        'protocol' => [
                            'custom_name' => 'custom'
                        ]
                    ],
                    'credentials_header' => 'credentials',
                    'scheme_header' => 'scheme'
                ]
            ]
        ]);
    }

    /**
     * @test default parameters for handler are added
     */
    public function defaultHandlerParameters()
    {
        $config = $this->processConfiguration([
            'schemes' => [
                'protocol' => [
                    'foo' => []
                ]
            ],
            'handlers' => [
                'handler1' => [
                    'schemes' => [
                        'protocol' => []
                    ]
                ]
            ]
        ]);

        $this->assertEquals($config, [
            'schemes' => [
                'protocol' => [
                    'type' => 'foo',
                    'parameters' => []
                ]
            ],
            'handlers' => [
                'handler1' => [
                    'schemes' => [
                        'protocol' => [
                            'custom_name' => null
                        ]
                    ],
                    'credentials_header' => SchemeHandler::DEFAULT_CREDENTIALS_HEADER,
                    'scheme_header' => SchemeHandler::DEFAULT_SCHEME_HEADER
                ]
            ]
        ]);
    }

    private function processConfiguration(array $config)
    {
        $processor = new Processor();

        return $processor->processConfiguration($this->config, ['http_api_auth' => $config]);
    }
}
