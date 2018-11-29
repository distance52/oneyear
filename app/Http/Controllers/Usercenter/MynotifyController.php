<?php

namespace App\Http\Controllers\Usercenter;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use App\Models\NotifyUser;
use App\Models\User;

class MynotifyController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $oUser = \Auth::user();
        $user_id = $oUser->id;
        $aSearch = [];
        $type=0;
        $where='';
        \Request::has('type') &&  $aSearch['type']= $type = \Request::input('type');
        $oObjs = NotifyUser::where('user_id',$user_id)->with('notify');//系统消息关联的是notify_users，微信消息，邮件，短信是多态关联的notify_pivots表
        if($type==1){
            $aSearch['type']=$type;
            $oObjs->where('is_scan','=',0);
        }
        if($type==2){
            $aSearch['type']=$type;
            $oObjs->where('is_scan','=',1);
        }
        $oObjs = $oObjs->orderBy('notify_id','desc')->paginate(20);
        foreach($oObjs as $val){
            if(isset($val->notify) && $val->notify->user_id>0){
                $val->sender_name=User::where('id',$val->notify->user_id)->value('name');
                $val->title=$val->notify->title;
                $val->addtime=$val->notify->send_time;
            }
            else{
                $val->sender_name='';
                $val->title='';
                $val->addtime='';
            }
            unset($val->notify);
        }
		if (view()->exists(session('mode').'.usercenter.mynotify.list')){
			return View(session('mode').'.usercenter.mynotify.list', compact('oObjs','aSearch','type'));
		}else{
			return View('default.usercenter.mynotify.list', compact('oObjs','aSearch','type'));
		}
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        if(!$id) {
            $msg = [
                "custom-msg"=> ["参数错误，非法操作"],
            ];
            return back()->withInput()->withErrors($msg);
        }
        $oUser = \Auth::user();
        $user_id = $oUser->id;
        $oObj = NotifyUser::where(['notify_id'=>$id,'user_id'=>$user_id])->with('notify')->first();
        if($oObj->notify->is_scan == 0){
            $is_scan = NotifyUser::where(['notify_id'=>$id,'user_id'=>$user_id])->update(['is_scan'=>1]);//修改已读未读状态
            if(!isset($is_scan)){
                $msg = [
                "custom-msg"=> ["修改已读状态失败"],
            ];
            return back()->withErrors($msg);
            }
        }
        
        
        if(empty($oObj)){
            $msg = [
                "custom-msg"=> ["消息不存在"],
            ];
            return back()->withErrors($msg);
        }
        if($oObj->user_id!=$user_id){
            $msg = [
                "custom-msg"=> ["消息不是发送给您的"],
            ];
            return back()->withErrors($msg);
        }

         $content = json_decode($oObj->notify->content,true);


         if (view()->exists(session('mode').'.usercenter.mynotify.show-v2')){
                 return View(session('mode').'.usercenter.mynotify.show-v2', compact('oObj','content'));
         }else{
                 return View('default.usercenter.mynotify.show-v2', compact('oObj','content'));
         }
        
        
        
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
