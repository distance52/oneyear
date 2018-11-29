<?php

namespace App\Http\Requests;

use App\Http\Requests\Request;

class UserRequest extends Request
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
            'email'=>'required|exists:users,email',
            'password'=>'required',
            'captcha'=>'sometimes|required|in:'.session('userCaptcha')
        ];
    }
    // 
    public function messages(){
        return [
            'captcha.in' => '验证码不正确',
            'captcha.required' => '验证码不能为空',
            'email.required'=>'帐号不能为空',
            'email.email'=>'帐号格式不正确',
            'email.exists'=>'帐号不存在',
            'password.required'=>'密码不能为空'
        ];
    }
}
