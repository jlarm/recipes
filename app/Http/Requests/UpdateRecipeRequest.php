<?php

namespace App\Http\Requests;

/**
 * Adding and editing a recipe share the same input contract.
 */
class UpdateRecipeRequest extends StoreRecipeRequest {}
