<?php

namespace App\Http\Controllers\Student;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use App\Http\Requests;
use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\NotifyUser;
use App\Models\User;
use Illuminate\Support\Facades\DB;
class MynotifyController extends BaseController
{

    //我的消息
    public function index(){
		if (view()->exists(session('mode').'.studentPlat.notify.list')){
			return View(session('mode').'.studentPlat.notify.list');
		}else{
			return View('default.studentPlat.notify.list');
		}
    }


    public function notifyJson(Request $request){
        $user_id = $this->user_id;
        $type= $request->input('type',0);
        $page=$request->input('page',1);
        $pagesize = 30;
        $oObjs = NotifyUser::where('user_id',$user_id);//系统消息关联的是notify_users，微信消息，邮件，短信是多态关联的notify_pivots表
        $oObjs =$oObjs->with(['notify' => function($query) {
            $query->groupBy('notifies.user_id');
        }]);

//        $count = 0;
//        $totalRows=$oObjs->with(['notify' => function($query) use(&$count,$oObjs) {
//            $query->groupBy('user_id');
//            if($oObjs->notify){
//                $count = $oObjs->count();
//            }
//            }])->get();
        if($type==1){
            $oObjs->where('is_scan',0);
        }
        if($type==2){
            $oObjs->where('is_scan',1);
        }
        $totalRows=$oObjs->count();
        $pages=ceil($totalRows/$pagesize);
        $offset=($page-1)*$pagesize;
        $oObjs = $oObjs->orderBy('is_scan','asc')->skip($offset)->take($pagesize)->get();
        $return=array();
        foreach($oObjs as $val){
            $arr=array();
            if(isset($val->notify)){
                $arr['id']=$val->id;
                $arr['user_id']=$val->notify->user_id;
                $arr['avatar']=getAvatar(User::where('id',$val->notify->user_id)->value('avatar'));
                $arr['send_name']=User::where('id',$val->notify->user_id)->value('name');
                $arr['title']=$val->notify->title;
                $arr['addtime']=$val->notify->send_time;
                $arr['is_read']=$val->is_scan;
                array_push($return,$arr);
            }
        }
        $return = array('pager'=>array('total'=>$totalRows,'pages'=>$pages,'page'=>$page),'data'=>$return);
        return response()->json($return);
    }

    public function show($send_id){
        $ou = User::find($send_id);
        if(!$ou){
            return redirect("/");
        }
        $mid = \Request::input('mid') ?? 0;
        if($mid){
            $oMsg =  NotifyUser::find($mid);
            $oMsg->is_scan =1;
            $oMsg->save();
        }
        return View('default.studentPlat.notify.list',compact('send_id'));
    }

    public function getNotify($send_id){
        $user_id = $this->user_id;
         $page = empty(\Request::input('page'))?1:\Request::input('page');
        $oObjs = NotifyUser::where('user_id',$user_id);

        $pagesize = 6;
        $oObjs=$oObjs->with(['notify' => function($query)use($send_id,&$count,$page,$pagesize,&$pages) {
            $query->where('user_id',$send_id);
            $count = $query->count();
            $pages=ceil($count/$pagesize);
            $offset=($page-1)*$pagesize;
            $query->skip($offset)->take($pagesize);
        }])->orderBy('notify_id','desc')->get();
//        $totalRows = $oObjs->count();
//        $pagesize = 30;
//        $pages=ceil($count/$pagesize);
//        $offset=($page-1)*$pagesize;
//        $oObjs = $oObjs->orderBy('notify_id','desc')->skip($offset)->take($pagesize)->get();
        $return=array();
        foreach($oObjs as $val){
            $arr=array();
            if(isset($val->notify)){
                $arr['id']=$val->notify_id;
                $arr['user_id']=$val->notify->user_id;
                $arr['avatar']=getAvatar(User::where('id',$val->notify->user_id)->value('avatar'));
                $arr['send_name']=User::where('id',$val->notify->user_id)->value('name');
                $arr['title']=$val->notify->title;
                $arr['addtime']=$val->notify->send_time;
                $arr['is_read']=$val->is_scan;
                array_push($return,$arr);
            }
        }

//        $return = array();

        return response()->json(['pager'=>array('total'=>$count,'pages'=>$pages,'page'=>$page),'data'=>$return]);

    }
    public function notifyView(Request $request){
        $id=$request->input('id',0);
        $user_id = $this->user_id;
        $oObj = NotifyUser::where(['notify_id'=>$id,'user_id'=>$user_id])->with('notify')->first();
        if(empty($oObj)){
            $msg = [
                "custom-msg"=> ["消息不存在"],
            ];
            return response()->json($msg)->setStatusCode(422);
        }
        if($oObj->user_id!=$this->user_id){
            $msg = [
                "custom-msg"=> ["消息不是发送给您的"],
            ];
            return response()->json($msg)->setStatusCode(422);
        }
        if(empty($oObj->notify)){
            $msg = [
                "custom-msg"=> ["消息已不存在"],
            ];
            return response()->json($msg)->setStatusCode(422);
        }
        if($oObj->is_scan==0){
            $oObj->is_scan=1;
            $oObj->save();
        }
        $data=array(
            'title'=>$oObj->notify->title,
            'content'=>$oObj->notify->content,
            'is_read'=>$oObj->is_scan,
            'addtime'=>$oObj->notify->send_time,
        );
		if (view()->exists(session('mode').'.studentPlat.notify.show')){
			return View(session('mode').'.studentPlat.notify.show',compact('data'));
		}else{
			return View('default.studentPlat.notify.show',compact('data'));
		}
    }
}
