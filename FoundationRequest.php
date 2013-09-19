<?php
/**
 * @LICENSE_TEXT
 */

namespace Che\HttpApiAuth\Bundle;

use Che\HttpApiAuth\HttpRequest;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class FoundationRequest
 *
 * @author Kirill chEbba Chebunin <iam@chebba.org>
 */
class FoundationRequest implements HttpRequest
{
    private $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    /**
     * {@inheritDoc}
     */
    public function getMethod()
    {
        return $this->request->getRealMethod();
    }

    /**
     * {@inheritDoc}
     */
    public function getHeaders()
    {
        $headers = $this->request->headers->all();
        // Header names already converted to lowercase
        foreach ($headers as &$value) {
            $value = implode(', ', $value);
        }

        return $headers;
    }

    /**
     * {@inheritDoc}
     */
    public function getUri()
    {
        return $this->request->getRequestUri();
    }

    /**
     * {@inheritDoc}
     */
    public function getHost()
    {
        return $this->request->getHost();
    }

    /**
     * {@inheritDoc}
     */
    public function getBody()
    {
        return $this->request->getContent();
    }
}