<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Models\User;

// 这个控制器主要写该账户下的其它接口。例如：所有班级  所有学校 所有环节 所有作业等
class AccountController extends Controller
{
    // 所有学生
    public function students() {
        $oUser = \Auth::user();
        if($oUser->plat === 0) {
            return User::where('plat',2)->paginate(20);
        } else {
            return User::where('plat',2)->where('school_id',$oUser->school_id)->paginate(20);
        }
    }
    // 包括学生、老师 学校管理员
    public function users() {
        $oUser = \Auth::user();
        if($oUser->plat === 0) {
            return User::paginate(20);
        } else {
            return User::where('school_id',$oUser->school_id)->paginate(20);
        }
    }
}
