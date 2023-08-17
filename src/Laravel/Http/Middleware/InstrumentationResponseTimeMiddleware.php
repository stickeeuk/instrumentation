<?php

namespace Stickee\Instrumentation\Laravel\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Stickee\Instrumentation\Laravel\Facade as Instrument;

class InstrumentationResponseTimeMiddleware
{
    /**
     * Handle the request
     */
    public function handle(Request $request, Closure $next)
    {
        $startTime = microtime(true);

        $response = $next($request);

        if ($response->exception ?? null) {
            Instrument::event('exception', ['exception' => get_class($response->exception)]);
        }

        $tags = [
            'status' => $response->getStatusCode(),
            'success' => (bool)$response->isSuccessful()
        ];

        Instrument::event('response_time', $tags, microtime(true) - $startTime);

        return $response;
    }
}
