<?php
/**
 * @LICENSE_TEXT
 */

namespace Che\HttpApiAuth\Bundle;

use Che\HttpApiAuth\Bundle\DependencyInjection\Security\HttpHeaderSecurityFactory;
use Symfony\Bundle\SecurityBundle\DependencyInjection\SecurityExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * Class HttpApiAuthBundle
 *
 * @author Kirill chEbba Chebunin <iam@chebba.org>
 */
class HttpApiAuthBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        if ($container->hasExtension('security')) {
            /** @var SecurityExtension $extension */
            $extension = $container->getExtension('security');
            $extension->addSecurityListenerFactory(new HttpHeaderSecurityFactory());
        }
    }
}