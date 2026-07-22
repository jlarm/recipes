<?php

namespace App\Http\Controllers;

use App\Http\Requests\VerifyPasscodeRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class PasscodeController extends Controller
{
    public function show(): Response
    {
        return Inertia::render('Passcode');
    }

    public function verify(VerifyPasscodeRequest $request): RedirectResponse
    {
        $expected = trim((string) config('recipes.passcode'));
        $provided = $request->string('passcode')->trim()->value();

        if ($expected === '' || ! hash_equals($expected, $provided)) {
            throw ValidationException::withMessages([
                'passcode' => 'That passcode is incorrect.',
            ]);
        }

        $request->session()->put('passcode_verified', true);

        $intended = $request->session()->pull('passcode_intended_url') ?? $this->safeRedirect($request);

        return redirect()->to($intended ?? route('recipes.create'));
    }

    /**
     * A same-site path the dialog asked us to return to, if it is safe to honour.
     *
     * Only relative paths are accepted so the field can't be used as an open redirect.
     */
    private function safeRedirect(VerifyPasscodeRequest $request): ?string
    {
        $redirect = $request->string('redirect')->trim()->value();

        if ($redirect === '' || ! str_starts_with($redirect, '/') || str_starts_with($redirect, '//')) {
            return null;
        }

        return $redirect;
    }
}
