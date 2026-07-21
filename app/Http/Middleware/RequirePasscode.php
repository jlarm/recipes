<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequirePasscode
{
    /**
     * Ensure the visitor has entered the shared contributor passcode before
     * reaching gated routes. Unverified visitors are sent to the passcode form,
     * remembering where they were headed.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->session()->get('passcode_verified') !== true) {
            $request->session()->put('passcode_intended_url', $request->fullUrl());

            return redirect()->route('passcode.show');
        }

        return $next($request);
    }
}
