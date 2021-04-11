<?php

namespace Wwwision\GraphQL\Http\Middleware;

use GuzzleHttp\Psr7\Response;
use Neos\Flow\Annotations as Flow;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * A simple HTTP Component that captures OPTIONS requests and responds with a general "Allow: GET, POST" header if a matching graphQL endpoint is configured
 */
class HttpOptionsMiddleware implements MiddlewareInterface
{
    /**
     * @Flow\InjectConfiguration(path="endpoints")
     * @var array
     */
    protected $endpoints;

    /**
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $next
     * @return ResponseInterface
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $next): ResponseInterface
    {
        // no OPTIONS request => skip
        if (!in_array($request->getMethod(), ["OPTIONS", "POST"])) {
            return $next->handle($request);
        }

        $endpoint = ltrim($request->getUri()->getPath(), '\/');

        // no matching graphQL endpoint configured => skip
        if (!isset($this->endpoints[$endpoint])) {
            return $next->handle($request);
        }

        $headers = [
            // @todo #52 add a way to have a dynamic configuration here
            'Access-Control-Allow-Origin' => 'https://medialib-ui.ttree.localhost',
            'Access-Control-Allow-Credentials' => 'true',
            'Vary' => 'Accept-Encoding, Origin',
            'Server' => 'Medialib CMS Content API',
            'Access-Control-Max-Age' => 86400,
            'Access-Control-Allow-Methods' => 'OPTIONS, GET, POST',
            'Access-Control-Allow-Headers' => 'content-type, authorization, cookie, if-match, if-unmodified-since'
        ];

        if ($request->getMethod() === 'OPTIONS') {
            return new Response(204, $headers);
        }

        $response = $next->handle($request);
        foreach ($headers as $headerName => $headerValue) {
            $response = $response->withHeader($headerName, $headerValue);
        }
        return $response;
    }
}
