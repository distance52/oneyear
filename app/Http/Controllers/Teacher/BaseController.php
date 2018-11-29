<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/4/7
 * Time: 15:44
 */

namespace App\Http\Controllers\Teacher;
use App\Http\Controllers\Controller;
use App\Models\Teacher;
class BaseController extends Controller
{
    protected $school_id=0;
    protected $teacher_id=0;
    protected $user_id=0;
    public function __construct()
    {
        if(\Auth::check()) {
            $oUser = \Auth::user();
            $user_id = $oUser->id;
            $teacher = Teacher::where('user_id', $user_id)->first();
            if ($oUser->plat != 2) {
                return redirect('error2')->withErrors(['msg' => '角色还不是教师'])->send();
            }
            if (!$teacher) {
                //提示该用户不是老师
                return redirect('error2')->withErrors(['msg' => '教师不存在'])->send();
            }
            $this->school_id = $oUser->school_id;
            if($this->school_id==0){
                return redirect('error2')->withErrors(['msg' => '您的账号尚未关联任何学校，请确认后重新登陆，谢谢！'])->send();
            }
            $this->user_id = $user_id;
            $this->teacher_id = $teacher->id;
        }
        else{
            return redirect('login')->send();
        }
    }
}