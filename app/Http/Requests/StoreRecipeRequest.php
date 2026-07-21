<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreRecipeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->session()->get('passcode_verified') === true;
    }

    /**
     * Multipart uploads turn absent numeric values into empty strings, so
     * normalise those back to null before the nullable rules run.
     */
    protected function prepareForValidation(): void
    {
        $nullIfBlank = fn (mixed $value): mixed => $value === '' ? null : $value;

        $ingredients = is_array($this->ingredients) ? $this->ingredients : [];

        $this->merge([
            'prep_minutes' => $nullIfBlank($this->prep_minutes),
            'cook_minutes' => $nullIfBlank($this->cook_minutes),
            'ingredients' => array_map(function (mixed $ingredient) use ($nullIfBlank): mixed {
                if (is_array($ingredient) && array_key_exists('quantity', $ingredient)) {
                    $ingredient['quantity'] = $nullIfBlank($ingredient['quantity']);
                }

                return $ingredient;
            }, $ingredients),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'category' => ['required', 'string', 'max:255'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:8192'],
            'remove_image' => ['nullable', 'boolean'],
            'description' => ['nullable', 'string', 'max:2000'],
            'servings' => ['required', 'integer', 'min:1', 'max:100'],
            'prep_minutes' => ['nullable', 'integer', 'min:0', 'max:1440'],
            'cook_minutes' => ['nullable', 'integer', 'min:0', 'max:1440'],
            'instructions' => ['required', 'string', 'max:10000'],
            'ingredients' => ['required', 'array', 'min:1'],
            'ingredients.*.name' => ['required', 'string', 'max:255'],
            'ingredients.*.quantity' => ['nullable', 'numeric', 'min:0', 'max:100000'],
            'ingredients.*.unit' => ['nullable', 'string', 'max:50'],
        ];
    }
}
