<?php

namespace App\Http\Controllers\Administrator\School;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use App\Models\NodeQa;
use App\Models\User;
use App\Models\School;
use App\Models\Teacher;
use App\Models\Teaching\Plan;

class JudgeController extends Controller
{
    public function index(){
        $plan_list=Plan::get();
		if (view()->exists(session('mode').'.school.judge.list')){
			return View(session('mode').'.school.judge.list', compact('plan_list'));
		}else{
			return View('default.school.judge.list', compact('plan_list'));
		}
    }

    /**
     * 获取摸个环节的所有评价列表
     */
    public function getJudgeList(Request $request)
    {
        $plan_id=$request->input('plan_id',0);
        $cell_id=$request->input('cell_id',0);
        $module_id=$request->input('module_id',0);
        $node_id=$request->input('node_id',0);
        $squad_id=$request->input('squad_id',0);
        $type=$request->input('type',0);
        $oObjs=NodeQa::where(['type'=>2]);
        if($type==1){
            //差评
            $oObjs=$oObjs->where('score',1);
        }
        elseif($type==2){
            //中评
            $oObjs=$oObjs->where('score',2);
        }
        elseif($type==3){
            //好评
            $oObjs=$oObjs->where('score',3);
        }
        if($squad_id!=0){
            $oObjs=$oObjs->where('squad_id',$squad_id);//这个班的所有答疑
        }
        if($plan_id!=0){
            $oObjs=$oObjs->where('plan_id',$plan_id);
        }
        if($cell_id!=0){
            $oObjs=$oObjs->where("cell_id",$cell_id);
        }
        if($module_id!=0){
            $oObjs=$oObjs->where("module_id",$module_id);
        }
        if($node_id!=0){
            $oObjs=$oObjs->where("node_id",$node_id);
        }
        $oObjs=$oObjs->with('squad','plan','module','cell','node');
		$num['a'] = $oObjs->count();
		$oObjs=$oObjs->paginate(5);
		$num['b'] = $oObjs->count();
        $return=array();
        foreach($oObjs as &$val){
            $return['content']=$val->content;
            $return['score']=$val->score;
            $return['addtime']=$val->created_at;
            if(isset($val->squad)){
                $val->squad_name=$val->squad->name;
                $val->school_name=School::where('id',$val->squad->school_id)->value('name');
                $val->teacher_name=Teacher::where('id',$val->squad->teacher_id)->value('name');
            }
            $avatar=User::where('id',$val->user_id)->value('avatar');
            $val->avatar=getAvatar($avatar);
        }
		if (view()->exists(session('mode').'.school.judge.qas-list')){
			return View(session('mode').'.school.judge.qas-list', compact('oObjs','num'));
		}else{
			return View('default.school.judge.qas-list', compact('oObjs','num'));
		}
    }
}
