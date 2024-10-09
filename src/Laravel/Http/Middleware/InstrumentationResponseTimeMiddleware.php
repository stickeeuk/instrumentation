<?php

namespace Stickee\Instrumentation\Laravel\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Stickee\Instrumentation\Laravel\Facades\Instrument;
use Stickee\Instrumentation\Utils\SemConv;

class InstrumentationResponseTimeMiddleware
{
    /**
     * Handle the request
     */
    public function handle(Request $request, Closure $next)
    {
        $startTime = microtime(true);

        $response = $next($request);

        Instrument::histogram(
            SemConv::HTTP_SERVER_REQUEST_DURATION_NAME,
            SemConv::HTTP_SERVER_REQUEST_DURATION_UNIT,
            SemConv::HTTP_SERVER_REQUEST_DURATION_DESCRIPTION,
            SemConv::HTTP_SERVER_REQUEST_DURATION_BUCKETS,
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
