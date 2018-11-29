<?php

namespace App\Http\Controllers\Student;

use App\Models\Exampaper;
use App\Models\NodeSquad;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use App\Http\Requests;
use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\SquadStruct;
use App\Models\Group;
use App\Models\GroupStudent;
use App\Models\User;
use App\Models\GroupScore;
use App\Models\Score;
use App\Models\StudentGroupScore;
use App\Models\Squad;
use Illuminate\Routing\UrlGenerator;

class GroupController extends BaseController
{
    //分组首页
    public function index(Request $request,$squad_id){

        $user_id = $this->user_id;
        // $squad_id=$this->squad_id;
        $oUser = \Auth::user();
        $oStudent = Student::where('user_id',$oUser->id)->first();
        $squads =SquadStruct::where('type',1)->where('struct_id',$oStudent->id)->pluck('squad_id')->toArray();
        if(in_array($squad_id,$squads)){
            $type=$request->input('type',0);
            $oGroup=Group::where(['squad_id'=>$squad_id,'type'=>$type])->get();
            $groups = Group::where('squad_id',$squad_id)->pluck('id')->toArray();
            $xmz_id=GroupStudent::where(['student_id'=>$this->student_id,'type'=>0])->whereIn('group_id',$groups)->orderBy('id','desc')->value('group_id');
            $ztz_id=GroupStudent::where(['student_id'=>$this->student_id,'type'=>1])->whereIn('group_id',$groups)->orderBy('id','desc')->value('group_id');
//            $my_xmz_group_id=empty($xmz_id) ? 0:$xmz_id;
//            $my_ztz_group_id=empty($ztz_id) ? 0:$ztz_id;

            $my_xmz_group_id = empty($xmz_id) ? 0:$xmz_id;
            $my_ztz_group_id = empty($ztz_id) ? 0 : $ztz_id;
//            $group_ids=Group::where(['squad_id'=>$squad_id,'type'=>$type])->pluck('id');
            //项目组/专题组有一个分组已经有了评分记录，则进入查看积分页面，不再让创建项目组，加入其他项目组
            /*
            $count=Score::whereIn('group_id',$group_ids)->count();
            if($count>0){
                foreach($oGroup as $val){
                    $arr=array();
                    $arr['id']=$val->id;
                    $arr['name']=$val->name;
                    $arr['studentNum']=GroupStudent::where(['group_id'=>$val->id])->count();//组员人数
                    $arr['judgeNum']=GroupScore::where(['group_id'=>$val->id])->count();//分组被评分次数
                    $group_student_ids=GroupStudent::where(['group_id'=>$val->id])->pluck('student_id');//此项目组的所有studentid
                    $user_ids=Student::whereIn('id',$group_student_ids)->pluck('user_id');
                    $arr['users']=User::whereIn('id',$user_ids)->pluck('name');//这个分组的所有成员昵称
                    //评分表
                    $arr['pingfen_table']=$this->_getPingFenTable($val->id);
                    if(!empty($arr['pingfen_table'])){
                        $score=array_column($arr['pingfen_table'],'score');
                        $arr['totalScore']=array_sum($score);
                    }
                    else{
                        $arr['totalScore']=0;
                    }
                    array_push($oObjs,$arr);
                }
                $mygroup=collect([$my_xmz_group_id,$my_ztz_group_id]);
                return View('studentPlat.group.score',compact('oObjs','squad_id','type','mygroup'));
            }
            else{
                foreach($oGroup as $val){
                    $arr['id']=$val->id;
                    $arr['name']=$val->name;
                    $group_student_ids=GroupStudent::where(['group_id'=>$val->id])->pluck('student_id');//此项目组的所有studentid
                    $user_ids=Student::whereIn('id',$group_student_ids)->pluck('user_id');
                    $arr['users']=User::whereIn('id',$user_ids)->pluck('name');//这个分组的所有成员昵称
                    array_push($oObjs,$arr);
                }
                $mygroup=collect([$my_xmz_group_id,$my_ztz_group_id]);
                return View('studentPlat.group.index',compact('oObjs','squad_id','type','mygroup'));
            }
            */
            $oObjs=array();
            foreach($oGroup as $val){
                $arr=array();
                $arr['id']=$val->id;
                $arr['name']=$val->name;
                $group_student_ids=GroupStudent::where(['group_id'=>$val->id])->pluck('student_id');//此项目组的所有studentid
                $user_ids=Student::whereIn('id',$group_student_ids)->pluck('user_id');
                $arr['users']=User::whereIn('id',$user_ids)->pluck('name');//这个分组的所有成员昵称
                array_push($oObjs,$arr);
            }
            $mygroup=collect([$my_xmz_group_id,$my_ztz_group_id]);
            if($type==0){
                $template='index-xmz';
            }
            else{
                $template='index-ztz';
            }
            if (view()->exists(session('mode').'.studentPlat.group.'.$template)){
                return View(session('mode').'.studentPlat.group.'.$template,compact('oObjs','squad_id','type','mygroup'));
            }else{
                return View('default.studentPlat.group.'.$template,compact('oObjs','squad_id','type','mygroup'));
            }
        }else{
            return redirect('error')->with(['msg'=>'非法操作', 'href'=>app(UrlGenerator::class)->previous()]); 
        }

    }

    /**
     * 获取某个项目组的评分表数据
     * @param $group_id
     * @return array
     */
    private function _getPingFenTable($group_id){
        $pingfen_exampaper_id=Score::where('group_id',$group_id)->where('type',4)->pluck('exampaper_id');
        $pingfen_unique_exampaper_id=$pingfen_exampaper_id->unique()->values()->all();
        $return=array();
        foreach($pingfen_unique_exampaper_id as $v){
            $arr=array();
            $arr['name']=Exampaper::where('id',$v)->value('name');
            $total_score=Score::where(['exampaper_id'=>$v,'type'=>4,'group_id'=>$group_id])->sum('number');//某个评分表的总分
            $total_num=Score::where(['exampaper_id'=>$v,'type'=>4,'group_id'=>$group_id])->count();//某个评分表的评分次数
            $score=round($total_score/$total_num);//求平均分
            $arr['score']=$score;
            $arr['addtime']=Score::where(['exampaper_id'=>$v,'group_id'=>$group_id])->orderBy('id','desc')->value('end_time');//取最后一个评分的人评分时间作为评分表的时间
            array_push($return,$arr);
        }
        return $return;
    }

    private function _getOnePingFenTable($group_id,$exampaper_id){
        $arr=array();
        $arr['name']=Exampaper::where('id',$exampaper_id)->value('name');
        $total_score=Score::where(['exampaper_id'=>$exampaper_id,'type'=>4,'group_id'=>$group_id])->sum('number');//某个评分表的总分
        $total_num=Score::where(['exampaper_id'=>$exampaper_id,'type'=>4,'group_id'=>$group_id])->count();//某个评分表的评分次数
        $score=round($total_score/$total_num);//求平均分
        $arr['score']=$score;//这次评分表的平均得分
        $arr['student_num']=GroupStudent::where('group_id',$group_id)->count();//这个分组的人数
        return $arr;
    }

    public function pingfen(Request $request,$squad_id){
        $user_id = $this->user_id;
        $type=$request->input('type',0);
        $my_group_id=GroupStudent::where(['student_id'=>$this->student_id,'type'=>$type])->whereHas('group', function($query) use($squad_id){return $query->where('squad_id',$squad_id);})->value('group_id');//我的项目组/专题组id
        $num_ids=NodeSquad::where('squad_id',$squad_id)->where('type',4)->pluck('id');//我所在班级的评分表ids
        $oObj=Score::where('group_id',$my_group_id)->where('type',4)->where('student_id',$this->student_id)->whereIn('num',$num_ids)->with('exampaper')->get();
        $studentNum=GroupStudent::where(['group_id'=>$my_group_id,'type'=>$type])->count();//组员人数
        $group_student_ids=GroupStudent::where(['group_id'=>$my_group_id,'type'=>$type])->pluck('student_id');//此项目组的所有studentid
        $user_ids=Student::whereIn('id',$group_student_ids)->pluck('user_id');
        $users=User::whereIn('id',$user_ids)->get(['name','id'])->toArray();//这个分组的所有成员昵称
        $return=array();

        foreach($oObj as $val){
            $arr=array();
            $arr['group_id']=$val->group_id;//给项目组/专题组评分截止时间
            $pingfen_endtime=strtotime($val->start_time)+2700;//45分钟评完
            $fenpei_endtime=$pingfen_endtime+10800;
            $arr['exampaper_end_time']=date('Y-m-d H:i:i:s',$pingfen_endtime);//45分钟评完
            $arr['dead_time']=date('Y-m-d H:i:s',$fenpei_endtime);//本次评分截至时间
            $arr['dead_timestamp']=$fenpei_endtime;//本次评分截至时间戳
            $arr['id']=$val->exampaper->id;//评分表的id
            $arr['name']=$val->exampaper->name;//评分表的名字
            $arr['num']=$val->num;//评分次数
            $arr['studentNum']=$studentNum;//组员人数
            $total_score=Score::where(['exampaper_id'=>$val->exampaper_id,'type'=>4,'group_id'=>$my_group_id,'num'=>$val->num])->sum('number');//某次评分的总分
            $total_num=Score::where(['exampaper_id'=>$val->exampaper_id,'type'=>4,'group_id'=>$my_group_id,'num'=>$val->num])->where('number','>',0)->count();//某个评分表的评分次数，未评分的mumber为0
            $score=($total_num==0)?0:round($total_score/$total_num);//求平均分
            $arr['score']=$score;//这次评分表的平均得分
            $arr['totalscore']=$score*$studentNum;//这次评分表的平均得分
            $group_user_ids=array_column($users,'id');
            $scores=StudentGroupScore::whereIn('to_user_id',$group_user_ids)->where(['from_user_id'=>$this->user_id,'exampaper_id'=>$val->exampaper_id,'group_id'=>$my_group_id,'num'=>$val->num])->pluck('score','to_user_id');
            $sc_count = StudentGroupScore::whereIn('to_user_id',$group_user_ids)->where(['exampaper_id'=>$val->exampaper_id,'group_id'=>$my_group_id,'num'=>$val->num])->groupBy('from_user_id')->count();//小组评分需总人数
            $total = StudentGroupScore::where(['exampaper_id'=>$val->exampaper_id,'group_id'=>$my_group_id,'num'=>$val->num])->count();
            if ($sc_count && $total){
                $people_num = $total/ $sc_count;
                $arr['status'] =  $sc_count == $people_num ? 0 : 1;
            }else{
                $arr['status'] = 1;
            }

            foreach($users as $k=>$v){
                /*
                //我在这次评分中获得的总分
                $totalscore=StudentGroupScore::where(['to_user_id'=>$v['id'],'exampaper_id'=>$val->exampaper_id,'group_id'=>$my_group_id,'num'=>$val->num])->sum('score');
                //这次参与评分的人数
                $num=StudentGroupScore::where(['to_user_id'=>$v['id'],'exampaper_id'=>$val->exampaper_id,'group_id'=>$my_group_id,'num'=>$val->num])->count();
                $v['score']=($num==0)?0:round($totalscore/$num);
                */
                $users[$k]['score']=isset($scores[$v['id']])?$scores[$v['id']]:0;
            }
            $arr['users']=$users;//这个分组的所有成员昵称及得分
            array_push($return,$arr);
        }
        $oObjs=$return;
        if($type==0){
            $template='pingfen-xmz';
        }
        else{
            $template='pingfen-ztz';
        }
		if (view()->exists(session('mode').'.studentPlat.group')){
			return View(session('mode').'.studentPlat.group.'.$template,compact('oObjs','squad_id','type'));
		}else{
			return View('default.studentPlat.group.'.$template,compact('oObjs','squad_id','type'));
		}
    }

    /**
     * 分配某次得分
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function distribute(Request $request){
        $squad_id=$this->squad_id;
        $user_point=$request->input('user_points');
        $group_id=$request->input('group_id',0);
        $num=$request->input('num',0);
        $exampaper_id=$request->input('exampaper_id',0);
        if($exampaper_id==0){
            $msg = [
                "custom-msg"=> ["评分表不能为空"],
            ];
            return response()->json($msg)->setStatusCode(422);
        }
        if($group_id==0){
            $msg = [
                "custom-msg"=> ["分组不能为空"],
            ];
            return response()->json($msg)->setStatusCode(422);
        }
        if($num==0){
            $msg = [
                "custom-msg"=> ["评分次数不能为0"],
            ];
            return response()->json($msg)->setStatusCode(422);
        }
        $total_fenpei_point=array_sum(array_values($user_point));//用户传递过来的总分
        $studentNum=GroupStudent::where(['group_id'=>$group_id])->count();//组员人数
        $total_score=Score::where(['exampaper_id'=>$exampaper_id,'type'=>4,'group_id'=>$group_id,'num'=>$num])->sum('number');//某个评分表的总分
        $total_num=Score::where(['exampaper_id'=>$exampaper_id,'type'=>4,'group_id'=>$group_id,'num'=>$num])->where('number','>',0)->count();//某个评分表的评分次数
        $score=($total_num==0)?0:round($total_score/$total_num);//求平均分
        $totalscore=$score*$studentNum;//这次评分表的总分=平均分*组员人数
        $student=GroupStudent::where(['student_id'=>$this->student_id,'group_id'=>$group_id])->first();
        $end_time=Score::where(['exampaper_id'=>$exampaper_id,'type'=>4,'group_id'=>$group_id,'num'=>$num])->first(['start_time']);
        $pingfen_endtime=strtotime($end_time->start_time)+2700;//45分钟评完
        $dead_timestamp=$pingfen_endtime+10800;//本次分配积分截至时间戳
        if(time()<$pingfen_endtime){
            $msg = [
                "custom-msg"=> ["评分45分钟后才能分配积分"],
            ];
            return response()->json($msg)->setStatusCode(422);
        }
        if(time()>$dead_timestamp){
            $msg = [
                "custom-msg"=> ["已经过了分配积分的截至时间，不能再参与分配"],
            ];
            return response()->json($msg)->setStatusCode(422);
        }
        if(empty($student)){
            $msg = [
                "custom-msg"=> ["您不是该组成员，无法去分配该组积分"],
            ];
            return response()->json($msg)->setStatusCode(422);
        }
        if($totalscore==0){
            $msg = [
                "custom-msg"=> ["项目组总分为0，无法分配"],
            ];
            return response()->json($msg)->setStatusCode(422);
        }
        if($total_fenpei_point!=$totalscore){
            $msg = [
                "custom-msg"=> ["所分配的分值总和跟分组所得总分不匹配"],
            ];
            return response()->json($msg)->setStatusCode(422);
        }
        //如果开始分配积分了则直接进入分配积分的页面
        foreach($user_point as $key=>$val){
            $oObj=StudentGroupScore::where(['group_id'=>$group_id,'exampaper_id'=>$exampaper_id,'num'=>$num,'from_user_id'=>$this->user_id,'to_user_id'=>$key])->first();
            if(empty($oObj)){
                $oObj=new StudentGroupScore();
                $oObj->group_id=$group_id;
                $oObj->num=$num;
                $oObj->exampaper_id=$exampaper_id;
                $oObj->from_user_id=$this->user_id;
                $oObj->to_user_id=$key;
                $oObj->score=$val;
                $oObj->save();
            }
            else{
                $oObj->score=$val;
                $oObj->save();
            }
        }
        return response()->json(null);
    }

    /**
     * 加入某个分组
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function join(Request $request,$squad_id){

        $group_id=$request->input('group_id',0);
        if($group_id==0){
            $msg = [
                "custom-msg"=> ["参数缺少"],
            ];
            return response()->json($msg)->setStatusCode(422);
        }
        $group_info = Group::where('id',$group_id)->first();
        if(empty($group_info)){
            $msg = [
                "custom-msg"=> ["项目组不存在"],
            ];
            return response()->json($msg)->setStatusCode(422);
        }

        if($group_info['squad_id'] != $squad_id){
            $msg = [
                "custom-msg"=> ["您所在的班级跟项目组所属班级不符"],
            ];
            return response()->json($msg)->setStatusCode(422);
        }

//        $students = GroupStudent::where('group_id',$group_id)->pluck('student_id')->toArray();
        $scores = Score::where('group_id',$group_id)->where('squad_id',$squad_id)->first();
        if(!empty($scores) ){
            $msg = [
                "custom-msg"=> ["该班级的项目组/专题组已经开始评分了，不能更换分组"],
            ];
            return response()->json($msg)->setStatusCode(422);
        }
        //判断学生是否已经加入过该类型的项目组，项目组专题组只能加入一个
//        $join_info=GroupStudent::where(['student_id'=>$this->student_id,'type'=>$group_info['type']])->first();
        $join_info=GroupStudent::where(['student_id'=>$this->student_id,'type'=>$group_info['type']])
            ->with(['group' => function($query) use($squad_id){
                return $query->where('squad_id',$squad_id);
            }])->orderBy('id','desc')->first();
        if(isset($join_info->group)){
            $join_info->group_id=$group_id;
            $join_info->save();
        } else {
            $oObj=new GroupStudent();
            $oObj->group_id=$group_id;
            $oObj->student_id=$this->student_id;
            $oObj->type=$group_info['type'];
            $oObj->save();
        }
        return response()->json(null);
    }

    /**
     * 创建项目组
     */
    public function create(Request $request,$id){
        $group_name =$request->input('group','');
        $user_id    = $this->user_id;
        $squad_id   = $this->squad_id[0] ?? $this->squad_id;
        $student_id = $this->student_id;
        if($group_name==''){
            $msg = [
                "custom-msg"=> ["项目名不能为空"],
            ];
            return response()->json($msg)->setStatusCode(422);
        }
        $group=Group::where(['name'=>$group_name,'squad_id'=> $squad_id])->first();

        if(!empty($group)){
            $msg = [
                "custom-msg"=> ["同班已存在同名的项目组，请换一个名称再提交"],
            ];
            return response()->json($msg)->setStatusCode(422);
        }
        $school_id=Student::where('id', $student_id)->value('school_id');

        $oObj=new Group();
        $oObj->school_id = $school_id;
        $oObj->squad_id  = $id;
        $oObj->name      = $group_name;
        $oObj->user_id   = $user_id;
        $oObj->type      = 0;
        $oObj->save();

        return response()->json(null);
    }
}
