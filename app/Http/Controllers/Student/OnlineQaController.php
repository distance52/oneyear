<?php

namespace App\Http\Controllers\Student;


use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Routing\UrlGenerator;
use App\Http\Requests;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Session;
use App\Models\Student;
use App\Models\SquadStruct;
use App\Models\NodeQa;

class OnlineQaController extends BaseController
{

    /**
     * 我的疑问
     * @param Request $request
     */
    public function myfaq($squad_id){
        $oUser = \Auth::user();
        $oStudent = Student::where('user_id',$oUser->id)->first();
        $squads =SquadStruct::where('type',1)->where('struct_id',$oStudent->id)->pluck('squad_id')->toArray();
        if(in_array($squad_id,$squads)){           
        if (view()->exists(session('mode').'.studentPlat.faq.index')){
                return View(session('mode').'.studentPlat.faq.index', compact('squad_id'));
            }else{
                return View('default.studentPlat.faq.index', compact('squad_id'));
            }
        }else{
            return redirect('error')->with(['msg'=>'非法操作', 'href'=>app(UrlGenerator::class)->previous()]); 
        }
		
    }

    /**
     * 我的疑问json列表
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function myfaqJson(Request $request){
        $page=$request->input('page',1);
        $type=$request->input('type',0);
        $squad_id=$request->input('squad_id',0);
        $pagesize=5;
        $model=new NodeQa();
        // $my_squads=Student::where('user_id',$this->user_id)->pluck('squad_id');
        if($type==0){
            //全部答疑，显示我这个班上的所有答疑
            $oObjs=NodeQa::where('type',3)->where('squad_id',$squad_id)->where(['parent_id'=>0,'is_black'=>0]);
        }
        else{
            //我的答疑，显示我发起的答疑
            $oObjs=NodeQa::where('type',3)->where(['user_id'=>$this->user_id,'parent_id'=>0,'is_black'=>0]);
        }
        $totalRows=$oObjs->count();
        $pages=ceil($totalRows/$pagesize);
        $offset=($page-1)*$pagesize;
        $oObjs=$oObjs->orderBy('id','desc')->with('user')->skip($offset)->take($pagesize)->get();
        $return=array();
        foreach($oObjs as $val){
            $arr=array();
            $oUser = User ::whereId($val->user_id)->first();
            $arr['id']=$val->id;
            $arr['avatar'] = getAvatar($oUser->avatar);
            $arr['name'] = $oUser->name;
            $arr['content']=$val->content;
            $arr['replyCount']=$model->replyCount($val->id);
            $arr['addtime']=empty($val->created_at)?0:$val->created_at->format('Y-m-d H:i:s');
            array_push($return,$arr);
        }
        $return = array('pager'=>array('total'=>$totalRows,'pages'=>$pages,'page'=>$page),'data'=>$return);
        return response()->json($return);
    }

    /**
     * 我的疑问查看
     * @param Request $request
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function faqView(Request $request){
        $model=new NodeQa();
        $id=$request->input('id',0);
        $oNodeQa=$model->where('type',3)->where(['id'=>$id,'parent_id'=>0,'is_black'=>0])->with('user','cell','module','node')->first();
        if(empty($oNodeQa)){
            //跳转错误页面
            return redirect('error')->with(['msg'=>'答疑不存在', 'href'=>app(UrlGenerator::class)->previous()]);
        }
        $oNodeReply=$model->where(['parent_id'=>$id,'is_black'=>0])->with('user','cell','module','node')->get();
        $oObj=array();
        $oObj['id']=$oNodeQa->id;
        $oObj['cell_name']=isset($oNodeQa->cell)?$oNodeQa->cell->name:'未知单元';
        $oObj['module_name']=isset($oNodeQa->module)?$oNodeQa->module->name:'未知模块';
        $oObj['node_name']=isset($oNodeQa->node)?$oNodeQa->node->name:'未知环节';
        $oObj['content']=$oNodeQa->content;
        $oObj['addtime']=$oNodeQa->created_at;
        $oObj['reply']=array();
        foreach($oNodeReply as $val){
            $arr=array();
            $arr['id']=$val->id;
            $arr['content']=$val->content;
            $arr['addtime']=$val->created_at;
            if($val->user){
                $arr['name']=$val->user->name;
            }
            array_push($oObj['reply'],$arr);
        }
		if (view()->exists(session('mode').'.studentPlat.faq.show')){
			return View(session('mode').'.studentPlat.faq.show',compact('oObj'));
		}else{
			return View('default.studentPlat.faq.show',compact('oObj'));
		}
    }

    //获取某个环节的答疑列表
    public function index(Request $request){
        $node_id=$request->input('node_id',0);
        $squad_id=$request->input('squad_id',0);
        $model=new NodeQa();
        $oNodeQa=$model->where(['node_id'=>$node_id,'squad_id'=>$squad_id,'parent_id'=>0,'is_black'=>0])->with('user')->paginate(5);
        $return=array();
        foreach($oNodeQa as $val){
            $arr=array();
            $arr['content']=$val['content'];
            $arr['replyCount']=$model->replyCount($val->id);
            if($val->user){
                $arr['id']=$val->id;
                $arr['name']=$val->user->name;
                $arr['avatar']=getAvatar($val->user->avater);
            }
            array_push($return,$arr);
        }
        return response()->json($return);
    }

    //某个答疑的回复列表
    public function replyList($id){
        if($id==0){
            $msg = [
                "custom-msg"=> ["参数缺少"],
            ];
            return response()->json($msg)->setStatusCode(422);
        }
        $model=new NodeQa();
        $oObj=$model->where('id',$id)->first();
        if(empty($oObj)){
            $msg = [
                "custom-msg"=> ["答疑不存在"],
            ];
            return response()->json($msg)->setStatusCode(422);
        }
        $oNodeQa=$model->where(['id'=>$id])->with('user')->paginate(5);
        $return=array();
        foreach($oNodeQa as $val){
            $arr=array();
            $arr['content']=$val['content'];
            $arr['replyCount']=$model->replyCount($val->id);
            if($val->user){
                $arr['id']=$val->id;
                $arr['name']=$val->user->name;
                $arr['avatar']=getAvatar($val->user->avater);
            }
            array_push($return,$arr);
        }
        return response()->json($return);
    }

    //提问某个环节
    public function ask(Request $request){
        $plan_id=$request->input('plan_id',0);
        $cell_id=$request->input('cell_id',0);
        $module_id=$request->input('module_id',0);
        $node_id=$request->input('node_id',0);
        $content=$request->input('content','');
        if($plan_id==0 || $cell_id==0 || $module_id==0  || $node_id==0){
            $msg = [
                "custom-msg"=> ["参数缺少"],
            ];
            return response()->json($msg)->setStatusCode(422);
        }
        if($content==''){
            $msg = [
                "custom-msg"=> ["答疑内容不能为空"],
            ];
            return response()->json($msg)->setStatusCode(422);
        }
        $oObj=new NodeQa();
        $oObj->plan_id=$plan_id;
        $oObj->cell_id=$cell_id;
        $oObj->module_id=$module_id;
        $oObj->node_id=$node_id;
        $oObj->user_id=$this->user_id;
        $oObj->ip=$request->getClientIp();
        $oObj->save();
        return response()->json(null);
    }

    //回答某个答疑
    public function reply($id,Request $request){
        $content=$request->input('content','');
        if($id==0){
            $msg = [
                "custom-msg"=> ["参数缺少"],
            ];
            return response()->json($msg)->setStatusCode(422);
        }
        $oObj=NodeQa::where('id',$id)->first();
        if(empty($oObj)){
            $msg = [
                "custom-msg"=> ["答疑不存在"],
            ];
            return response()->json($msg)->setStatusCode(422);
        }
        $newoObj=new NodeQa();
        $newoObj->parent_id=$oObj->id;
        $newoObj->plan_id=$oObj->plan_id;
        $newoObj->squad_id = $oObj->squad_id;
        $newoObj->cell_id=$oObj->cell_id;
        $newoObj->module_id=$oObj->module_id;
        $newoObj->node_id=$oObj->node_id;
        $newoObj->user_id=$this->user_id;
        $newoObj->type = 3;
        $newoObj->content=$content;
        $newoObj->ip=$request->getClientIp();
        $newoObj->save();
        if($oObj->is_reply==0){
            $oObj->is_reply=1;
            $oObj->save();
        }
        $return =array();
        $oObj=NodeQa::where('parent_id',$id)->orderBy('id','desc')->first();
        $return['id']=$oObj->id;
        $return['title']='';
        $return['content']=$content;
        $return['name']=$oObj->user->name;
        $return['avatar']=getAvatar($oObj->user->avatar);
        $return['addtime']=strtotime($oObj->created_at);
        $return['ip']=$oObj->ip;
        return response()->json($return);
    }

    public function getOne($parent_id){
        $oObjs = NodeQa::where('type',3)->where('parent_id',$parent_id);
        $oObjs=$oObjs->orderBy('id','desc')->with('user')->get();
        $return=array();
        foreach($oObjs as $val){
            $arr=array();
            $arr['id']=$val->id;
            $arr['avatar']=getAvatar($val->user_id);
            $arr['name'] = User ::whereId($val->user_id)->value('name');
            $arr['content']=$val->content;
            $arr['addtime']=empty($val->created_at)?0:$val->created_at->format('Y-m-d H:i:s');
            array_push($return,$arr);
        }
        $return = array('data'=>$return);
        return response()->json($return);
    }
}
