<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Advertise machine-discoverable resources via RFC 8288 `Link` response headers
 * so agents and crawlers can find the API catalog and sitemap without scraping
 * the HTML. Applied to the web group, so every HTML page (incl. the homepage)
 * carries the headers.
 */
class AdvertiseDiscoveryLinks
{
    /**
     * Relative URIs are resolved by the client against the request URL, so they
     * stay correct on any host/scheme.
     *
     * @var list<string>
     */
    private const LINKS = [
        '</.well-known/api-catalog>; rel="api-catalog"; type="application/linkset+json"',
        '</sitemap.xml>; rel="sitemap"; type="application/xml"',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Only decorate HTML page responses; leave JSON/redirects/downloads alone,
        // and never clobber a `Link` header a controller already set.
        $contentType = (string) $response->headers->get('Content-Type');

        if (! $response->headers->has('Link') && str_contains($contentType, 'text/html')) {
            $response->headers->set('Link', implode(', ', self::LINKS));
        }

        return $response;
    }
}
