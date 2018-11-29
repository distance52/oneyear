<?php

namespace App\Http\Requests;

use App\Http\Requests\Request;

class OssSettingRequest extends Request
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
            'access_key_id'=>'required',
            'access_key_secret' => 'required',
            'file_entrance' => 'required',
            'domain' => 'required',
            'endpoint' => 'required',
            'bucket' => 'required',
        ];
    }
}
