<?php
/*
 * Copyright (c)
 * Kirill chEbba Chebunin <iam@chebba.org>
 *
 * This source file is subject to the MIT license that is bundled
 * with this package in the file LICENSE.
 */

namespace Che\HttpApiAuth\Bundle\Security;

use Che\HttpApiAuth\Bundle\FoundationRequest;
use Che\HttpApiAuth\HeaderNotFoundException;
use Che\HttpApiAuth\SchemeHandler;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\Security\Core\Authentication\AuthenticationManagerInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\SecurityContextInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Http\Firewall\ListenerInterface;

/**
 * Request listener to handle http API authorization
 *
 * @author Kirill chEbba Chebunin <iam@chebba.org>
 * @license http://opensource.org/licenses/mit-license.php MIT
 */
class HttpHeaderAuthenticationListener implements ListenerInterface
{
    private $securityContext;
    private $authManager;
    private $schemeHandler;
    private $providerKey;
    private $logger;

    public function __construct(SecurityContextInterface $securityContext,
                                AuthenticationManagerInterface $authManager,
                                SchemeHandler $schemeHandler, $providerKey)
    {
        $this->securityContext = $securityContext;
        $this->authManager = $authManager;
        $this->schemeHandler = $schemeHandler;
        $this->providerKey = $providerKey;
//        $this->logger = LoggerFactory::getLogger(__CLASS__);
    }

    /**
     * Handle HTTP API request
     *
     * @param GetResponseEvent $event
     */
    public function handle(GetResponseEvent $event)
    {
        $request = new FoundationRequest($event->getRequest());

        try {
            $data = $this->schemeHandler->parseRequest($request);
        } catch (HeaderNotFoundException $e) {
            $header = $this->schemeHandler->createDefaultSchemeHeader();
            $this->fail($event, $e, new Response('', 401, [$header->getName() => $header->getValue()]));

            return;
        } catch (\RuntimeException $e) {
            $this->fail($event, $e);

            return;
        }

        $token = new RequestSchemeToken($request, $data, $this->providerKey);
        try {
            $token = $this->authManager->authenticate($token);
        } catch (AuthenticationException $e) {
            $this->fail($event, $e);

            return;
        }

        $this->securityContext->setToken($token);
    }

    private function fail(GetResponseEvent $event, \Exception $error, Response $response = null)
    {
        $this->securityContext->setToken(null);

        if (null !== $this->logger) {
            $this->logger->info(sprintf('Authentication fail', ['exception' => $error]));
        }

        $event->setResponse($response ?: new Response('', '403'));
    }
}
