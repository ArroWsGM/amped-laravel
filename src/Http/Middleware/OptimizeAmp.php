<?php

namespace Arrowsgm\Amped\Http\Middleware;

use Arrowsgm\Amped\Facades\Amped;
use Closure;
use Illuminate\Http\Request;

class OptimizeAmp
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     *
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        $response->setContent(Amped::optimize($response->content()));

        return $response;
    }
}
