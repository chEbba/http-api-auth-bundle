<?php
/*
 * Copyright (c)
 * Kirill chEbba Chebunin <iam@chebba.org>
 *
 * This source file is subject to the MIT license that is bundled
 * with this package in the file LICENSE.
 */

namespace Che\HttpApiAuth\Bundle\Tests\DependencyInjection\Signature;

use Che\HttpApiAuth\Bundle\DependencyInjection\Scheme\Signature\HmacAlgorithmExtension;
use PHPUnit_Framework_TestCase as TestCase;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\Processor;

/**
 * Test for HmacAlgorithmExtension
 *
 * @author Kirill chEbba Chebunin <iam@chebba.org>
 * @license http://opensource.org/licenses/mit-license.php MIT
 */
class HmacAlgorithmExtensionTest extends TestCase
{
    /** @var  HmacAlgorithmExtension */
    private $extension;

    protected function setUp()
    {
        $this->extension = new HmacAlgorithmExtension();
    }

    /**
     * @test all configuration parameters
     */
    public function fullConfiguration()
    {
        $this->assertEquals(
            [
                'hash' => 'md5',
                'binary' => false
            ],
            $this->processConfig([
                'hash' => 'md5',
                'binary' => false
            ])
        );
    }

    /**
     * @test default configuration parameters
     */
    public function defaultConfiguration()
    {
        $this->assertEquals(
            [
                'hash' => HmacAlgorithmExtension::DEFAULT_HASH_ALGORITHM,
                'binary' => true
            ],
            $this->processConfig([])
        );
    }

    private function processConfig(array $config)
    {
        $builder = new TreeBuilder();
        $this->extension->addAlgorithmConfig($builder->root('hmac'));

        return (new Processor())->process($builder->buildTree(), ['hmac' => $config]);
    }
}
