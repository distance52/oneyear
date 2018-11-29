<?php

namespace App\Http\Controllers\Administrator\Xmpy;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use App\Models\ProjectSchoolProcess;
use App\Models\Project;
use App\Models\Student;
use App\Models\School;
use App\Models\ProjectOverview;
use App\Models\ProjectReservation;
use App\Models\ProjectSchoolTutor;
use App\Models\ProjectTutor;
use App\Models\Teacher;
use App\Models\ProjectInvite;
use App\Models\ProjectGuide;
use App\Models\ProjectLog;
use App\Models\ProjectProcess;
use App\Models\ProjectInfo;
use App\Models\User;
use App\Events\ProjectEvent;
class SettingController  extends Controller
{
    protected $stage = array();

    public function __construct()
    {
//        parent::__construct();
//        $stage['onlyType'] = array('icon-faqixiangmu' , 'icon-zhuti' , 'icon-gaishu-' , 'icon-BPguanli' , 'icon-reserve' , 'icon-yuanxing' , 'icon-luyan' , 'icon-rongzi' , '完成' );
        $stage['onlyType'] =  array('发' , '题' , '概' , 'BP' , '优' , '原' , '演' , '融' , '完成' );

        $stage['all'] = array('发起项目' , '确立项目主题' , '生成项目概述' , '生成项目BP' , '项目打磨与优化' , '项目原型与样本' , '项目展示与路演' , '融资' , '完成' );

        $this->stage = $stage;

    }

    /*
     * 管理员给学校设置服务商分类
     * */
    public function settingindex()
    {
        $data=ProjectSchoolProcess::with('school')->paginate(12);//duo
        return view('default.xmpy.settingindex',compact('data'));
    }

    public function settingshow(Request $request,$id){
        $data = ProjectSchoolProcess::where('school_id',$request->school_id)->first();
        if($request->isMethod('post')){
            if($data){
                $data->school_id = $request->school_id;
                $data->process = json_encode($request->process);
            }else{
                $data = new ProjectSchoolProcess();
                $data->school_id = $request->school_id;
                $data->process = json_encode($request->process);
            }
            $data->save();
            return redirect('/xmpy/settingindex');
        }else{
            $checked = empty($data->process) ? '' : json_decode($data->process , true);
            return view('default.xmpy.settingshow' ,compact('checked'));
        }
    }
    public function index(){
        $stage=$this->stage['all'];
        $data =$count=$list= [];
        $count['tutorCount']=ProjectSchoolTutor::count('id');
        $count['ProjectStudentCount']=Project::groupBy('student_id')->count('id');
        $count['ProjectCount']=Project::count('id');
        $project=Project::select('uuid')->get()->toArray();

        $list['studentMessage']=ProjectLog::where(['type'=>2])->whereIn('project_uuid',$project)
            ->where('stage','!=',4)->with('student')->orderBy('id','desc')->limit(5)->get();
        $list['tutorMessage']=ProjectGuide::whereIn('project_uuid',$project)->with('tutor')->limit(5)->get();

        $invite=ProjectInvite::with('tutor')->take(5)->get();
        return view('default.xmpy.index' ,compact('data','count','list','invite','stage'));
    }


    public function review(){
        $data = Project::with('student')->orderBy('status')->paginate(10);
        foreach($data as $k=>&$v){
            $v['newcontent']=jStringSubstr(strip_tags($v['content']),5);
        }
        $search ='';
        $stage = $this->stage;
        $status = array('等待学生完成','学生已完成，等待导师点评','等待学校管理员审核并进入下一阶段');
        return view('default.xmpy.review' ,compact('data','search','stage','status'));
    }

    public function show($id){
//        $data = Project::where('uuid',$id)->where('school_id',$this->school_id)->first();
//        $stage = $this->stage;

        $obj = ProjectProcess::where(['project_uuid'=>$id])->first();
        if(empty($obj)){
            return redirect('/xmpy/review');
        }
        $stage = $this->stage;
        $process = ProjectSchoolProcess::where('school_id',$obj->school_id)->value('process');
        $stage['process'] = json_decode($process ,true);
//        $stage['active'] = json_decode($obj->stages ,true);
        $data = Project::where('uuid',$id)->first();
        $info=[];

        foreach($stage['process'] as $k=>$v){
            $info[$v]['student'] = ProjectLog::where(['project_uuid' => $id, 'plat' => 3, 'stage' => $v])
                ->with('student', 'guide')
                ->get()->toArray();
        }
        foreach($info as $k=>$v) {
            foreach ($v['student'] as $kk => $vv) {
                if ($vv['stage'] == $k) {
                    $tutor = ProjectLog::where(['project_uuid' => $id, 'plat' => 4, 'stage' => $k])->with('tutor')->select('user_id')->get()->toArray();
                    if($tutor){
                        foreach ($vv['guide'] as $kkk => $vvv) {
                            $info[$k]['student'][$kk]['guide'][$kkk]['name'] = $tutor[$kkk]['tutor']['name'];
                        }
                    }
                }
            }
        }
        return view('default.xmpy.show' ,compact('data','info','stage'));
    }



//学校管理员同意进入下一阶段
    public function approve($id , Request $request){
        $obj = Project::where('uuid',$id)->where('school_id',$this->school_id)->first();
        if(empty( $obj )){
            return response()->json(array('status' =>false ,'data' =>'','msg'=>'审核失败'));
        }
        //阶段大于1需要导师提交审核通过下一阶段
        //阶段为0需要管理员通过审核，在之后导师可以进入
        if($obj->stage==7&&$obj->status==2){
            $name=Student::where("id",$obj->student_id)->value('name');
            $message="恭喜学生".$name."项目".$id."完成.继续努力";
            ProjectLog::firstOrCreate(['project_uuid'=>$id,'stage'=>7,'plat'=>3,'user_id'=>$obj->student_id]
                ,['reward_type'=>0,'reward'=>0,'type'=>0,'message'=>$message]);
//            \Event::fire(new ProjectEvent($id,$obj->stage , 3 ,$obj->student_id,$message , 0 , 0 , 0));
            return response()->json(array('status' =>false ,'data' =>'','msg'=>'任务结束'));
        }

        if($obj->stage){
            if($obj->status != 2){
                //status=2 为导师对流程同意，申请进入下一流程
                return response()->json(array('status' =>false ,'data' =>'','msg'=>'审核失败'));
            }
        }else{
            if($obj->stage==0&&$obj->status==2){
                $stage = $this->stage;
                $curr = array_search($obj->stage, $stage['process']);

                $obj->status = 0;
                $obj->stage = $stage['process'][$curr+1];
                $obj->save();
                $message="项目".$obj->uuid.'的【'.$stage['all'][$curr]."】已完成";
                \Event::fire(new ProjectEvent($id,$obj->stage , 1 ,\Auth::user()->id,$message , 0 , 0 , 0));

                return response()->json(array('status' =>true ,'data' =>'','msg'=>'ok'));
            }
            if($obj->status != 1){
                return response()->json(array('status' =>false ,'data' =>'','msg'=>'审核失败'));
            }

        }


//        if($request->input('approve')){
        $stage = $this->stage;
        $curr = array_search($obj->stage, $stage['process']);

        $obj->status = 0;
        $obj->stage = $stage['process'][$curr+1];
        $obj->save();
        //记录日志(学校管理员)
        $message="项目".$obj->uuid.'的【'.$stage['all'][$curr]."】已完成";
        \Event::fire(new ProjectEvent($id,$obj->stage , 1 ,\Auth::user()->id,$message , 0 , 0 , 0));

//        }else{
//            //被拒绝，这里需要管理员来选择指派给哪个阶段，0/1
//            $obj->status = $request->input('status',0);
//            $obj->save();
//        }

//        $projectProcess=ProjectProcess::where(['project_uuid'=>$id,'school_id'=>$this->school_id])->first();
//        $projectTutor=ProjectTutor::find($projectProcess->id);
//
//        //给导师结算积分
//        if($request->input('approve')){
//
//            //记录日志(导师)
//            $message="导师".$projectTutor->name."辅导完成项目".$projectProcess->project_uuid."的".$stage['process'][$curr]."添加2个积分";
//            \Event::fire(new ProjectEvent($id,$obj->stages , 4 ,$projectTutor->user_id,$message , 1 , 1 , 2));
//
//        }

        return response()->json(array('status' =>true ,'data' =>'','msg'=>'ok'));

    }
    //本校导师
    public function tutor(){
        $data = ProjectSchoolTutor::where(['status'=>1,'type'=>1])->with('tutor')->paginate(10);
        return view('default.xmpy.teacher' ,compact('data'));
    }
    //本校导师个人信息
    public function teacherInfo($id,Request $request){
        $teacherinfo=ProjectTutor::find($id);
        return view('default.xmpy.teacherinfo' ,compact('teacherinfo'));
    }
    //修改信息
    public function doTeacherInfo(Request $request){
        $teacherinfo=ProjectTutor::find($request->id);
        if($request->isMethod('post')){
            if ($request->hasFile('avatar')) {
                $file = $request->file('avatar');
                $filename = $file->getClientOriginalName();
//            $result = basename($filename,".".substr(strrchr($filename, '.'), 1));//文件名
                $extension = $file->getClientOriginalExtension();
                $file_name = md5(time() . $filename) . "." . $extension;
                \Storage::disk('oss')->put($file_name, file_get_contents($file->getRealPath()));
                if (\Storage::disk('oss')->exists($file_name)) {
                    $teacherinfo->avatar = $file_name;
                } else {
                    $teacherinfo->avatar = $file_name;//file src
                }
            }
            $teacherinfo->name=$request->name;
            $teacherinfo->desc=$request->desc;
            $teacherinfo->save();
            return redirect('/xmpy/tutor');
        }
    }

    //移除本校的导师
    public function delTutor($id){
        $tutor=ProjectSchoolTutor::find($id);
//        $tutor_id=ProjectSchoolTutor::where("school_id",$this->school_id)->pluck('tutor_id')->toArray();
        if($tutor->school_id!=$this->school_id){
            return response()->json(array('status' =>false ,'data' =>'','msg'=>'非法操作'));
        }
        $process=ProjectProcess::where(['school_id'=>$this->school_id,'tutor_id'=>$tutor->tutor_id])->pluck('stages')->first();
        if($process){
            return response()->json(array('status' =>false ,'data' =>'','msg'=>'该老师现在有辅导的项目'));
        }
        if($tutor){
            $tutor->status=0;
            $tutor->save();
            return response()->json(array('status' =>true ,'data' =>'','msg'=>'ok'));
        }else{
            return response()->json(array('status' =>false ,'data' =>'','msg'=>'no'));
        }
    }

    //学校管理员邀请列表
    public function schoolInvite(Request $request){
        $data=ProjectInvite::with('tutor')->paginate(12);
        return view('default.xmpy.schoolinvite' ,compact('data'));

    }
//    管理员同意设置导师辅导项目
    public function schoolInviteAgree($id,Request $request){
//        if($request->isMethod('post')){

        if($request->type==0){
            $obj = ProjectInvite::where('id',$id)->first();
            if(empty($obj)){
                return response()->json(array('status' =>false ,'data' =>'','msg'=>'操作错误'));
            }

            $pObj=ProjectSchoolTutor::firstOrCreate(['school_id'=>$obj->school_id,'tutor_id'=>$obj->tutor_id,'score'=>0],
                ['status'=>1,'type'=>0]
            );

            $obj->status=1;
            $obj->save();
            $tutorName=ProjectTutor::where('id',$obj->tutor_id)->value('name');
            $school_name=School::where('id',$obj->school_id)->value('name');
            //日志
            if($pObj->wasRecentlyCreated){
                $message="导师".$tutorName."成为".$school_name."的校外导师";
                \Event::fire(new ProjectEvent($pObj->id,0 , 4 ,$obj->tutor_id,$message , 0 , 0 , 0));
            }
        }else{
            $info=ProjectInvite::where('id',$id)->with('tutor')->first();
            $obj=Project::where('uuid',$info->project_uuid)->first();
            if(empty($obj)||$obj->stage!=$info->stage){
                return response()->json(array('status' =>false ,'msg' => '消息过期' ));
            }
            $data = ProjectProcess::where(['project_uuid'=>$info['project_uuid']])->with('tutor')->get();
            foreach ($data as $v) {
                if ($v->project_uuid == $info['project_uuid'] && $v->tutor_id == $info['tutor_id']) {
                    return response()->json(array('status' =>false ,'msg' => '本项目已在其他阶段指派过了该教师' ));
                }
                $set_stage = json_decode($v->stages , true);
                if($set_stage==null)$set_stage=array();
                if(array_intersect($set_stage,explode(",",$obj->stage))){
                    return response()->json(array('status' =>false ,'msg' => '项目阶段已设置教师' ));
                }
            }
            $obj1 = new ProjectProcess();
            $obj1->project_uuid = $info['project_uuid'];
            $obj1->school_id = $info['school_id'];
            $obj1->tutor_id = $info['tutor_id'];
            $obj1->stages = json_encode(explode(",",$obj->stage));
            $obj1->save();
            $info->status=1;
            $info->save();
            //写入日志
            $message="系统管理员同意学生".$info->invite_name."邀请导师".$info['tutor']['name']."加入项目".$info['project_uuid'];
            \Event::fire(new ProjectEvent($info['project_uuid'],$obj->stage , 1 , \Auth::user()->id,$message , 0 , 0 , 0));
        }
//        return redirect('/xmpy/schoolInvite');

    }

//    public function guide($id ,Request $request){
//        $oProject = Project::where('uuid',$id)->where('school_id',$this->school_id)->first();
//        if(empty($oProject) || $oProject->stage == 0){
//            return response()->json(array('status' =>false ,'data' =>''));
//        }
//        $stage = $request->input('stage',0);
//        $data = ProjectGuide::where('project_uuid',$id);
//        if($stage){
//            $data = $data->where('stage',$stage);
//        }
//        $data = $data->orderBy('created_at','desc')->take(10)->get()->toArray();
//        return response()->json(array('status' =>false ,'data' =>$data));
//    }

    //学校设置学生项目的辅导老师
    public function process($id ,Request $request){
        $data = ProjectProcess::where(['school_id'=>$this->school_id,'project_uuid'=>$id])->with('tutor')->get();

        if($request->isMethod('post')){
            $obj = new ProjectProcess();
            if($request->type=="studentinvite"){
                $invite=ProjectInvite::where('id',$request->id)->first();
                foreach ($data as $v) {
                    if ($v->project_uuid == $id && $v->tutor_id == $invite->tutor_id) {
                        return response()->json(array('status' =>false ,'msg' => '本项目已在其他阶段指派过了该教师' ));
                    }
                    $set_stage = json_decode($v->stages , true);
                    if($set_stage==null)$set_stage=array();
                    if(array_intersect($set_stage,$invite->stage)){
                        return response()->json(array('status' =>false ,'msg' => '项目阶段已设置教师' ));
                    }
                }
                $obj->project_uuid = $id;
                $obj->school_id = $this->school_id;
                $obj->tutor_id=$invite->tutor_id;
                $obj->stages= json_encode($invite->stage);
            }else{
                foreach ($data as $v) {

                    if ($v->project_uuid == $id && $v->tutor_id == $request->input('tutor_id')) {
                        return response()->json(array('status' =>false ,'msg' => '本项目已在其他阶段指派过了该教师' ));
                    }
                    $set_stage = json_decode($v->stages , true);
                    if($set_stage==null)$set_stage=array();
                    if(array_intersect($set_stage,$request->input('stages'))){
                        return response()->json(array('status' =>false ,'msg' => '项目阶段已设置教师' ));
                    }
                }
                $obj->project_uuid = $id;
                $obj->school_id = $this->school_id;
                $obj->stages = json_encode($request->input('stages'));
                $obj->tutor_id = $request->input('tutor_id');
            }


            $obj->save();
            //记录日志(学校管理员)
            return redirect('/xmpy/review');
        }else{

            $stage = $this->stage;
            return redirect('/xmpy/review');
        }
    }
    //导师
    public function allTutor($id,Request $request){
//        $tutor_id=ProjectSchoolTutor::select('tutor_id')->get()->toArray();
        $setTutor=ProjectProcess::where(['project_uuid'=>$id])->pluck('tutor_id');
        $param['tutorAll']=new ProjectTutor;

        if($request->email){
            $param['tutorAll']=$param['tutorAll']->where('email','like','%'.trim($request->email).'%');
        }
        $param['type']=$request->type;
        $param['email']=$request->email;
        $param['count']=$param['tutorAll']->count('id');//

        $param['pageSize']=8;//
        $param['page']=isset($request->page)?$request->page:1;
        $param['totalPage'] = ceil($param['count'] / $param['pageSize']);//
        $start = ($param['page'] -1 ) * $param['pageSize'];
        $param['tutorAll']=$param['tutorAll']->skip($start)->take($param['pageSize'])->get()->toArray();
        return response()->json(array('status' => true ,'msg' =>'ok','data'=>$param));

    }
    public function reservation(Request $request){
        $data=ProjectReservation::with('tutor','student')->paginate(12);
        return view('default.xmpy.reservation' ,compact('data'));

    }
    //导师预约接口
    public function bossReservation($id,Request $request){

            //这里的log_id是reservation 里面的主键id
            $obj = ProjectReservation::where(['id'=>$id])->first();

            $obj1 = Project::where('uuid',$obj->project_uuid)->first();
            if(empty( $obj1 ) ){
                return response()->json(array('status' =>false ,'data' =>''));
            }
            if(empty( $obj ) || $obj->status != 0 ){
                return response()->json(array('status' =>false ,'data' =>'','msg'=>'审核失败'));
            }
            $obj->status = $request->type;

            if($obj->status==1){
                $obj1->status=1;
                $obj1->save();
            }else{
                $obj1->status=0;
                $obj1->save();
            }
            //加入message到日志
            $obj->save();
            $agree=$request->type==1?'同意':'不同意';
            //写入日志
            $name=ProjectTutor::where('id',$obj->tutor_id)->value('name');
            $message="导师".$name.$agree."该时间段辅导".$obj->project_uuid;
            \Event::fire(new ProjectEvent($id,$obj1->stage , 4 ,$obj->tutor_id,$message , 0 , 0 , 0));
            $guide= new ProjectGuide;
            $guide->project_uuid=$id;
            $guide->stage=$obj1->stage;
            $guide->log_id=$obj->log_id;
            $guide->tutor_id=$obj->tutor_id;
            $guide->content=$message;
            $guide->save();
            return response()->json(array('status' =>true ,'data' =>$message));

    }






}