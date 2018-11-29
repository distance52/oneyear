<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use App\Models\Notify;
use App\Models\NotifyUser;
use App\Models\User;

class MyNotifyController extends Controller
{
    /**
     * 用户查看消息列表
     * @return \Illuminate\Http\JsonResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function index(){
        if(\Auth::check()) {
            $oUser = \Auth::user();
            $user_id = $oUser->id;
            $aSearch = [];
            $title=$tag=$status=$where='';
            \Request::has('title') &&  $aSearch['title']=$title = \Request::input('title');
            \Request::has('status') &&  $aSearch['status']=$status = \Request::input('status');
            \Request::has('tag') &&  $aSearch['tag']=$tag = \Request::input('tag');
            $oObjs = NotifyUser::where('user_id',$user_id)->orderBy('notify_id','desc')->with('notify');//系统消息关联的是notify_users，微信消息，邮件，短信是多态关联的notify_pivots表
            if($title!=''){
                $oObjs = $oObjs->where('title','like', '%'.$title.'%');
            }
            if($status!=''){
                $oObjs = $oObjs->where('is_scan',$status);
            }
            $oObjs = $oObjs->orderBy('notify_id','desc')->paginate(20);
            $result=array();
            foreach($oObjs as $val){
                if(!isset($val->notify->id)){
                    continue;
                }
                if($val->notify->user_id>0){
                    $return['sender_name']=User::where('id',$val->notify->user_id)->value('name');
                }
                else{
                    $return['sender_name']='';
                }
                $return['title']=$val->notify->title;
                $return['addtime']=$val->notify->send_time;
                $return['url']='/usercenter/mynotify/'.$val->notify->id;
                $result[]=$return;
            }
            //\Debugbar::info($oObjs);
            return response()->json($result);
        }
        else{
            $msg = [
                "custom-msg"=> ["需要登录，还未登录"],
            ];
            return response()->json($msg)->setStatusCode(422);
        }
    }



    /**
     * 查看消息详情
     * @param $id
     */
    public function show($id){
        if(\Auth::check()) {
            $oUser = \Auth::user();
            $user_id = $oUser->id;
            $oObj = NotifyUser::where('id',$id)->with('notify')->first();
            if(empty($oObj)){
                $msg = [
                    "custom-msg"=> ["消息不存在"],
                ];
                return response()->json($msg)->setStatusCode(422);
            }
            if($oObj->user_id!=$user_id){
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
                $oObj->is_scan==1;
                $oObj->save();
            }
            return response()->json($oObj);
        }
        else{
            $msg = [
                "custom-msg"=> ["需要登录，还未登录"],
            ];
            return response()->json($msg)->setStatusCode(422);
        }
    }
}
