<?php

namespace App\Http\Controllers\Student;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Session;
use App\Models\NodeQa;
use App\Models\User;
use App\Models\Student;
use App\Models\SquadStruct;

class JudgeController extends BaseController
{

    public function index($squad_id){ 
        $oUser = \Auth::user();
        $oStudent = Student::where('user_id',$oUser->id)->first();
        $squads =SquadStruct::where('type',1)->where('struct_id',$oStudent->id)->pluck('squad_id')->toArray();
        if(in_array($squad_id,$squads)){
			if (view()->exists(session('mode').'.studentPlat.jduge.index')){
				return View(session('mode').'.studentPlat.jduge.index',compact('squad_id'));
			}else{
				return View('default.studentPlat.jduge.index',compact('squad_id'));
			}
        }else{
            return redirect('error')->with(['msg'=>'非法操作', 'href'=>app(UrlGenerator::class)->previous()]); 
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

    /**
     * 评价一个环节
     */
    public function doJudge(Request $request)
    {
        $plan_id=$request->input('plan_id',0);
        $cell_id=$request->input('cell_id',0);
        $module_id=$request->input('module_id',0);
        $node_id=$request->input('node_id',0);
        $content=$request->input('content','');
        $score=$request->input('score');//1差评 2中评：3：好评
        if(empty($plan_id) || empty($cell_id) || empty($module_id) || empty($node_id)){
            $msg = [
                "custom-msg"=> ["参数缺少"],
            ];
            return response()->json($msg)->setStatusCode(422);
        }
        if(!in_array($score,array('1','2','3'))){
            $msg = [
                "custom-msg"=> ["评价的分值不对"],
            ];
            return response()->json($msg)->setStatusCode(422);
        }
        if(empty($content)){
            $msg = [
                "custom-msg"=> ["评价内容不能为空"],
            ];
            return response()->json($msg)->setStatusCode(422);
        }
        if(!in_array($score,array(-1,0,1))){
            $msg = [
                "custom-msg"=> ["评价分值不对"],
            ];
            return response()->json($msg)->setStatusCode(422);
        }
        $oObj=NodeQa::where(['type'=>2,'node_id'=>$node_id,'user_id'=>$this->user_id])->first();
        if(!empty($oObj)){
            $msg = [
                "custom-msg"=> ["已经评价过"],
            ];
            return response()->json($msg)->setStatusCode(422);
        }
        $model=new NodeQa();
        $model->plan_id=$plan_id;
        $model->cell_id=$cell_id;
        $model->module_id=$module_id;
        $model->node_id=$node_id;
        $model->user_id=$this->user_id;
        $model->score=$score;
        $model->content=$content;
        $model->type=1;
        $model->content=$content;
        return response()->json(null);
    }
}
