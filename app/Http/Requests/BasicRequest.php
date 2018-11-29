<?php

namespace App\Http\Requests;

use App\Http\Requests\Request;

class BasicRequest extends Request
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
          'system_name'=>'required',
          'alias_info' => 'sometimes',
          'telephone' => 'required',
          'email' => 'required|email',
          'address' => 'sometimes',
          'icp' => 'sometimes',
          'code'=> 'sometimes',
        ];
    }
}
