<?php

namespace App\Http\Requests;

use App\Http\Requests\Request;

class SchoolRequest extends Request
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
            'name'=>'required|unique:schools,name,'.$this->get('id'),
            'host_suffix'=>'required|unique:schools,host_suffix,'.$this->get('id'),
        ];
    }
    public function messages(){
        return [
            'name.required' => '名称不能为空',
            'host_suffix.required' => '网址不能为空',
            'host_suffix.unique' => '网址前缀已经存在，请换一个',
            'name.unique' => '学校名称已经存在，请换一个',
        ];
    }
}
