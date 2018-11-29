<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/4/7
 * Time: 15:44
 */

namespace App\Http\Controllers\School;
use App\Http\Controllers\Controller;
class BaseController extends Controller
{
    protected $school_id=0;
    protected $user_id=0;
    public function __construct()
    {
        if(\Auth::check()) {
            $oUser = \Auth::user();
            $user_id = $oUser->id;
            if($oUser->plat!=1){
                return redirect('error2')->withErrors(['msg' => '角色还不是学校管理员'])->send();
            }
            $this->school_id = $oUser->school_id;
            if($this->school_id==0){
                return redirect('error2')->withErrors(['msg' => '您的账号尚未关联任何学校，请确认后重新登陆，谢谢！'])->send();
            }
            $this->user_id = $user_id;
        }
        else{
            return redirect('login')->send();
        }
    }
}