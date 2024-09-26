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

        Instrument::histogram(
            \Stickee\Instrumentation\Utils\SemConv::HTTP_SERVER_REQUEST_DURATION_NAME,
            \Stickee\Instrumentation\Utils\SemConv::HTTP_SERVER_REQUEST_DURATION_UNIT,
            \Stickee\Instrumentation\Utils\SemConv::HTTP_SERVER_REQUEST_DURATION_DESCRIPTION,
            \Stickee\Instrumentation\Utils\SemConv::HTTP_SERVER_REQUEST_DURATION_BUCKETS,
            microtime(true) - $startTime,
            [
                'http.response.status_code' => $response->getStatusCode(),
                'http.request.method' => $request->method(),
                'http.route' => $request->path(),
            ]
        );

        return $response;
    }
}
