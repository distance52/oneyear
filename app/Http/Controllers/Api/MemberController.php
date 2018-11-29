<?php

namespace App\Http\Controllers\Api;

use App\Models\Teacher;
use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Student;
use App\Models\Squad;
use App\Models\School;

class MemberController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $plat = $request->has('plat')? $request->input('plat'): null;
        $school_id = $request->has('school_id')? $request->input('school_id'): 0;
        $oUser = new User();
        if(!is_null($plat)){
            $oUser = $oUser->where('plat',$plat);
        }
        if(!empty($school_id)){
            $oUser = $oUser->where('school_id',$school_id);
        }
        return $oUser->paginate(20);
    }

    /**
     * 返回teacher_id的老师接口
     */
    public function teacher(Request $request){
        $oTeacher = new Teacher();
        return $oTeacher->paginate(20);
    }
	public function teacher_id(Request $request)
	{
        $school_id = $request->has('school_id')? $request->input('school_id'): 0;
		$oUser = new Teacher();
        if(!empty($school_id)){
            $oUser = $oUser->where('school_id',$school_id);
        }
		return $oUser->paginate(20);
	}
    /**
     * 返回teacher_id的老师接口
     */
    public function student(Request $request){
        $oStudent = new Student();
        if($request->has('squad_id')){
            $oStudent->where('squad_id',$request->has('squad_id'));
        }
        return $oStudent->paginate(20);
    }

    /**
     * 返回各个平台可以选择的基础列表
     */
    public function defaultUser(Request $request){
        $oUser = \Auth::user();
        $school_id=$oUser->school_id;
        $keyword=$request->input('keyword','');
        if($keyword==''){
            if($oUser->plat === 0) {
                //allAdmin,AllSchoolAdmin,AllTeacher
                $arr=array();
                $arr[]=array('allAdmin','全部管理员');
                $arr[]=array('AllSchoolAdmin','全部学校管理员');
                $arr[]=array('AllTeacher','全部老师');
                return response()->json($arr);
            } elseif($oUser->plat === 1) {
                //本校所有老师，某个班级所有学生
                //AllSchoolTeacher,squad|1|all
                $arr=array();
                if($school_id==0) return response()->json(null);
                $squad_list=Squad::where('school_id',$school_id)->pluck('name','id');
                //dd($squad_list->toArray());
                foreach($squad_list as $key=>$val){
                    $arr[]=array('squad|'.$key.'|all',$val);
                }
                $arr[]=array('AllSchoolTeacher','全部老师');
                return response()->json($arr);
            }
            elseif($oUser->plat === 2) {
                //AllSchoolTeacher,AllSquad,squad|1|all
                $arr=array();
                if($school_id==0) return response()->json(null);
                $teacher_id=Teacher::where('user_id',$oUser->id)->value('id');
                $squad_list=Squad::where('teacher_id',$teacher_id)->pluck('name','id');
                foreach($squad_list as $key=>$val){
                    $arr[]=array('squad|'.$key.'|all',$val);
                }
                $arr[]=array('AllSquad','全部所教学生');
                return response()->json($arr);
            }
            else{
                return response()->json(null);
            }
        }
        else{
            $oObjs=new User();
            if($oUser->plat === 0) {
                $oObjs = $oObjs->where('name','like', '%'.$keyword.'%')->get(['id','name']);
                return response()->json($oObjs);
            } elseif($oUser->plat === 1) {
                $oObjs = $oObjs->where('name','like', '%'.$keyword.'%')->where('school_id',$school_id)->get(['id','name']);
                return response()->json($oObjs);
            }
            elseif($oUser->plat === 2) {
                $teacher_id=Teacher::where('user_id',$oUser->id)->value('id');
                $squad_ids=Squad::where('school_id',$school_id)->where('teacher_id',$teacher_id)->pluck('id');//所教的全部班级ids
                $user_ids = User::where('name','like', '%'.$keyword.'%')->pluck('id');
                $oObjs=Student::whereIn('squad_id',$squad_ids)->whereIn('user_id',$user_ids)->get(['user_id as id','name']);
                return response()->json($oObjs);
            }
            else{
                return response()->json(null);
            }
        }
    }

    public function school(Request $request){
        $name = $request->input('name',0);
        if(!$name){
            return response()->json(array('status'=>false ,'msg'=>'请输入学校名称','data'=>null));
        }
        $data = [];
        $obj = School::where("name","like","%$name%")->take(5)->get();
        $obj->each(function ($obj,$i) use(&$data){
            $data[$i]['id'] = $obj->id;
            $data[$i]['name'] = $obj->name;
        });
        return response()->json(array('status'=>true ,'msg'=>'ok','data'=>$data));

    }

    public function squad($id,Request $request){
        $name = $request->input('name',0);
        if(!$name){
            return response()->json(array('status'=>false ,'msg'=>'请输入班级名称','data'=>null));
        }
        $data = [];
        $obj = Squad::where('school_id',$id)->where("name","like","%$name%")->take(5)->get();
        $obj->each(function ($obj,$i) use(&$data){
            $data[$i]['id'] = $obj->id;
            $data[$i]['name'] = $obj->name;
        });
        return response()->json(array('status'=>true ,'msg'=>'ok','data'=>$data));

    }


}
