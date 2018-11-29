<?php

namespace App\Http\Controllers\Administrator\School;

use App\Models\Teaching\Plan;
use App\Models\NodeQa;
use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;

class OnlineQaController extends Controller
{
    public function index(){
        $plan_list=Plan::get();
		if (view()->exists(session('mode').'.school.onlineqa.list')){
			return View(session('mode').'.school.onlineqa.list', compact('plan_list'));
		}else{
			return View('default.school.onlineqa.list', compact('plan_list'));
		}
    }


    /**
     * ajax加载在线答疑列表
     */
    public function getFaqList(){
        $plan_id= \Request::input('plan_id',0);
        $cell_id= \Request::input('cell_id',0);
        $module_id=\Request::input('module_id',0);
        $node_id=\Request::input('node_id',0);
        $type=\Request::input('type',0);
        $model=new NodeQa();
        $oObjs=$model->where(['parent_id'=>0,'is_black'=>0,'type'=>3]);
        if($type==0){
            $aSearch['type'] = 0;
        }
        elseif($type==1){
            //已回复
            $aSearch['type'] = 1;
            $oObjs->where('is_reply',1);
        }
        elseif($type==2){
            //未回复
            $aSearch['type'] = 2;
            $oObjs->where('is_reply',0);
        }
        if($plan_id!=0){
            $oObjs->where("plan_id",$plan_id);
        }
        if($cell_id!=0){
            $oObjs->where("cell_id",$cell_id);
        }
        if($module_id!=0){
            $oObjs->where("module_id",$module_id);
        }
        if($node_id!=0){
            $oObjs->where("node_id",$node_id);
        }
        $oObjs=$oObjs->orderBy('id','desc')->with('user','squad','plan','module','cell','node');
		$num['a'] = $oObjs->count();
		$oObjs=$oObjs->paginate(5);
		$num['b'] = $oObjs->count();
        $oObjs->setPath('/school/faq_part');
        foreach($oObjs as $k=> &$val){
			if($val->user){
				$val->avatar=getAvatar($val->user->avatar);
			}else{
				unset($oObjs[$k]);
			}
            
            $val->reply=$model->where(['parent_id'=>$val->id,'is_black'=>0])->get();
            $val->replyCount=$model->replyCount($val->id);
        }
		if (view()->exists(session('mode').'.school.onlineqa.qas-list')){
			return View(session('mode').'.school.onlineqa.qas-list', compact('oObjs','num'));
		}else{
			return View('default.school.onlineqa.qas-list', compact('oObjs','num'));
		}
    }

    /**
     * 获取一个在线答疑的回复
     * @param $id
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function getReplyList(Request $request,$id){
        $page=$request->input('page',1);
        $pagesize=2;
        $model=new NodeQa();
        $oObjs=$model->where(['parent_id'=>$id,'is_black'=>0,'type'=>3]);
        $totalRows=$oObjs->count();
        $pages=ceil($totalRows/$pagesize);
        $offset=($page-1)*$pagesize;
        $oObjs=$oObjs->orderBy('id','desc')->with('user')->skip($offset)->take($pagesize)->get();
        $data=array();
        foreach($oObjs as $val){
            $return =array();
            $return['id']=$val->id;
            $return['title']=$val->title;
            $return['content']=$val->content;
            $return['name']=$val->user->name;
            $return['avatar']=getAvatar($val->user->avatar);
            $return['addtime']=$val->created_at;
            $return['ip']=$val->ip;
            array_push($data,$return);
        }
        $return = array('pager'=>array('total'=>$totalRows,'pages'=>$pages,'page'=>$page),'data'=>$data);
        return response()->json($return);
    }

    /**
     * 屏蔽某条消息
     * @return \Illuminate\Http\JsonResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function black($id){
        if(!$id) {
            $msg = ["msg"=> ["参数错误，非法操作"],];
            return response()->json($msg);
        } else {
            $oObj=NodeQa::where('id',$id)->first();
            if(empty($oObj)){
                $msg = [
                    "msg"=> ["回复的答疑不存在"],
                ];
                return response()->json($msg)->setStatusCode(422);
            }
            $oObj->is_black=1;
            $oObj->save();
			return back();
            //return response()->json(null);
        }
    }
}
