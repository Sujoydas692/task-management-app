<?php

namespace App\Http\Requests\API\V1;


class GroupUserStoreRequest extends ApiFormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
//        return [
//            'user_id' => 'nullable|exists:users,id|required_without:user_ids',
//            'user_ids' => 'nullable|array|required_without:user_id',
//            'user_ids.*' => 'integer|exists:users,id',
//        ];

        return [
            'user_id' => 'required_without:user_ids|nullable|exists:users,id',
            'user_ids' => 'required_without:user_id|array|nullable',
            'user_ids.*' => 'integer|exists:users,id',
        ];

    }
}
