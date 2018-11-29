<?php

namespace App\Http\Controllers\School;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Models\Notify;
use App\Models\Tag;
use App\Models\NotifyTemplate;
use App\Models\Squad;
use App\Models\User;

// 系统消息
class NotifyController extends BaseController
{

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $aSearch = [];
        $keywords=$where='';
        \Request::has('keywords') &&  $aSearch['keywords']=$keywords = \Request::input('keywords');
        $oObjs = Notify::whereIn('send_method',[1,3])->where('user_id',$this->user_id);//系统消息关联的是notify_users，微信消息，邮件，短信是多态关联的notify_pivots表
        if($keywords!=''){
            $oObjs = $oObjs->where('title','like', '%'.$keywords.'%');
        }
        $oObjs = $oObjs->orderBy('id','desc')->paginate(20);
		if(view()->exists(session('mode').'.schoolplat.notify.notify_list')){
			return View(session('mode').'.schoolplat.notify.notify_list', compact('oObjs','aSearch'));
		}else{
			return View('default.schoolplat.notify.notify_list', compact('oObjs','aSearch'));
		}
    }

    private function _userIds($users){
        $user_ids=array();
        $squad_ids=array();
        $squad_regix = '/^squad\|(\d+)\|all$/i';
        $school_id=$this->school_id;
        foreach($users as $val){
            if($val=='allAdmin'){
                $user=User::where('plat',0)->pluck('id')->toArray();//所有系统管理员
                empty($user) || $user_ids=array_merge($user_ids,$user);
            }
            elseif($val=='AllSchoolAdmin'){
                $user=User::where('plat',1)->pluck('id')->toArray();//所有学校管理员
                empty($user) || $user_ids=array_merge($user_ids,$user);
            }
            elseif($val=='AllTeacher'){
                $user=User::where('plat',2)->pluck('id')->toArray();//系统所有老师
                empty($user) || $user_ids=array_merge($user_ids,$user);
            }
            elseif($val=='AllSchoolTeacher'){
                $user=User::where(['plat'=>2,'school_id'=>$school_id])->pluck('id')->toArray();//所有老师
                empty($user) || $user_ids=array_merge($user_ids,$user);
            }
            elseif(preg_match($squad_regix,$val,$matches)){
                $squad_id=Squad::where(['id'=>$matches[1]])->first(['id']);//所有老师
                empty($squad_id) || array_push($squad_ids,$squad_id->id);
            }
            elseif(preg_match('/^(\d+)$/',$val,$matches)){
                array_push($user_ids,$matches[1]);
            }
        }
        return array('user_ids'=>$user_ids,'squad_ids'=>$squad_ids);
    }

    /**
     * Store a newly created resource in storage.
     * 提交创建|修改
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $send_method=$request->input('send_method');
        $user_id=\Auth::user()->id;
        $title=$request->input('title','');
        //系统消息跟邮件
        if($send_method==1 || $send_method=3){
            $content=$request->input('content','');
            $send_time=$request->input('send_time');
            $oNotify = Notify::create([
                'title' => $title,
                'send_time' => $send_time,
                'user_obj' => $request->input('userids',''),
                'template_id' => 0,
                'send_method' => $send_method,
                'content' => $content,
                'url' => '',
                'user_id' => $user_id
            ]);
            // 后期去掉
            $oNotify->id = Notify::where('user_id', $user_id)->orderBy('id','desc')->take(1)->value('id');
            //$users=array('25');
            $users=json_decode($request->input('userids'),true);
            $return=$this->_userIds(array_keys($users));
            if(!empty($return['user_ids'])) {
                $oNotify->users()->sync($return['user_ids']);
            }
            if(!empty($return['squad_ids'])) {
                $oNotify->squads()->sync($return['squad_ids']);
            }
            return response()->json(null);
        }
        else{
            return response()->json(['msg' => '发送类型选择错误',])->setStatusCode(422);
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
        if($id){
            $oObj = Notify::where(['id'=>$id])->first();
            if(!$oObj){
                return response()->json(['msg' => '学生不存在',])->setStatusCode(422);
            }
            if($oObj->user_id != $this->user_id){
                return response()->json(['msg' => '不能修改他人的消息',])->setStatusCode(422);
            }
            return response()->json($oObj);
        }
        else{
            return response()->json(['msg' => '参数缺失',])->setStatusCode(422);
        }
    }

    /**
     * Display the specified resource.
     * 展示单条
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        if(!$id) {
            $msg = [
                "msg"=> ["参数错误，非法操作"],
            ];
            return back()->withInput()->withErrors($msg);
        }
        $oObj=Notify::where('id',$id)->first();
        if(empty($oObj)){
            $msg = [
                "msg"=> ["消息不存在"],
            ];
            return back()->withInput()->withErrors($msg);
        }
        if($oObj->user_id != $this->user_id){
            $msg = [
                "msg"=> ["不能查看他人的消息"],
            ];
            return back()->withInput()->withErrors($msg);
        }
		if(view()->exists(session('mode').'.schoolplat.notify.notify_show')){
			return View(session('mode').'.schoolplat.notify.notify_show', compact('oObj'));
		}else{
			return View('default.schoolplat.notify.notify_show', compact('oObj'));
		}
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
        $oNotify = Notify::find($id);
        if(!$oNotify) {
            $msg = [
                "msg"=> ["请求错误，没有指定id"],
            ];
            return response()->json($msg)->setStatusCode(422);
        }
        if($oNotify->user_id != $this->user_id){
            $msg = [
                "msg"=> ["不能查看编辑他人的消息"],
            ];
            return back()->withInput()->withErrors($msg);
        }
        $oNotify->title = $request->input('title');
        $oNotify->content = $request->input('content');
        //立即发送
        $send_time=$request->input('send_time',date('Y-m-d H:i:s'));
        $oNotify->send_time = $send_time;
        $oNotify->send_method = $request->input('send_method');
        $oNotify->user_obj = $request->input('userids');
        $oNotify->user_id = \Auth::user()->id;
        $oNotify->save();
        $users=json_decode($request->input('userids'),true);
        $return=$this->_userIds(array_keys($users));
        if(!empty($return['user_ids'])) {
            $oNotify->users()->sync($return['user_ids']);
        }
        if(!empty($return['squad_ids'])) {
            $oNotify->squads()->sync($return['squad_ids']);
        }
        return response()->json(null);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        if(!$id) {
            $msg = ["msg"=> ["参数错误，非法操作"],];
            return back()->withInput()->withErrors($msg);
        }
        $oNotify = Notify::find($id);
        if($oNotify->user_id != $this->user_id){
            $msg = [
                "msg"=> ["不能查看删除他人的消息"],
            ];
            return back()->withInput()->withErrors($msg);
        }
        $oNotify->tags()->detach();
        $oNotify->users()->detach();
        $oNotify->delete();
        return back();
    }

}
