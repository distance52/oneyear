<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use App\Models\Notify;
use App\Models\Message;
class WechatNotifyController extends Controller
{
    public static function __callStatic($func, $arguments){
        if(!\Auth::check()) {
            return array('status'=>0,'info'=>'还未登录');
        }
        $oUser = \Auth::user();
        $user_id=$oUser->id;
        $wechat_data=array();
		$type = 3;
        if($func=='homework_publish'){
			$title = "作业发布通知";
            //作业发布通知
            $template_id='9MeZ96HRU1pQlg4CucJv54U5WXAEHAUB0nJL8ANsvPE';
            $fields='keyword1,keyword2,keyword3,keyword4,keyword5';
            $wechat_data['first']=(!isset($arguments[1]) || $arguments[1]=='')?'同学，你有一个新的作业：':$arguments[1];
            $wechat_data['remark']=(!isset($arguments[2]) || $arguments[2]=='')?'点击详情开始在线做作业!':$arguments[2];
        }
        if($func=='homework_cuijiao'){
			$title = "作业催缴通知";
            //作业催缴通知
            $template_id='VA6HAcDe_X6w9uRSp1pY1EJMJUAQHgTF0QNuG4LBb1M';
            $fields='keyword1,keyword2';
            $wechat_data['first']=(!isset($arguments[1]) || $arguments[1]=='')?'同学你好，以下作业即将到期：':$arguments[1];
            $wechat_data['remark']=(!isset($arguments[2]) || $arguments[2]=='')?'点击进入完成作业!':$arguments[2];
        }
        if($func=='homework_piyue'){
			$title = "作业批阅提醒";
            //作业批阅提醒
            $template_id='lFmBn8OuVaDX_CWqjWX7U0iyZKtTQLGdkkSBsEO71Z0';
            $fields='keyword1,keyword2,keyword3';
            $wechat_data['first']=(!isset($arguments[1]) || $arguments[1]=='')?'你的作业已批阅：':$arguments[1];
            $wechat_data['remark']=(!isset($arguments[2]) || $arguments[2]=='')?'点击查看详情!':$arguments[2];
        }
        if($func=='homework_yuxi'){
			$type = 4;
			$title = "预习提醒";
            //预习提醒
            $template_id='9MeZ96HRU1pQlg4CucJv54U5WXAEHAUB0nJL8ANsvPE';
            $fields='keyword1,keyword2,keyword3,keyword4,keyword5';
            $wechat_data['first']=(!isset($arguments[1]) || $arguments[1]=='')?'同学，你有一个新的预习任务：':$arguments[1];
            $wechat_data['remark']=(!isset($arguments[2]) || $arguments[2]=='')?'点击详情进入预习!':$arguments[2];
            $wechat_data['keyword4']=(!isset($arguments[8]) || $arguments[8]=='')?'下次上课前完成':$arguments[8];
            $wechat_data['keyword5']=(!isset($arguments[9]) || $arguments[9]=='')?'根据预习内容确定':$arguments[9];

        }
        if($func=='ketang_pingfen'){
			$type = 6;
			$title = "课堂评分提醒";
            //课堂评分提醒
            $template_id='9MeZ96HRU1pQlg4CucJv54U5WXAEHAUB0nJL8ANsvPE';
            $fields='keyword1,keyword2,keyword3,keyword4,keyword5';
            $wechat_data['first']=(!isset($arguments[1]) || $arguments[1]=='')?'同学，请根据该组的表现，给予评分：':$arguments[1];
            $wechat_data['remark']=(!isset($arguments[2]) || $arguments[2]=='')?'点击详情进入评分!':$arguments[2];
            $wechat_data['keyword5']=(!isset($arguments[9]) || $arguments[9]=='')?'根据评分内容确定':$arguments[9];
        }
        if($func=='score_pingfen'){
			$type = 6;
			$title = "评分分配";
            //评分分配
            $template_id='XOG0Kkj-b06zam9eY3vhddge_IrAbWoPgHQeT735cbo';
            $fields='keyword1,keyword2';
            $wechat_data['first']=(!isset($arguments[1]) || $arguments[1]=='')?'同学，积分开始分配了：':$arguments[1];
            $wechat_data['remark']=(!isset($arguments[2]) || $arguments[2]=='')?'点击进入“我的分组”进行分配!':$arguments[2];
            $wechat_data['keyword1']=(!isset($arguments[5]) || $arguments[5]=='')?'组内分配积分':$arguments[5];
            $wechat_data['keyword2']=(!isset($arguments[6]) || $arguments[6]=='')?'项目组/专题组开始分配积分了，赶紧进入给组员分配积分吧，48小时内分配有效，过期视为自动弃权哦':$arguments[6];
        }
		if($func=='student_sign'){
			$title = "签到通知";
            //签到通知
            $template_id='lFmBn8OuVaDX_CWqjWX7U0iyZKtTQLGdkkSBsEO71Z0';
            $fields='keyword1,keyword2,keyword3,keyword4,keyword5';
            $wechat_data['first']=(!isset($arguments[1]) || $arguments[1]=='')?'同学，上课了，赶紧来签到吧！：':$arguments[1];
            $wechat_data['remark']=(!isset($arguments[2]) || $arguments[2]=='')?'点击详情进行签到!':$arguments[2];
        }
        if($func=='homework_vote'){
			$title = "发布投票通知";
            //发送投票通知
            $template_id='lFmBn8OuVaDX_CWqjWX7U0iyZKtTQLGdkkSBsEO71Z0';
            $fields='keyword1,keyword2,keyword3,keyword4,keyword5';
            $wechat_data['first']=(!isset($arguments[1]) || $arguments[1]=='')?'同学，还没有投票吧?赶紧来投票哦：':$arguments[1];
            $wechat_data['remark']=(!isset($arguments[2]) || $arguments[2]=='')?'点击详情开始投票!':$arguments[2];
        }
        if($func=='student_reward'){
			$title = "点名加分";
            //点名加分
            $template_id='lFmBn8OuVaDX_CWqjWX7U0iyZKtTQLGdkkSBsEO71Z0';
            $fields='keyword1,keyword2,keyword3,keyword4,keyword5';
            $wechat_data['first']=(!isset($arguments[1]) || $arguments[1]=='')?'同学,看来查看你的成绩吧！：':$arguments[1];
            $wechat_data['remark']=(!isset($arguments[2]) || $arguments[2]=='')?'点击详情查看成绩!':$arguments[2];
        }
        if(!isset($arguments[0]) || $arguments[0]==0){
            return array('status'=>0,'info'=>'班级id不能为空');
        }
        if(!isset($wechat_data['first']) || $wechat_data['first']==''){
            return array('status'=>0,'info'=>'缺少first参数');
        }
        if(!isset($wechat_data['remark']) || $wechat_data['remark']==''){
            return array('status'=>0,'info'=>'缺少remark参数');
        }
        $field_keys=explode(',',$fields);
        foreach($field_keys as $key=>$val){
            //keyword1对应4的键
            if(!isset($arguments[$key+5])){
                return array('status'=>0,'info'=>'缺少'.$val.'参数');
            }
            empty($wechat_data[$val]) && $wechat_data[$val]=$arguments[$key+5];
        }

        $send_time=!empty($arguments[4])?$arguments[4]:date('Y-m-d H:i',time());
        $url=!empty($arguments[3])?$arguments[3]:'';
        $squad_id=$arguments[0];
        $wechat_data=json_encode($wechat_data);
        $oNotify = Notify::create([
            'title' => '微信通知',
            'send_time' => $send_time,
            'template_id' => $template_id,
            'send_method' => 4,
            'content' => $wechat_data,
            'url' => $url,
            'user_id' => $user_id,
            'send_type' => 4
        ]);
        $notify = new Notify;
        $notify->add_users(2,$squad_id,$oNotify->id);
        // 后期去掉
        $oNotify->id = Notify::where('user_id', $user_id)->orderBy('id','desc')->take(1)->value('id');
        //作业催缴之针对个人，不针对班级
        if($func=='homework_cuijiao'){
            $oNotify->users()->sync([$squad_id]);
        }
        else{
            $oNotify->squads()->sync([$squad_id]);
        }
//		$massage = new Message;
//
//		$massage->title = $title;
//		$massage->url = $url;
//		$massage->content = $wechat_data;
//		$massage->send_method = 1;
//		$massage->send_time = $send_time;
//		$massage->send_type = 4;
//		$massage->send_id 	= $squad_id;
//		$massage->template_id = 1;
//		//$massage->receive_type = 2;
//		//$massage->send_method = 1;
//
//		$massage->save();
//		$massage->users(2,$squad_id,$massage->id);
        return array('status'=>1,'info'=>'插入消息数据库成功');
    }
}
