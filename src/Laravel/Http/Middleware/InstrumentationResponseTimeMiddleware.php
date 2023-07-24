<?php

namespace Stickee\Instrumentation\Laravel\Http\Middleware;

use Closure;
use Exception;
use Illuminate\Http\Request;
use Stickee\Instrumentation\Laravel\Facade as Instrument;

class InstrumentationResponseTimeMiddleware
{
    /**
     * @throws Exception
     */
    public function handle(Request $request, Closure $next)
    {
        $startTime = microtime(true);

        $response = $next($request);

        if ($response->exception) {
            Instrument::event('exception', ['exception' => get_class($response->exception)]);
        }

        $tags = [
            'status' => $response->status(),
            'success' => (bool)$response->isSuccessful()
        ];

        Instrument::event('response_time', $tags, microtime(true) - $startTime);

        return $response;
    }
}
