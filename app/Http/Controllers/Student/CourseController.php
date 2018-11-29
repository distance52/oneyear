<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Administrator\Resource\InfoController;
use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use App\Models\Teaching\Plan;
use App\Models\Teaching\Node;
use App\Models\Teaching\Cell;
use App\Models\Teaching\Module;
use App\Models\NodeSquad;
use App\Models\NodeQa;
use Illuminate\Routing\UrlGenerator;
use App\Http\Controllers\Api\Student\OnlineQaController;
use App\Models\StudentPoint;
use App\Models\Student;
use App\Models\SquadStruct;
use App\Models\Group;
use App\Models\Teaching\PlanStruct;
use App\Models\Squad;
use App\Models\PlanImGroup;
use DB;
use GatewayClient\Gateway as Gateway;
use Illuminate\Support\Facades\Redis as Redis;
use App\Models\Info;
use App\Models\File;

// 课程系列
class CourseController extends BaseController
{
    //班级首页跳转
    public function getIndex($squad_id) {
        $oUser = \Auth::user();
        $oStudent = Student::where('user_id',$oUser->id)->first();
        $squads =SquadStruct::where('type',1)->where('struct_id',$oStudent->id)->pluck('squad_id')->toArray();
        if(!in_array($squad_id,$squads)){
            return redirect("/");
        }

        $plan_id = Squad::whereId($squad_id)->value('plan_id');
//        $oPlanStruct=PlanStruct::where('plan_id',$plan_id)->where('node_ord',1)->first();
        //获取模块单元第一个环节，该环节还必须为type=0或1类型
//        $nodes = PlanStruct::where('plan_id',$plan_id)->pluck('node_id')->toArray();
        $nodes = Plan::find($plan_id)->nodeList();
        $node_id = Node::whereIn('id',$nodes)->where('type','<',2)->value('id');
        $planStruct_id = PlanStruct::where('plan_id',$plan_id)->where('node_id',$node_id)->value('id');
//        $node_id = $node_id ?? 0;
        $planStruct_id = $planStruct_id ?? 0;
//        return redirect("/course/study/{$squad_id}/{$planStruct_id}/{$node_id}");
        return redirect("/course/study/{$squad_id}/{$planStruct_id}");
    }

    //班级模块跳转
    public function getStudy($squad_id, $planStruct_id) {
        $oUser = \Auth::user();
        $oStudent = Student::where('user_id',$oUser->id)->first();
        $squads =SquadStruct::where('type',1)->where('struct_id',$oStudent->id)->pluck('squad_id')->toArray();
        if(!in_array($squad_id,$squads)){
            return redirect("/");
        }
        //两种获取方式，第一种是通过planid 直接能获取到有课件的环节（如果没有环节的话该怎么处理？）
        //第二种是通过planStruct_id获取到默认的第一个环节，但不一定是有课件的，现在用的这种。
//        $plan_id = Squad::whereId($squad_id)->value('plan_id');
//        $nodes = Plan::find($plan_id)->nodeList();
//
//        $node_id = Node::whereIn('id',$nodes)->where('type','<',2)->value('id');

        $oPlanStruct = PlanStruct::whereId($planStruct_id)->first();

        return redirect("/course/study/{$squad_id}/{$planStruct_id}/{$oPlanStruct['node_id']}");
//        $nodes = PlanStruct::whereId($planStruct_id)->pluck('node_id')->toArray();
//        $node_id = Node::whereIn('id',$nodes)->where('type','<',2)->first();
//        return redirect("/course/study/{$squad_id}/{$planStruct_id}/{$node_id}");
    }

    //跳转至详细的环节
    public function classV2($squad_id, $planStruct_id,$node_id){
        $oUser = \Auth::user();
//        dd($this->student_id);
        $obj = SquadStruct::where('squad_id',$squad_id)->where('struct_id',$this->student_id)->where('type',1)->first();
        if (!$obj){
            return redirect("/");
        }
        $oPlanStruct = PlanStruct::find($planStruct_id);
        if(!$oPlanStruct){
            return redirect("/");
        }
        $nodes = $oPlanStruct->getNodes();
//        $node_id = $oPlanStruct->node_id;
        $node = Node::whereId($node_id)->first();
        //面包屑
        $bread = [];
        $bread['cell'] = $oPlanStruct->cell_id ? Cell::whereId($oPlanStruct->cell_id)->value('name') :'';
        $bread['module'] = $oPlanStruct->module_id ? Module::whereId($oPlanStruct->module_id)->value('name') :'';
//        $bread['node'] = isset($node->id )? $node->name :'';
        //资源
        $src_url = $src_html = $qnpic ='';
        $other_type = 0;
        if(isset($node->info_id) && $node->info_id){
            $oInfo = Info::whereId($node->info_id)->first();
            if($oInfo->url && $oInfo->type != 3) {
                $disk = \Storage::disk('qiniu');

//                $info = new InfoController();
                $this->preview_html($oInfo,$src_url , $src_html,$other_type , $squad_id);
//                $res = $disk->getDriver()->persistentStatus($oInfo->qnlasting);//解锁正式

//                $qnpic = $res['0']['items']['0']['keys']['0'];

//                $qnpic = $qnpic.'.png';

//                $picture = $disk->getDriver()->downloadUrl($qnpic);
//                $picture = substr($picture,0,-4);

//                if(!$other_type && !$src_html && $src_url) {
//                    // 这个是针对office的
//                    return redirect($src_url);
//                }
            }
        }
        //环节
        foreach($nodes as &$node) {
            if(isset($node['obj']) && $node['obj']) {
                if($node['obj']->info_id && $node['obj']->info  && $node['obj']->type >= 0 && $node['obj']->type < 2) {
                    $node['type_url'] = "/course/study/{$squad_id}/{$planStruct_id}/{$node['obj']->id}";
                }elseif($node['obj']->exampaper && $node['obj']->type >= 0 && $node['obj']->type < 2) {
                    $node['type_url'] = "/course/study/{$squad_id}/{$planStruct_id}/{$node['obj']->id}";
                }
                if($node['obj']->type > 0 && isset($node['type_url'])) {
                    switch ($node['obj']->type) {
                        case 1:$node['type_name'] = $node['obj']->name;break;
                        /*case 2:$node['type_name'] = '作业';break;
                        case 3:$node['type_name'] = '评分';break;
                        case 4:$node['type_name'] = '路演';break;*/
                        default:break;
                    }
                }
                if(!$node['obj']->type && $node['obj']->info_id && $node['obj']->info && isset($node['type_url'])) {
                    $node['type_name'] = $node['obj']->name;
                }
            }
            // !isset($node['type_url']) && unset($node);
        }
        //目录
        $oPlan = Plan::find($oPlanStruct->plan_id);
        $structs = $oPlan->detailStructV2();
        $cell_id =  $oPlanStruct->cell_id;
        //详情
        $oSquad = Squad::find($squad_id);
        $oTeacher = $oSquad->teacher;
        $oSchool = $oUser->school;
        $oSchool->score_rank && $oSchool->score_rank=json_decode($oSchool->score_rank, true);
        $student_count = SquadStruct::where('squad_id',$oSquad->id)->count();
        $topic_group_count = Group::where('type','1')->where('squad_id',$oSquad->id)->count();
        $project_group_count = Group::where('type','0')->where('squad_id',$oSquad->id)->count();
        return View('default.studentPlat.course.index',compact('squad_id','planStruct_id','nodes','structs','cell_id','oSquad','oTeacher','student_count','topic_group_count','project_group_count','oSchool','bread','src_html','src_url','other_type'));
    }

    public function menuList($squad_id){
        return View('default.studentPlat.my.mycourse',compact('squad_id'));
    }

    public function history($group_id){http://student.v3.cnczxy.com/course/study/203/191772/125098#item2mobile
//        $master_id = PlanImGroup::whereId($group_id)->value('master_id');
//        if($master_id != \Auth::user()->id){
//            return response()->json(array('state'=>false , 'msg'=>'这不是你的班级哦','data'=>null));
//        }

        $current_page = \Request::input('page') ?? 1;
        $rec_key = 'chat_group_rec:'.$group_id;//历史聊天记录
        $len = Redis::llen($rec_key);

        if (!$len){
            return response()->json(array('state'=>false , 'msg'=>'没有历史聊天记录','data'=>null,'pager'=>array('total'=>0 , 'pages'=>0 , 'page' => 0)));
        }

        $reslut = [];
        $reslut['state']  = true;
        $reslut['msg']  = null;
        if ($current_page>1){
//            $chat_rec = Redis::lrange($rec_key , ($current_page*10-5) , ($current_page*10+4));
            $chat_rec = Redis::lrange($rec_key , ($current_page-1)*5 , ($current_page*5-1));
        }else{
            $chat_rec = Redis::lrange($rec_key , 0 , 4);
        }
//        dd($current_page);
        if (count($chat_rec)){
//            $chats = array_reverse($chat_rec);
//            $html = '';  //设置返回html内容
            $arr = array();
            foreach ($chat_rec as $v){
                $data =  json_decode($v ,true);
////                $html .= "<div class=\"item feed-item\">";
////                $html .= "<div class=\"item-main\">";
////                $html .= "<small class=\"time\">".date('Y-m-d H:i:s', $data['timestamp'])."</small>";
////                $html .= "<div class=\"text\">{$data['user_name']}：";
////                $html .=  $data['attach'] ? "{$data['message']}<p><a href=\"http://static.cnczxy.com/{$data['attach']}\" target=\"_blank\"><img src=\"http://static.cnczxy.com/{$data['attach']}@1e_1c_0o_0l_100h_100w_90q.src\" /></img></a></p>":"{$data['message']}";
////                $html .= "</div></div></div>";
                array_push($arr, $data);
            }
            $reslut['data'] =  $arr;
            $pages = ceil($len/5);
            $reslut['pager'] =  array('total'=>$len , 'pages'=>$pages , 'page' => $current_page);
            return response()->json($reslut);
        }else{
            return response()->json(array('state'=>false , 'msg'=>'找不到更多记录了','data'=>null));
        }
    }

    //原版学生首页
    public function getIndex1($squad_id) {

        $oUser = \Auth::user();
        $oStudent = Student::where('user_id',$oUser->id)->first();
        $squads =SquadStruct::where('type',1)->where('struct_id',$oStudent->id)->pluck('squad_id')->toArray();
        if(in_array($squad_id,$squads)){
            $oUser->load('student.squad.plan');
            $qa_html = '';
            $oSquad = Squad::find($squad_id);
            $plan_id = $oSquad->plan? $oSquad->plan->id: 0;
            $plan_id && $oPlan = Plan::find($plan_id);
            $oTeacher = $oSquad->teacher;
            $oSchool = $oUser->school;
            $oSchool->score_rank && $oSchool->score_rank=json_decode($oSchool->score_rank, true);
            $student_count = SquadStruct::where('squad_id',$oSquad->id)->count();
            $topic_group_count = Group::where('type','1')->where('squad_id',$oSquad->id)->count();
            $project_group_count = Group::where('type','0')->where('squad_id',$oSquad->id)->count();
            // 已完成的环节
            // 已完成的环节
            $oModuleSquad = NodeSquad::where('squad_id', $oSquad->id)->where('type',1)->where('module_id','>',0)->pluck('module_id')->toArray();

            if($plan_id && $oPlan) {
                // 结构
                $structs = $oPlan->detailStructV2();
                if (view()->exists(session('mode').'.studentPlat.course.index')){
                    return View(session('mode').'.studentPlat.course.index',compact('structs','oPlan','oSquad','oTeacher','oModuleSquad','oSchool','student_count','topic_group_count','project_group_count','squad_id'));
                }else{
                    return View('default.studentPlat.course.index',compact('structs','oPlan','oSquad','oTeacher','oModuleSquad','oSchool','student_count','topic_group_count','project_group_count','squad_id'));
                }
            }else{
                return redirect('error')->with(['msg'=>'暂无可用课程', 'href'=>env('APP_PRO').'student.'.env('APP_SITE')]);
            }
        }else{
            return redirect('error')->with(['msg'=>'非法操作', 'href'=>app(UrlGenerator::class)->previous()]);
        }
    }

    //原版学生在线学习
    public function getStudy1($squad_id, $planStruct_id) {
        //
        $oUser = \Auth::user();
        $oStudent = Student::where('user_id',$oUser->id)->first();

        $squads =SquadStruct::where('type',1)->where('struct_id',$oStudent->id)->pluck('squad_id')->toArray();
        if(in_array($squad_id,$squads)){
            $oPlanStruct = PlanStruct::find($planStruct_id);
            if(!$oPlanStruct) {
                return redirect('error')->with(['msg'=>'参数错误，资源不存在', 'href'=>app(UrlGenerator::class)->previous()]);
            }

            $plan_id = $oPlanStruct->plan_id;
            //
            $oCell = Cell::find($oPlanStruct->cell_id);
            $oNode = Node::find($oPlanStruct->node_id);
            $oModule = Module::find($oPlanStruct->module_id);
            $oSquad = Squad::whereId($squad_id)->first();
            //
            $nodes = $oPlanStruct->getNodes();

            //$nodes = $oPlanStruct->getNodes_stu();
            $node_count = count($nodes);
            //
            foreach($nodes as &$node) {
                if(isset($node['obj']) && $node['obj']) {
                    if($node['obj']->info_id && $node['obj']->info  && $node['obj']->type >= 0 && $node['obj']->type < 2) {
                        $node['type_url'] = "/preview/info/".$node['obj']->info->sign."/".$squad_id;
                    }elseif($node['obj']->exampaper && $node['obj']->type >= 0 && $node['obj']->type < 2) {
                        $node['type_url'] = "/resource/exampaper/view/".$node['obj']->exampaper_id;
                    }
                    if($node['obj']->type > 0 && isset($node['type_url'])) {
                        switch ($node['obj']->type) {
                            case 1:
                                $node['type_name'] = '预习';
                                break;
                            /*case 2:
                                $node['type_name'] = '作业';
                                break;
                            case 3:
                                $node['type_name'] = '评分';
                                break;
                            case 4:
                                $node['type_name'] = '路演';
                                break;*/
                            default:
                                break;
                        }
                    }
                    if(!$node['obj']->type && $node['obj']->info_id && $node['obj']->info && isset($node['type_url'])) {
                        $node['type_name'] = '课件';
                    }
                }
                // !isset($node['type_url']) && unset($node);
            }
            // 返回下一个的连接
            $next_id = $pre_id = 0;
            $next_url = $pre_url = '';
            $oModuleList = $oPlanStruct->getAllIdsByModules();
            if($oModuleList) {
                foreach($oModuleList as $key=>$m_id) {
                    if($m_id == $planStruct_id) {
                        if(isset($oModuleList[$key+1]) && $oModuleList[$key+1]) {
                            $next_id = $oModuleList[$key+1];
                            $next_url = url('course/study',[$squad_id, $next_id]);
                        }
                        if(($key-1)>=0 && isset($oModuleList[$key-1])) {
                            $pre_id = $oModuleList[$key-1];
                            $pre_url = url('course/study',[$squad_id, $pre_id]);
                        }
                        break;
                    }
                }
            }
            // 调用互动代码
//            $oQa = new OnlineQaController();
//            $qa_html = $oQa->run($oPlanStruct->id, 1);
            //
            if (view()->exists(session('mode').'.studentPlat.study-v2')){
                return View(session('mode').'.studentPlat.study-v2', compact('pre_url','node_count','next_url','oCell','oNode','oModule','oPlanStruct','squad_id','nodes','planStruct_id'));
            }else{
                return View('default.studentPlat.study', compact('pre_url','node_count','next_url','oCell','oNode','oModule','oPlanStruct','squad_id','qa_html','nodes'));
            }
        }else{
            return redirect('error')->with(['msg'=>'非法操作', 'href'=>app(UrlGenerator::class)->previous()]);
        }
    }



    public function preview_html($oInfo, &$src_url, &$src_html, &$other_type=0,$squad_id,$qnpic='') {
        $disk = \Storage::disk('qiniu');
        $oFile = File::find($oInfo->file_id);
        // 积分
        $oStudentPoint = new StudentPoint();
        $sign = 'r-f'.$oInfo->file_id.'-u'.\Auth::user()->id;
        // 1-视频 2-音频 3-图文 4-flash 5-office 6-其它
        // 3 已经不在这里了
        if($oInfo->type == 3) {
            $oStudentPoint->setPoints('pic_word', $sign,$squad_id);
            //$src_html = Storage::disk('oss')->get($oInfo->src);
            $src_html = $oInfo->content;

        } else {
            if($oFile) {
                switch ($oInfo->type) {
                    case 1:
                        $oStudentPoint->setPoints('video', $sign,$squad_id);
                        //$src_url = $disk->getDriver()->downloadUrl($oFile->src,'custom');
                        //$src_url = json_decode(json_encode($src_url), true);
                        $other_type = 1;
                        $src_url = config('alioss.static_domain').'/'.$oFile->src;
                        /*if(empty($oInfo->qnlasting)){
                            $file1 = $oFile->src;//数据库暂时在七牛云中找不到对应的文件
                            $file = "video/db9142cf070a3c18fccdedc3a1a3fc59.mp4";
                            echo '||||'.$file.'===';
                            echo $file1;
                            $res = $disk->getDriver()->persistentFop($file,'vsample/png/ss/0/t/6/s/480x360/pattern/dmZyYW1lLSQoY291bnQp');
                            var_dump($res);die;
                            $qnlasting['qnlasting'] = $res;//解锁正式
                            //$qnlasting['qnlasting'] = 'z0.596ebd7845a2650c990b13b0';
                            Info::where('id',$oInfo->id)->update($qnlasting);
                            echo 'empty';
                        }*/

//                        $src_url =base64_encode($src_url);
                        //$src_html = "<div id='J_prismPlayer' class='prism-player' data-source='".$src_url."'></div>";
                        $src_html = "<video width=\"100%\" src=\"$src_url\" autobuffer=\"\" autoloop=\"\" loop=\"\" controls=\"\" ></video>";
                        break;
                    case 2:
                        $oStudentPoint->setPoints('video', $sign,$squad_id);
                        $other_type = 2;
                        $src_url = $disk->getDriver()->downloadUrl($oFile->src,'custom'); //调用七牛的数据文件
                        $src_url = json_decode(json_encode($src_url), true);
                        //$src_url = config('alioss.static_domain').'/'.$oFile->src;
                        $src_html = "<div id='J_prismPlayer' class='prism-player' data-source='".$src_url."'></div>";
                        //$src_html = $src_url;
                        break;
                    case 5:
                        $oStudentPoint->setPoints('office', $sign,$squad_id);
                        $other_type = 5;
                        $src_url = 'https://officeweb365.com/o/?i=9633&ssl=1&furl='.'https://static.cnczxy.com'.'/'.$oFile->src;
                        $src_html = "<iframe width=\"100%\" height=\"100%\" class=\"iframe-imginfo\" scrolling=\"no\" frameborder=\"0\" src=\"{$src_url}\"></iframe>";
                        break;
                    default:
                        $src_url = config('alioss.static_domain').'/'.$oFile->src;
                        $other_type = 1;
                        break;
                }
                // return view('resource.file.preview',compact('src_html','oFile','src_url','other_type'));
            }
        }
    }



    /**
     *
     * TODO 课堂在线互动，学生端聊天
     * @param  content , group_id , attach
     * 保存附件只aliyun OSS
     * 将聊天内容发送至各个客户端
     * 将聊天记录保存至Redis
     * @notice redis记录分为两种，一种是聊天历史消息记录，保存最近D天的N条记录，一种是队列保存聊天记录，定时存入数据库
     * @return Json
     *
     **/

    public function postStudy(Request $request,$squad_id, $planStruct_id) {

        $data = $request->only('content','group','image');

        //保存上传附件
        if (\Request::hasFile('image')) {

            $file = $request->file('image');
            $allowedExts = array("gif", "jpeg", "jpg", "png", "bmp","GIF", "JPEG", "JPG", "PNG", "BMP","RAW","raw");//支持文件类型的扩展名
            $attcht_type = $file->getClientOriginalExtension();
            $attcht_size = $file->getClientSize();

            if (in_array($attcht_type, $allowedExts)) {
                $file_name = 'images/' . uniqid().$file->getClientOriginalName();
                \Storage::disk('oss')->put($file_name, file_get_contents($file->getRealPath()));
                if(\Storage::disk('oss')->exists($file_name)) {
                    //                $exp = new \DateTime(date("Y-m-d H:i:s",strtotime("+3 year")));
                    //                $url = \AliyunOSS::getUrl($file_name,$exp, $bucket = config('filesystems.disks.oss.bucket'));
                    //                $imgs = [
                    //                    'src'=>$file_name,
                    //                    'url'=>$url
                    //                ];
                    $imgs = $file_name;
                }
            }else{

                $msg = ['msg'=> '图片上传失败，请重新上传'];
                return response()->json($msg)->setStatusCode(400);
            }
        }else{
            $imgs = null;
            $attcht_type = null;
        }

        $group_id =  $data['group'];
        $oUser = \Auth::user();
        if(empty($data['content'])){
            $content = '';
        }else{
            $content = $data['content'];
        }

        //设置消息
        $message = array(
            'type'     => 'send_to_group',
            'to'    => $group_id ,
            'message'  => $content ,
            'from'  => $oUser->id,
            'user_name'=> $oUser->name ,
            'avatar'=> getAvatar($oUser->avatar) ,
            'timestamp'=> time(),
            'attach'    => $imgs ,
            'attach_type' => $attcht_type ,
        );

        //发送至客户端
        Gateway::$registerAddress = '127.0.0.1:1236';
        Gateway::sendToGroup($group_id, json_encode($message));
        //聊天消息保存至redis
//        $rec_key = 'chat_group_rec:'.$group_id;//历史聊天记录
        $rec_key = 'chat_group_rec:'.$group_id;//历史聊天记录
        $queue_key = 'chat_group_queue:'.$group_id;//存入队列
        $value = json_encode($message);
        Redis::lpush($rec_key,$value);
        Redis::lpush($queue_key,$value);
        //
        Redis::expire($rec_key , 60*60*24*180);
//        $count = Redis::llen($rec_key);

        return response()->json('ok')->setStatusCode(200);
//        $oUser = \Auth::user();
//        $oNodeQa = new NodeQa();
//        // dd(\Request::all());
//        $oNodeQa->squad_id = $squad_id;
//        $oNodeQa->plan_id = \Request::input('plan_id',0);
//        $oNodeQa->cell_id = \Request::input('cell_id',0);
//        $oNodeQa->module_id = \Request::input('module_id',0);
//        $oNodeQa->node_id = \Request::input('node_id',0);
//        $oNodeQa->content = \Request::input('content','');
//        $oNodeQa->type = 1;
//        $oNodeQa->user_id = $oUser->id;
//        $oNodeQa->ip = \Request::getClientIp();
//        $oNodeQa->parent_id = \Request::input('parent_id',0);
//        // 积分
//        $oStudentPoint = new StudentPoint();
//        $sign ='s'. $oNodeQa->squad_id.'-n'.$oNodeQa->node_id.'-u'.\Auth::user()->id.'-t'.$oNodeQa->type.'-p'.$oNodeQa->parent_id;
//        // $oStudentPoint->setPoints('interact',$sign,$oNodeQa->squad_id);
//        // imgs
//        // dd(\Request::file('imgs')->first()->isValid());
//        $imgs = [];
//        if (\Request::hasFile('imgs')) {
//            foreach (\Request::file('imgs') as $file) {
//                if ($file->isValid()){
//
//                   $file_name = time().str_random(6).$file->getClientOriginalName();
//                   \Storage::disk('oss')->put($file_name, file_get_contents($file->getRealPath()));
//                   if(\Storage::disk('oss')->exists($file_name)) {
//                        $exp = new \DateTime(date("Y-m-d H:i:s",strtotime("+3 year")));
//                        $url = \AliyunOSS::getUrl($file_name,$exp, $bucket = config('filesystems.disks.oss.bucket'));
//                        $imgs[] = [
//                            'src'=>$file_name,
//                            'url'=>$url
//                        ];
//                   }
//                } else {
//                   return back()->withInput()->withErrors([
//                       'msg' => '图片上传失败',
//                   ]);
//                }
//            }
//            $oNodeQa->imgs = $imgs;
//       }
//       $oNodeQa->save();
//       //
//       $back_url = app(UrlGenerator::class)->previous();
//       return redirect($back_url);
    }

    /**
     *
     * @TODO 课堂在线互动，学生用户uid绑定至群组
     * @param  squad_id
     * @param client_id
     * @param planStruct_id
     * @return json
     *
     **/

    public function bind(Request $request){
        $data = $request->only('squad_id','planStruct_id','client_id');
        $uid      = \Auth::user()->id;
        $client_id = $data['client_id'];
        //通过module模块来获取group_id
        $module_id = PlanStruct::whereId($data['planStruct_id'])->value('module_id');
        $group = PlanImGroup::where('squad_id', $data['squad_id'])->where('plan_id',$module_id)->first();
        if(!$group){
            return response()->json([
                'msg' => '课堂互动暂未开启，请联系教师。',
                'state' => false
            ]);
        }
        $group_id = $group->id;

        Gateway::$registerAddress = '127.0.0.1:1236';
        Gateway::bindUid($client_id, $uid);
        Gateway::joinGroup($client_id, $group_id);
        $msg = ['squad_id'=>$data['squad_id'] , 'planStruct_id'=>$data['planStruct_id'] , 'client_id'=>$data['client_id'],'group'=>$group_id];
        return response()->json($msg)->setStatusCode(200);

    }

    //撤销学生互动提交不满意的答案
    public function Del($id,$user,$userId){
        $oUser = \Auth::user();
        if($oUser->name==$user){
            $user = DB::table("node_qas")->where('id',$id)->delete();
            $back_url = app(UrlGenerator::class)->previous();
            return redirect($back_url);
        }else{
            // return back()->with("msg","<script>alert('无权修改他人信息!');</script>");
            $back_url = app(UrlGenerator::class)->previous();
            return redirect($back_url)->withErrors(['msg' => '权限不足']);
        }
    }

    //扫一扫签到
    public function Scan(){
        // $oUser = \Auth::user();
        $access = get_access_token();
        $jsaip_url = "https://api.weixin.qq.com/cgi-bin/ticket/getticket?access_token=".$access."&type=jsapi";
        $jsapi  =json_decode(file_get_contents($jsaip_url), true);
        $jsapiTicket = $jsapi['ticket'];
        $timestamp = time();
        $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
        $nonceStr = "";
        for ($i = 0; $i < 16; $i++)
        {
            $nonceStr .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
        }
        $url = env("APP_PRO")."$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
        $string = "jsapi_ticket=$jsapiTicket&noncestr=$nonceStr&timestamp=$timestamp&url=$url";
        $signature = sha1($string);
        $oBind = config('login_bind');
        $signPackage = array(
            "appId"     => $oBind['weixinmob_key'],
            "nonceStr"  => $nonceStr,
            "timestamp" => $timestamp,
            "url"       => $url,
            "signature" => $signature,
            "rawString" => $string
        );

        $oUser = \Auth::user();
        $oStudent = Student::where('user_id',$oUser->id)->first();
        $oObjs = \DB::table('sign_log')->where('student_id',$oStudent->id)->orderBy('time','desc')->get();
        return View('default.studentPlat.scan.index',compact('signPackage','oObjs'));
    }

}   
