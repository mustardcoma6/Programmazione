<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileUpdateRequest extends FormRequest
{
    /**
     * Regole di validazione per il profilo.
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'], // <--- CONTROLLA QUESTA RIGA
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique(User::class)->ignore($this->user()->id),
            ],
        ];
    }

    /**
     * Permesso di eseguire la richiesta.
     */
    public function authorize(): bool
    {
        return true;
    }
}