<?php

namespace App\Http\Requests;

use App\Http\Requests\Request;

class NodeRequest extends Request
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
            'name'=>'bail|required',
            // 'show_time'=>'sometimes|date_format:"Y-m-d H:i:s"',
        ];
    }
    public function messages(){
        return [
            'name.required' => '名称不能为空',
            // 'show_time.date_format'=>'发布时间格式错误。2016-01-01 10:10:10',
        ];
    }
}
