<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Session;

class WechatTestController extends Controller
{
    public function index(Request $request){
        $user_id   = Session::get('user_id');
        $wechat_id = Session::get('wechat_id');
        var_dump($user_id);
        var_dump($wechat_id);
        echo 'abc';
    }
}
