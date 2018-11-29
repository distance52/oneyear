<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/4/7
 * Time: 15:44
 */

namespace App\Http\Controllers\Student;
use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\SquadStruct;
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
            $student_data = Student::where('user_id', $user_id)->first();
            if(empty($student_data)){
                return redirect('error2')->withErrors(['msg' => '角色还不是学生'])->send();
            }
            $student_info = $student_data->id;
			$squad_id = SquadStruct::where('type',1)->where('struct_id',$student_info)->pluck('squad_id');
            if ($oUser->plat != 3) {
                return redirect('error2')->withErrors(['msg' => '角色还不是学生'])->send();
            }
            if (!$student_info) {
                //提示该用户不是老师
                return redirect('error2')->withErrors(['msg' => '学生信息不存在'])->send();
            }
            $this->school_id = $oUser->school_id;
            if($this->school_id==0){
                return redirect('error2')->withErrors(['msg' => '您的账号尚未关联任何班级，请确认后重新登陆，谢谢'])->send();
            }

            $this->user_id=$user_id; 
            $this->student_id=$student_info;
            $this->squad_id=$squad_id;
        }
        else{
            return redirect('login')->send();
        }
    }
}