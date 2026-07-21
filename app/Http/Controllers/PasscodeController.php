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

        $intended = $request->session()->pull('passcode_intended_url');

        return redirect()->to($intended ?? route('recipes.create'));
    }
}
