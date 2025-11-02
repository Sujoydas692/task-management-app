<?php

namespace App\Http\Requests\API\V1;

use App\Traits\ApiResponseTrait;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class ApiFormRequest extends FormRequest
{
    use ApiResponseTrait;

    protected function failedValidation(Validator $validator)
    {
       throw new HttpResponseException($this->error($validator->errors()->all(), 422));
    }
}