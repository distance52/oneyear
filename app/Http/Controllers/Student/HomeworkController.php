<?php

namespace App\Http\Controllers\Student;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;

class HomeworkController extends BaseController
{

    public function index(){
		if (view()->exists(session('mode').'.studentPlat.jduge.index')){
			return View(session('mode').'.studentPlat.jduge.index');
		}else{
			return View('default.studentPlat.jduge.index');
		}
    }

    public function jdugeJson(Request $request){
        $page=$request->input('page',1);
        $pagesize=2;
        $oObjs=NodeQa::where(['type'=>2,'user_id'=>$this->user_id]);
        $totalRows=$oObjs->count();
        $pages=ceil($totalRows/$pagesize);
        $offset=($page-1)*$pagesize;
        $oObjs=$oObjs->orderBy('id','desc')->with('plan','module','cell','node')->skip($offset)->take($pagesize)->get();
        $return=array();
        foreach($oObjs as $val){
            $arr=array();
            $arr['id']=$val->id;
            $arr['content']=$val->content;
            $arr['score']=$val->score;
            $arr['addtime']=empty($val->created_at)?0:$val->created_at->format('Y-m-d H:i:s');
            array_push($return,$arr);
        }
        $return = array('pager'=>array('total'=>$totalRows,'pages'=>$pages,'page'=>$page),'data'=>$return);
        return response()->json($return);
    }
}
