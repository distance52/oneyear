<?php

namespace App\Http\Requests;

use App\Http\Requests\Request;

class NotifyRequest extends Request
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function rules()
    {
        return [
            'title'=>'required',
            'template' => 'required',
            'fields' => 'required',
            'example' => 'required',
            'type' => 'required|in:1,2',
        ];
    }

    public function messages(){
        return [
            'captcha.in' => '验证码不正确',
            'captcha.required' => '验证码不能为空',
            'title.required'=>'模板名称不能为空',
            'template.required'=>'模板标识不能为空，请填写有效的微信或短信模板标识',
            'fields.required'=>'请用英文逗号分隔必填字段',
            'example.required'=>'模板样例不能为空',
            'type.in'=>'类型只能为短信模板或者微信模板'
        ];
    }
}
