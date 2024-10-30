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
     *
     * @param \Illuminate\Http\Request $request The request
     * @param \Closure $next The next middleware
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
                SemConv::HTTP_RESPONSE_STATUS_CODE => $response->getStatusCode(),
                SemConv::HTTP_REQUEST_METHOD => $request->method(),
                SemConv::HTTP_ROUTE => $request->path(),
            ]
        );

        return $response;
    }
}
