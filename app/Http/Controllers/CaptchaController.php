<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Gregwar\Captcha\CaptchaBuilder;
use App\Http\Requests;

class CaptchaController extends Controller
{
    //
    public function login($tmp) {
        //生成验证码图片的Builder对象，配置相应属性
        $builder = new CaptchaBuilder;
        //可以设置图片宽高及字体
        $builder->build($width = 100, $height = 40, $font = null);
        //获取验证码的内容
        $phrase = $builder->getPhrase();
        //把内容存入session
        \Session::flash('userCaptcha', $phrase);
        //生成图片
        ob_clean();
        return response($builder->output())
            ->header('Content-Type', 'image/jpeg')
            ->header('Cache-Control', 'no-cache, must-revalidate');

    }

    public function imgSrc($session_key = 'capture') {
        //生成验证码图片的Builder对象，配置相应属性
        $builder = new CaptchaBuilder;
        //可以设置图片宽高及字体
        $builder->build($width = 100, $height = 40, $font = null);
        //获取验证码的内容
        $phrase = $builder->getPhrase();
        //把内容存入session
        \Session::flash($session_key, $phrase);
        //生成图片
        return $builder->inline();
    }
}
