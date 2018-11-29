<?php

namespace App\Http\Controllers\Student;

use App\Models\NotifyPivot;
use App\Models\Teacher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use App\Http\Requests;
use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\School;
use App\Models\Squad;
use App\Models\Group;
use App\Models\Message;
use App\Models\MessagePeople;
use App\Models\User;
use App\Models\NotifyUser;
use App\Models\Notify;

class MessageController extends BaseController
{

    //我的消息
    public function index(){
		$student_id = $this->student_id;
		$message_id = NotifyUser::where('receive_type',2)->where('user_id',$student_id)->pluck('notify_id');
//		$message = Notify::whereIn('id',$message_id)->groupBy('send_type')->groupBy('user_id')->with(['squads' => function($query) {
//			$query->groupBy('notify_pivots_id');
//		}])->orderBy('id','desc')->get();
//		$message = NotifyPivot::whereIn('notify_id',$message_id)->groupBy('notify_pivots_id')->with('squads')->get();

		$message = NotifyPivot::whereIn('notify_id',$message_id)->groupBy('notify_pivots_id')->with('notify')->get();
		$oObj = [];
		foreach($message as $k=>$v){
			//发送人类型	0为系统管理，1为老师，2为学生，3为学校，4为班级，5为组
			if($v->notify->send_type==0 ||$v->notify->send_type==3 ||$v->notify->send_type==4 ||$v->notify->send_type==5){
				if($v->notify->send_type==4){
//					$squad = Squad::where('id',$v->user_id)->first();
//					$teacher_id	= Teacher::where('user_id',$v->user_id)->value('id');
					$oObj[$k]['name'] = Squad::whereId($v->notify_pivots_id)->value('name');
				}elseif($v->notify->send_type==5){
					$group = Group::where('id',$v->notify->user_id)->first();

					$oObj[$k]['name'] = $group->name;
				}elseif($v->notify->send_type==3){
					$school = School::where('id',$v->notify->user_id)->first();
//					$school_id = $school->id;
					$oObj[$k]['name'] = $school->name;
				}else{
//					$school_id = 0;
					$oObj[$k]['name'] = "系统消息";
				}
//				$oObj[$k]['avatar'] = \AliyunOSS::getUrl($school->logo, $expire = new \DateTime("+3 year"), $bucket = config('filesystems.disks.oss.bucket'));
//dd($school->logo);
				$oObj[$k]['avatar'] = mb_substr($oObj[$k]['name'],0,1,'utf8' );
			}else{
				$user = User::where('id',$v->user_id)->value('name');
				$oObj[$k]['avatar'] = mb_substr($user,0,1,'utf8' );
				$oObj[$k]['name'] = $user;
			}
			$new = Notify::where('send_type',$v->notify->send_type)->where('user_id',$v->notify->user_id)->orderBy('id','desc');
			$count = $new->pluck('id');
			$news = $new->first();
			$oObj[$k]['desc'] = $news->title;
			$oObj[$k]['time'] = $news->created_at;
			$oObj[$k]['count'] = NotifyUser::whereIn('notify_id',$count)->where('user_id',$student_id)->where('is_scan',1)->count();
			//1为个人文本，2为附件，3为作业，4为预习，5为预习，6为评分，等等……	
			$oObj[$k]['type'] = $v->notify->news_type;
			$oObj[$k]['send_id'] = $v->notify_pivots_id;
			$oObj[$k]['send_type'] = $v->notify->send_type;
		}
		if (view()->exists(session('mode').'.studentPlat.message.list')){
			return View(session('mode').'.studentPlat.message.list',compact('oObj'));
		}else{
			return View('default.studentPlat.message.list',compact('oObj'));
		}
    }

//	public function index_old(){
//		$student_id = $this->student_id;
//		$message_id = MessagePeople::where('receive_type',2)->where('user_id',$student_id)->pluck('message_id');
//		$message = Message::whereIn('id',$message_id)->groupBy('send_type')->groupBy('send_id')->orderBy('id','desc')->get();
//		$oObj = [];
//		foreach($message as $k=>$v){
//			//发送人类型	0为系统管理，1为老师，2为学生，3为学校，4为班级，5为组
//			if($v->send_type==0 ||$v->send_type==3 ||$v->send_type==4 ||$v->send_type==5){
//				if($v->send_type==4){
//					$squad = Squad::where('id',$v->send_id)->first();
//					$school_id = $squad->school_id;
//					$oObj[$k]['name'] = $squad->name;
//				}elseif($v->send_type==5){
//					$group = Group::where('id',$v->send_id)->first();
//					$school_id = $group->school_id;
//					$oObj[$k]['name'] = $group->name;
//				}elseif($v->send_type==5){
//					$school = School::where('id',$v->send_id)->first();
//					$school_id = $school->id;
//					$oObj[$k]['name'] = $school->name;
//				}else{
//					$school_id = 0;
//					$oObj[$k]['name'] = "系统消息";
//				}
//				$school = School::where('id',$school_id)->first();
//				$oObj[$k]['avatar'] = \AliyunOSS::getUrl($school->logo, $expire = new \DateTime("+3 year"), $bucket = config('filesystems.disks.oss.bucket'));
//			}else{
//				$user = User::where('id',$v->send_id)->first();
//				$oObj[$k]['avatar'] = getAvatar($user->avatar);
//				$oObj[$k]['name'] = $user->name;
//			}
//			$new = Message::where('send_type',$v->send_type)->where('send_id',$v->send_id)->orderBy('id','desc');
//			$count = $new->pluck('id');
//			$news = $new->first();
//			$oObj[$k]['desc'] = $news->title;
//			$oObj[$k]['time'] = $news->created_at;
//			$oObj[$k]['count'] = MessagePeople::whereIn('message_id',$count)->where('user_id',$student_id)->where('is_scan',1)->count();
//			//1为个人文本，2为附件，3为作业，4为预习，5为预习，6为评分，等等……
//			$oObj[$k]['type'] = $v->news_type;
//			$oObj[$k]['send_id'] = $v->send_id;
//			$oObj[$k]['send_type'] = $v->send_type;
//		}
//		if (view()->exists(session('mode').'.studentPlat.message.list')){
//			return View(session('mode').'.studentPlat.message.list',compact('oObj'));
//		}else{
//			return View('default.studentPlat.message.list',compact('oObj'));
//		}
//	}

    public function lists($send_id,$send_type){
		$oObj = [];
		if($send_type==0 ||$send_type==3 ||$send_type==4 ||$send_type==5){
			if($send_type==4){
				$squad = Squad::where('id',$send_id)->first();

				$id = $squad->id;
				$model = "App\\Models\\Squad";
				$oObj['name'] = $squad->name;
			}elseif($send_type==5){
				$group = Group::where('id',$send_id)->first();
				$oObj['name'] = $group->name;
				$id = $group->id;
				$model = "App\\Models\\Group";
			}elseif($send_type==3){
				$school = School::where('id',$send_id)->first();
				$oObj['name'] = $school->name;
				$id = $school->id;
				$model = "App\\Models\\School";
			}else{
				$oObj['name'] = "系统消息";
				$id ='';
				$model = "";
			}
//			$oObj['avatar'] =  mb_substr($oObj['name'] , 0, 1,'utf8' );
		}else{
			$user = User::where('id',$send_id)->value('name');
//			$oObj['avatar'] = mb_substr($user , 0, 1,'utf8' );
			$oObj['name'] = $user;
		}

		$id_list = NotifyPivot::where('notify_pivots_id',$id)->where('notify_pivots_type',$model)->pluck('notify_id')->toArray();
		$message = Notify::where('send_type',$send_type)->whereIn('id',$id_list)->orderBy('id','desc')->get();

//		$message = Notify::where('send_type',$send_type)->with(['notifyPivots' => function($query)use($send_id) {
//			$query->where('notify_pivots_id',$send_id);
//		}])->orderBy('id','desc')->get();

		foreach($message as $k=>$v){
			if($v->content){
				$v->content = json_decode($v->content);
			}
			if(!$v->content){
				unset($message[$k]);
			}
			$v->is_scan = 2;
			$v->scan_id = 0;
			$scan = NotifyUser::where('notify_id',$v->id)->where('receive_type',2)->where('user_id',$this->student_id)->first();
			$v->is_scan = $scan->is_scan ?? 0;
			$v->scan_id = $scan->id ?? 0;
		}
		if (view()->exists(session('mode').'.studentPlat.message.list')){
			return View(session('mode').'.studentPlat.message.show',compact('oObj','message'));
		}else{
			return View('default.studentPlat.message.show',compact('oObj','message'));
		}
    }

//	public function lists_old($send_id,$send_type){
//		$oObj = [];
//		if($send_type==0 ||$send_type==3 ||$send_type==4 ||$send_type==5){
//			if($send_type==4){
//				$squad = Squad::where('id',$send_id)->first();
//				$school_id = $squad->school_id;
//				$oObj['name'] = $squad->name;
//			}elseif($send_type==5){
//				$group = Group::where('id',$send_id)->first();
//				$school_id = $group->school_id;
//				$oObj['name'] = $group->name;
//			}elseif($send_type==5){
//				$school = School::where('id',$send_id)->first();
//				$school_id = $school->id;
//				$oObj['name'] = $school->name;
//			}else{
//				$school_id = 0;
//				$oObj['name'] = "系统消息";
//			}
//			$school = School::where('id',$school_id)->first();
//			$oObj['avatar'] = \AliyunOSS::getUrl($school->logo, $expire = new \DateTime("+3 year"), $bucket = config('filesystems.disks.oss.bucket'));
//		}else{
//			$user = User::where('id',$send_id)->first();
//			$oObj['avatar'] = getAvatar($user->avatar);
//			$oObj['name'] = $user->name;
//		}
//		$message = Message::where('send_type',$send_type)->where('send_id',$send_id)->orderBy('id','desc')->get();
//		foreach($message as $k=>$v){
//			if($v->content){
//				$v->content = json_decode($v->content);
//			}
//			if(!$v->content){
//				unset($message[$k]);
//			}
//			$v->is_scan = 2;
//			$v->scan_id = 0;
//			$scan = MessagePeople::where('message_id',$v->id)->where('receive_type',2)->where('user_id',$this->student_id)->first();
//			$v->is_scan = $scan->is_scan;
//			$v->scan_id = $scan->id;
//		}
//		if (view()->exists(session('mode').'.studentPlat.message.list')){
//			return View(session('mode').'.studentPlat.message.show',compact('oObj','message'));
//		}else{
//			return View('default.studentPlat.message.show',compact('oObj','message'));
//		}
//	}

	public function scan(Request $request,$id)
	{
		$url = $request->url;
		if($id){
			$msg = NotifyUser::where('id',$id)->first();
			$msg->is_scan = 2;
			$msg->save();
		}
		return redirect($url);
	}

//	public function scan_old(Request $request,$id)
//	{
//		$url = $request->url;
//		if($id){
//			$msg = MessagePeople::where('id',$id)->first();
//			$msg->is_scan = 2;
//			$msg->save();
//		}
//		return redirect($url);
//	}
}
