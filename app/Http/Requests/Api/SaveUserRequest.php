<?php

namespace App\Http\Requests\Api;

use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\Validator;

class SaveUserRequest extends FormRequest
{
    private ?User $target = null;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $target = $this->target();

        return $target ? $this->user()->can('update', $target) : $this->user()->can('create', User::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $target = $this->target();
        $usernameChanged = $target === null || $target->username !== $this->input('username');

        return [
            'name' => ['required', 'string', 'max:255'],
            'username' => [
                'required',
                'string',
                ...($usernameChanged ? ['alpha_dash:ascii', 'lowercase', 'min:3', 'max:40'] : []),
                Rule::unique('users', 'username')->ignore($target?->id),
            ],
            'password' => [Rule::requiredIf($target === null), 'nullable', 'string', Password::defaults()],
            'perms' => ['sometimes', 'array:'.implode(',', User::PERMISSIONS)],
            'perms.*' => ['boolean'],
            'routes' => ['sometimes', is_array($this->input('routes')) ? 'array' : Rule::in(['all'])],
            'routes.*' => ['string', 'distinct', Rule::exists('routes', 'source_key')],
            'active' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($this->target()?->is($this->user()) && $this->has('active') && ! $this->boolean('active')) {
                    $validator->errors()->add('active', 'You cannot block your own account.');
                }

                if (is_array($this->input('routes')) && $this->input('routes') === []) {
                    $validator->errors()->add('routes', 'Choose at least one route, or give access to all routes.');
                }
            },
        ];
    }

    /**
     * The account being edited, or null when a new one is being created.
     */
    public function target(): ?User
    {
        return $this->target ??= User::query()->where('source_key', $this->route('key'))->first();
    }
}
