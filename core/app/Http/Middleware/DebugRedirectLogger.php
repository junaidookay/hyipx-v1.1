<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DebugRedirectLogger
{
    public function handle(Request $request, Closure $next)
    {
        \Log::info('[DEBUG-MW-IN] Request: ' . $request->method() . ' ' . $request->path(), [
            'full_url' => $request->fullUrl(),
            'route_name' => $request->route()?->getName(),
            'route_action' => $request->route()?->getActionName(),
            'middleware_stack' => $request->route()?->gatherMiddleware() ?? [],
        ]);

        $response = $next($request);

        if ($response instanceof \Illuminate\Http\RedirectResponse) {
            $to = $response->getTargetUrl();
            \Log::warning('[DEBUG-MW-REDIRECT] Redirect detected', [
                'from'     => $request->path(),
                'to'       => $to,
                'status'   => $response->getStatusCode(),
                'route_name' => $request->route()?->getName(),
                'controller' => $request->route()?->getActionName(),
                'trace'    => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 20),
            ]);
        }

        return $response;
    }
}
