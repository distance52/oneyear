<?php

namespace App\Http\Controllers\Api\Teacher;

use Guzzle\Service\Resource\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use App\Models\Teaching\Plan;
use App\Models\Teaching\Cell;
use App\Models\Teaching\Module;
use App\Models\Teaching\Node;
use App\Models\Squad;
use App\Models\NodeSquad;
use App\Models\Notify;
use App\Models\Score;
use App\Models\Student;
use App\Models\GroupStudent;
use App\Models\StudentPoint;
use App\Models\Teaching\PlanStruct;
use App\Models\Exampaper;
use App\Models\Info;
use Illuminate\Support\Facades\DB;
use App\Models\Group;
use App\Models\SquadStruct;

use App\Http\Controllers\Api\WechatNotifyController;

class SquadController extends Controller
{
    private function _getPlanStruct($id){
        $key='plan-'.$id;
        $plan_info = Cache::store('file')->remember($key, 1, function () use($id) {
            $oObj = Plan::whereId($id)->first();
            $struct = $oObj->struct;
            $cell_ids=array();
            $module_ids=array();
            $node_ids=array();
            $cell_arr=array();
            $module_arr=array();
            $node_arr=array();
            $module_parent=array();
            $node_parent=array();
            if(empty($struct)){
                return array('cell'=>array(),'module'=>array(),'node'=>array());
            }
            foreach($struct as $val){
                array_push($cell_ids,$val['id']);
                array_push($cell_arr,$val['id']);
                if(!isset($val['lists'])){
                    continue;
                }
                foreach($val['lists'] as $v){
                    array_push($module_ids,$v['id']);
                    $module_arr[$val['id']][]=$v['id'];
                    $module_parent[$v['id']]['parentId']=$val['id'];
                    if(!isset($v['lists'])){
                        continue;
                    }
                    foreach ($v['lists'] as $_v) {
                        array_push($node_ids, $_v['id']);
                        $node_arr[$v['id']][] = $_v['id'];
                        $node_parent[$_v['id']]['parentId'] = $v['id'];
                    }
                }
            }
            $cell_info=Cell::whereIn('id',$cell_ids)->get(['name','id'])->toArray();
            $module_info=Module::whereIn('id',$module_ids)->get(['name','id'])->toArray();
            $node_info=Node::whereIn('id',$node_ids)->get(['name','id'])->toArray();
            foreach($module_info as $key=>&$val){
                $val['parentId']=$module_parent[$val['id']]['parentId'];
            }
            foreach($node_info as $key=>&$val){
                $val['parentId']=$node_parent[$val['id']]['parentId'];
            }
            return array('cell'=>$cell_info,'module'=>$module_info,'node'=>$node_info);
        });
        return $plan_info;
    }

    /**
     * 根据班级获取单元列表
     */
    public function getCellListBySquad($squad_id)
    {
        $squadInfo = Squad::where('id', $squad_id)->first();
        $plan_info = $this->_getPlanStruct($squadInfo->plan_id);
        $arr = array(
            'plan_id' => $squadInfo->plan_id,
            'cell' => $plan_info['cell']
        );
        return response()->json($arr);
    }

    /**
     * 根据方案id获取单元列表
     * @param $plan_id
     * @return \Illuminate\Http\JsonResponse
     */
    public function getCellListByPlan($plan_id)
    {
        $plan_info = $this->_getPlanStruct($plan_id);
        return response()->json($plan_info['cell']);
    }

    /*
     *  获取某单元下的模块列表
     */
    public function getModuleListByCell($plan_id,$cell_id){
        $plan_info=$this->_getPlanStruct($plan_id);
        $module_arr=array();
        foreach($plan_info['module'] as &$val){
            if($val['parentId']==$cell_id){
                $module_arr[]=$val;
            }
        }
        return response()->json($module_arr);
    }

    /**
     *  获取某模块下的环节列表
     */
    public function getNodeListByModule($plan_id,$module_id){
        $plan_info=$this->_getPlanStruct($plan_id);
        $module_arr=array();
        foreach($plan_info['node'] as &$val){
            if($val['parentId']==$module_id){
                $module_arr[]=$val;
            }
        }
        return response()->json($module_arr);
    }

    /**
     * 三级联动获取方案下的环节，模块单元
     * @param $id
     * @return \Illuminate\Http\JsonResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function getAll($id){
        if($id) {
            $key='plan-'.$id;
            $plan_info = Cache::store('file')->remember($key, 1, function () use($id) {
                $oObj = Plan::whereId($id)->first();
                $struct = $oObj->struct;
                $cell_ids=array();
                $module_ids=array();
                $node_ids=array();
                $cell_arr=array();
                $module_arr=array();
                $node_arr=array();
                $module_parent=array();
                $node_parent=array();
                foreach($struct as $val){
                    array_push($cell_ids,$val['id']);
                    array_push($cell_arr,$val['id']);
                    foreach($val['lists'] as $v){
                        array_push($module_ids,$v['id']);
                        $module_arr[$val['id']][]=$v['id'];
                        $module_parent[$v['id']]['parentId']=$val['id'];
                        foreach($v['lists'] as $_v){
                            array_push($node_ids,$_v['id']);
                            $node_arr[$v['id']][]=$_v['id'];
                            $node_parent[$_v['id']]['parentId']=$v['id'];
                        }
                    }
                }
                $cell_info=Cell::whereIn('id',$cell_ids)->get(['name','id'])->toArray();
                $module_info=Module::whereIn('id',$module_ids)->get(['name','id'])->toArray();
                $node_info=Node::whereIn('id',$node_ids)->get(['name','id'])->toArray();
                foreach($module_info as $key=>&$val){
                    $val['parentId']=$module_parent[$val['id']]['parentId'];
                }
                foreach($node_info as $key=>&$val){
                    $val['parentId']=$node_parent[$val['id']]['parentId'];
                }
                return array('cell'=>$cell_info,'module'=>$module_info,'node'=>$node_info);
            });
            return response()->json($plan_info);
        } else {
            $msg = [
                "custom-msg"=> ["参数错误，非法操作"],
            ];
            return response()->json($msg)->setStatusCode(422);
        }
    }

    // 新版的
    public function moduleComplete($squad_id, $planStruct_id) {

        $oUser = \Auth::user();
        $oSquad = Squad::find($squad_id);
        $oPlanStruct = PlanStruct::find($planStruct_id);
        $oModule = Module::find($oPlanStruct->module_id);
        if($oModule && $oSquad) {
            $oNodeSquad = NodeSquad::where('module_id', $oModule->id)->where('squad_id', $squad_id)->where('type',1)->first();
            if(!$oNodeSquad) {
                $oNodeSquad = new NodeSquad;
                $oNodeSquad->module_id = $oPlanStruct->module_id;
                $oNodeSquad->squad_id = $squad_id;
                $oNodeSquad->type = 1;
                $oNodeSquad->save();
            }

            // 返回下一个的连接
            $next_id = 0;
            //
            $oModuleList = $oPlanStruct->getAllIdsByModules();
            \Debugbar::info($oModuleList);
            if($oModuleList) {
                foreach($oModuleList as $key=>$m_id) {
                    if($m_id == $planStruct_id) {
                        $next_id = isset($oModuleList[$key+1])? $oModuleList[$key+1]: $planStruct_id;
                        break;
                    }
                }
            }
            !$next_id && $next_id = $planStruct_id;
            $next_url = \Request::root().'/teachone/squad/console/'.$squad_id.'/'.$next_id;
            //
            $msg = [
                "custom-url"=> [$next_url],
            ];
            return response()->json($msg);
        }

        $msg = [
            "custom-msg"=> ["参数错误，非法操作"],
        ];
        return response()->json($msg)->setStatusCode(422);
    }

    // ajax 设置作业完成时间+消息提示+路演

    public function nodeSetPaper($squad_id, $node_id) {
        $oUser = \Auth::user();
        $teacher_id = $oUser->teacher->id;
        $start_date = date("Y-m-d H:i:s");
        $end_date = \Request::input('end_time','');
        //
        if($end_date) {
            $oNode = Node::find($node_id);
            $oSquad = Squad::find($squad_id);
            if($oNode && $oSquad) {
                //  nodes  2-作业 3-评分 4-路演
                // score  2-作业  4- 评分 5-路演
                switch ($oNode->type) {
                    case 2:
                        $type= 2;
                        break;
                    case 3:
                        $type= 4;
                        break;
                    case 4:
                        $type= 5;
                        break;
                    default:
                        $type= 0;
                        break;
                }
                if($type == 2) {
                    $oNodeSquad = NodeSquad::where('node_id', $node_id)->where('squad_id', $squad_id)->where('type',$type)->first();
                }
                if(!$oNodeSquad) {
                    $oNodeSquad = new NodeSquad;
                    $oNodeSquad->node_id = $node_id;
                    $oNodeSquad->squad_id = $squad_id;
                    $oNodeSquad->type = $type;
                    $oNodeSquad->save();
                }
                $oNodeSquad = NodeSquad::where('node_id', $node_id)->where('squad_id', $squad_id)->where('type', $type)->where('created_at', $oNodeSquad->created_at)->orderBy('id','desc')->first();
                $student_ids = \DB::table('squad_structs')->where('squad_id',$squad_id)->where('type',1)->pluck("struct_id");
                Student::whereIn('id', $student_ids)->get()->each(function($oStudent) use ($node_id, $squad_id, $start_date, $end_date, $oSquad, $oNode, $type, $oNodeSquad,$teacher_id) {
                    if($type == 2) {
                        $oScore = Score
                            ::where('node_id', $node_id)
                            ->where('student_id', $oStudent->id)
                            ->first();
                    }

                    if(!$oScore) {
                        $oScore = new Score;
                        $oScore->type = $oNodeSquad->type;
                        $oScore->num = $oNodeSquad->id;
                        $oScore->node_id = $node_id;
                        $oScore->squad_id = $squad_id;
                        $oScore->student_id = $oStudent->id;
                        $oScore->exampaper_id = $oNode->exampaper_id;
                        $oScore->group_id = \Request::input('group_id',0);
                        $oScore->plan_id = $oSquad->plan_id;
                        $oScore->start_time = $start_date;
                        $oScore->end_time = $end_date;
                        $oScore->teacher_id = $teacher_id;
                        $oScore->save();
                    } else {
                        $oScore->num = $oNodeSquad->id;
                        $oScore->start_time = $start_date;
                        $oScore->end_time = $end_date;
                        $oScore->teacher_id = $teacher_id;
                        $oScore->save();
                    }

                });
                $oCell = $oModule = '';
                $this->getCellModuleData($oSquad, $node_id, $oCell, $oModule);
                // 获取作业的总分
                $oExampaper = Exampaper::whereId($oNode->exampaper_id)->with("questions")->first();
                $newscore = json_decode($oExampaper->scorenew,1);
                if(!empty($oExampaper->questions)) {
                    foreach($oExampaper->questions as $item) {
                        $oExampaper->totalitem += 1;
                        $oExampaper->totalscore += trim($newscore[$item->id]);
                    }
                } else {
                    $oExampaper->totalitem =0;
                    $oExampaper->totalscore = 0;
                }
                $url = route('student_examdo',["{$oNode->exampaper_id}","{$oNodeSquad->id}"]);
                // 执行发送通知
                WechatNotifyController::homework_publish(
                    $squad_id,
                    '',
                    '',
                    $url,
                    $start_date,
                    $oCell->name,
                    $oModule->name,
                    $start_date,
                    $end_date,
                    $oExampaper->totalscore.'分'
                );

                // dd($oNotify);
                $oNotify = Notify::where('user_id',$oUser->id)->orderBy('id','desc')->first();
                $oNotify->squads()->sync([$squad_id]);
                return response()->json(null);
            }
        }
        $msg = [
            "custom-msg"=> ["参数错误，非法操作"],
        ];
        return response()->json($msg)->setStatusCode(422);
    }

    // 预习发送
    public function nodeSetYuXi($squad_id, $node_id) {

        $oUser = \Auth::user();

        $oNode = Node::find($node_id);
        $oSquad = Squad::find($squad_id);
        if($oNode && $oSquad) {
            // $oNodeSquad = NodeSquad::where('node_id', $node_id)->where('squad_id', $squad_id)->where('type',3)->first();
            // if($oNodeSquad) {
            //     $msg = [
            //         "custom-msg"=> ["预习已经发送过了，不允许多次操作"],
            //     ];
            //     return response()->json($msg)->setStatusCode(422);
            // } else {
            $oNodeSquad = new NodeSquad;
            $oNodeSquad->node_id = $node_id;
            $oNodeSquad->squad_id = $squad_id;
            $oNodeSquad->type = 3;
            $oNodeSquad->save();

            $oCell = $oModule = '';
            $this->getCellModuleData($oSquad, $node_id, $oCell, $oModule);
            // 执行发送通知
            $start_time = date("Y-m-d H:i:s");
            $end_time = date("Y-m-d H:i:s",time()+60*45);
            $oInfo = Info::find($oNode->info_id);
            $oNode->info_id  = Info::find($oNode->info_id);
            if($oInfo) {
                $url = route('preview_info',["{$oInfo->sign}","{$squad_id}"]);
                WechatNotifyController::homework_yuxi(
                    $squad_id,
                    '',
                    '',
                    $url,
                    $start_time,
                    $oCell->name,
                    $oModule->name,
                    $start_time,
                    $end_time,
                    '10分'
                );
            }
            //发送预习后，直接完成该模块
            $planStruct_id = \Request::input('structId','');
            if ($planStruct_id){
                $this->moduleComplete($squad_id, $planStruct_id);
            }
            return response()->json(null);
            // }
        }

        $msg = [
            "custom-msg"=> ["参数错误，非法操作"],
        ];
        return response()->json($msg)->setStatusCode(422);
    }

    // 评分|路演
    public function nodeSetEvaluate($squad_id, $node_id) {
        $oUser = \Auth::user();
        $teacher_id = $oUser->teacher->id;
        $start_date = date("Y-m-d H:i:s");
        $end_date = date("Y-m-d H:i:s", strtotime($start_date)+60*45);
        $group_id = \Request::input('group_id',0);
        $oGroup = Group::find($group_id);
        $oNode = Node::find($node_id);
        $oSquad = Squad::find($squad_id);
//        \Log::info('nodeSetEvaluate:$group_id',['group_id' => $group_id]);
        $type = $oNode->type+1; // 4-评分  5-路演 这是针对scores表  nodes表里面3-评分 4-路演

        if($oNode && $oSquad) {
            // add
            $oNodeSquad = new NodeSquad;
            $oNodeSquad->node_id = $node_id;
            $oNodeSquad->squad_id = $squad_id;
            $oNodeSquad->type = $type;
            $oSquad->start_time=$start_date;
            $oSquad->end_time=$end_date;
            $oNodeSquad->save();

//            $oNodeSquad = NodeSquad::where('node_id', $node_id)->where('squad_id', $squad_id)->where('type', $type)->where('created_at', $oNodeSquad->created_at)->orderBy('id','desc')->first();

            $oGroupStudent = GroupStudent::where('group_id', $group_id)->pluck('student_id')->toArray();
            if(empty($oGroupStudent)){
                $msg = [
                    "custom-msg"=> "抱歉，该小组(编号：{$group_id})中暂时还没有学生，暂时无法发起评分。",
                ];
                return response()->json($msg);
            }

//            Student::where('squad_id', $squad_id)->get()
            SquadStruct::where('squad_id', $squad_id)->where('type',1)->get()
                ->each(function($oStudent) use ($node_id, $squad_id, $start_date, $end_date, $oSquad, $oNode, $oGroupStudent, $group_id, $oNodeSquad,$teacher_id)  {
                    if(!in_array($oStudent->id, $oGroupStudent)) {
                        //注释掉自己不给自己评价功能，因为发送消息是以班级为单位发送，会导致数据不匹配。
                        $oScore = new Score;
                        $oScore->type = $oNodeSquad->type;
                        $oScore->num = $oNodeSquad->id;
                        $oScore->node_id = $node_id;
                        $oScore->squad_id = $squad_id;
                        $oScore->student_id = $oStudent->struct_id;
                        $oScore->exampaper_id = $oNode->exampaper_id;
                        $oScore->group_id = $group_id;
                        $oScore->plan_id = $oSquad->plan_id;
                        $oScore->start_time = $start_date;
                        $oScore->end_time = $end_date;
                        $oScore->teacher_id = $teacher_id;
                        $oScore->save();
                    }
                });
            $oCell = $oModule = '';
            $this->getCellModuleData($oSquad, $node_id, $oCell, $oModule);
            // 获取作业的总分
            $oExampaper = Exampaper::whereId($oNode->exampaper_id)->with("questions")->first();

            $newscore = json_decode($oExampaper->scorenew,1);
            if(!empty($oExampaper->questions)) {
                foreach($oExampaper->questions as $item) {
                    $oExampaper->totalitem += 1;
                    $oExampaper->totalscore += trim($newscore[$item->id]);
                }
            } else {
                $oExampaper->totalitem =0;
                $oExampaper->totalscore = 0;
            }
            // 执行发送通知
            $url = route('student_examdo',["{$oNode->exampaper_id}","{$oNodeSquad->id}"]);
            //对班级评分推送提醒
            WechatNotifyController::ketang_pingfen(
                $squad_id,
                '',
                '',
                $url,
                $start_date,
                $oCell->name,
                $oModule->name,
                $start_date,
                $end_date,
                $oGroup->name
            );
            $oNotify = Notify::where('user_id',$oUser->id)->orderBy('id','desc')->first();
            $oNotify->squads()->sync([$squad_id]);

            //对群组进行推送提醒


            return response()->json(null);
        }

        $msg = [
            "custom-msg"=> ["参数错误，非法操作"],
        ];
        return response()->json($msg)->setStatusCode(422);
    }

    // 获取所有的项目组和专题组
    public function groups($squad_id) {
        $oSquad = Squad::where('id',$squad_id)->with('groups')->first();
        $groups = [];
        // dd($oSquad->groups);
        $oSquad->groups->each(function($group) use (&$groups) {
            $sql='select count(*) num,id from scores where group_id='.$group->id.' and type=4 group by num';
            $count=DB::select($sql);
            $times=count($count);
            $groups[$group->type][] = [
                'id'=>$group->id,
                'name'=>$group->name,
                'times'=>$times,
            ];
        });
        return response()->json($groups);
    }

    private function getCellModuleData($oSquad,$node_id,&$oCell,&$oModule) {
        $oCell = $oModule = '';
        $oPlan = Plan::find($oSquad->plan_id);
        $structs = $cell_id = $module_id =  '';
        $cells = $modules = $nodes = [];
        $oPlan->commonHeader($structs, $cell_id, $module_id, $node_id,$cells,$modules,$nodes);
        $cell_id && $oCell = Cell::find($cell_id);
        $module_id && $oModule = Module::find($module_id);
    }



    //发起投票
    // ajax 设置完成时间+消息提示
    public function nodeSetVote($squad_id,$module_id,$node_id) {
        $oUser = \Auth::user();
        $start_date = date("Y-m-d H:i:s");
        $times = \Request::input('end_time','');
        $end_date = date('Y-m-d H:i:s',time()+$times*60);
        $sNode = Node::find($node_id);
        if($sNode->type==6){
            if($end_date) {
                $wj_id = $sNode->wj_id;
                $oNode = \DB::table('ext_wj_examps')->find($wj_id);
                $oSquad = Squad::find($squad_id);
                $oNodeSquad = NodeSquad::where('node_id', $node_id)->where('squad_id', $squad_id)->where('module_id',$module_id)->where('type',6)->first();
                if($oNodeSquad) {
                    $msg = [
                        "custom-msg"=> ["投票已经发送过了，不允许多次操作"],
                    ];
                    return response()->json($msg)->setStatusCode(422);
                }
                if($oNode && $oSquad) {

                    $oNodeSquad = new NodeSquad;
                    $oNodeSquad->node_id = $node_id;
                    $oNodeSquad->module_id = $module_id;
                    $oNodeSquad->squad_id = $squad_id;
                    $oNodeSquad->type =	6;
                    $oNodeSquad->save();

                    // $oNodeSquad = NodeSquad::where('node_id', $node_id)->where('squad_id', $squad_id)->where('type', $type)->where('created_at', $oNodeSquad->created_at)->orderBy('id','desc')->first();
                    //$oNodeSquads = \DB::table('ext_wj_examp')->where('id',$wj_id)->first();
                    // Student::where('squad_id', $squad_id)->get()->each(function($oStudent) use ($squad_id, $start_date, $end_date, $oSquad, $oNode, $type, $oNodeSquad,$oNodeSquads) {

                    // });
                    // $oCell = $oModule = '';
                    $data1['wj_id'] = $wj_id;
                    $data1['teacher_id'] = $oUser->teacher->id;
                    $data1['squad_id'] = $squad_id;
                    $data1['module_id'] = $module_id;
                    $data1['node_id'] = $node_id;
                    $data1['time'] = strtotime($end_date);
                    $data1['created_at'] = date('Y-m-d H:i:s',time());
                    $ws_id = \DB::table('ext_wj_send')->insertGetId($data1);

                    $url = route('student_votes',["{$ws_id}","{$squad_id}"]);
                    // 执行发送通知
                    $a = WechatNotifyController::homework_vote(
                        $squad_id,
                        '',
                        '',
                        $url,
                        $start_date,
                        $oNode->title,
                        $oNode->title,
                        $start_date,
                        $end_date,
                        0
                    );
                    /*
                    //$oStudents = Student::where('squad_id', $squad_id)->pluck('id');
                    $oStudents = \DB::table('squad_structs')->where('squad_id',$squad_id)->where('type',1)->pluck("struct_id");
                    $oWqs = \DB::table('ext_wj_questions')->where('id',$wj_id)->pluck('id');
                    $data['wj_id'] = $wj_id;
                    $data['ws_id'] = $ws_id;
                    foreach($oStudents as $student)
                    {
                        $data['student_id'] =  $student;
                        foreach($oWqs as $wq)
                        {
                            $data['wq_id'] = $wq;
                            \DB::table('ext_wj_result')->insert($data);
                        }
                    }
                    // dd($oNotify);
                    */
                    $oNotify = Notify::where('user_id',$oUser->id)->orderBy('id','desc')->first();
                    $oNotify->squads()->sync([$squad_id]);
                    return response()->json(null);
                }
            }
        }
        $msg = [
            "custom-msg"=> ["参数错误，非法操作"],
        ];
        return response()->json($msg)->setStatusCode(422);
    }
}
