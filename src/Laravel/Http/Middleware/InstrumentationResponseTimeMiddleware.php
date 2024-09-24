<?php

namespace Stickee\Instrumentation\Laravel\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Stickee\Instrumentation\Laravel\Facades\Instrument;

class InstrumentationResponseTimeMiddleware
{
    /**
     * Handle the request
     */
    public function handle(Request $request, Closure $next)
    {
        $startTime = microtime(true);

        $response = $next($request);

        // if ($response->exception ?? null) {
        //     Instrument::event('exception', ['exception' => get_class($response->exception)]);
        // }

        $tags = [
            'http.response.status_code' => $response->getStatusCode(),
            // 'http.route' => $request->route()->uri,
        ];

        // Instrument::event('response_time', $tags, microtime(true) - $startTime);

        $buckets = [0.1, 0.2, 0.5, 1, 2, 5, 10, 30, 100];

        Instrument::histogram('http.server.request.duration', 's', 'Request duration', $buckets, microtime(true) - $startTime, $tags);

        return $response;
    }
}
