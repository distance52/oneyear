<?php

namespace App\Http\Controllers\Administrator\School;

use App\Models\StudentPoint;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Requests;
use App\Models\School;
use App\Models\Squad;
use App\Models\Student;

class PointController extends Controller
{
    public function index()
    {
        $aSearch = [];
        $name=$squad_name=$where='';
        $type=0;
        \Request::has('name') &&  $aSearch['name']=$name = \Request::input('name');
        $oObjs=StudentPoint::with('student');
        \Request::has('type') &&  $aSearch['type']= $type = \Request::input('type');
        if($name!=''){
            $student_ids=Student::where('name','like', '%'.$name.'%')->pluck('id');
            $oObjs = $oObjs->whereIn('student_id',$student_ids);
        }
        $student_id=\Request::input('student_id',0);
        if($student_id>0){
            $oObjs = $oObjs->where('student_id',$student_id);
        }
        if($type>0){
            $oObjs=$oObjs->where(['type'=>$type]);
        }
        $oObjs=$oObjs->orderBy('id','desc');
		$num['a'] = $oObjs->count();
		$oObjs=$oObjs->paginate(20);
		$num['b'] = $oObjs->count();
        foreach($oObjs as &$val){
            if($val->student_id>0){
                $ostudent = $val->student;
				  if($ostudent){
					$school_name=School::where('id',$ostudent->school_id)->value('name');
					$squad_name=Squad::where('id',$ostudent->squad_id)->value('name');
					$val->school_name=$school_name;
					$val->squad_name=$squad_name;
                }
            }else{
                $val->school_name='';
                $val->squad_name='';
            }
        }
		if (view()->exists(session('mode').'.school.point.list')){
			return View(session('mode').'.school.point.list', compact('oObjs','aSearch','num'));
		}else{
			return View('default.school.point.list', compact('oObjs','aSearch','num'));
		}
    }
}
