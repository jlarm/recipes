<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Recipe Contributor Passcode
    |--------------------------------------------------------------------------
    |
    | Recipes are public to read, but adding a new one is gated behind this
    | shared passcode. Once entered correctly it is remembered in the visitor's
    | session so they can add multiple recipes without re-entering it.
    |
    */

    'passcode' => env('RECIPE_PASSCODE'),
];
