<?php

namespace App\Http\Controllers\School;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use App\Models\School;
use App\Models\User;
use App\Models\Teaching\Plan;
use App\Models\SchoolTime;
use App\Services\Plugin\PluginManager;
use App\Models\Option;

class SchoolSeetingController extends BaseController
{
    public function index(){
        $id=$this->school_id;
        $oObj = School::where(['id'=>$id])->first();
        $SchoolTime = SchoolTime::where('school_id',$id)->where('mainid','0')->get();
        $term = array('1'=>'上学期','2'=>'下学期');
        if($oObj) {
            if ($oObj->logo && \Storage::disk('oss')->exists($oObj->logo)) {
                $oObj->logo = \AliyunOSS::getUrl($oObj->logo, $expire = new \DateTime("+1 day"), $bucket = config('filesystems.disks.oss.bucket'));
            } else {
                $oObj->logo = '';
            }
            $plan_list = Plan::where('school_id', $id)->get(['id', 'name']);
            $oObj->score_rank = json_decode($oObj->score_rank, true);
            $oObj->up = json_decode($oObj->up, true);
            $oObj->down = json_decode($oObj->down, true);
            if($oObj->admin_user_id>0){
                $oObj->school_admin=User::where('id',$oObj->admin_user_id)->value('name');
            }
            else{
                $oObj->school_admin='尚未设置';
            }
			if(view()->exists(session('mode').'.schoolplat.school.schoolsetting')){
				return View(session('mode').'.schoolplat.school.schoolsetting', compact('oObj','plan_list','SchoolTime','term'));
			}else{
				return View('default.schoolplat.school.schoolsetting', compact('oObj','plan_list','SchoolTime','term'));
			}
        }
        else{
            return back()->withInput()->withErrors(['msg' => '学校不存在',]);
        }
    }
    
    public function addSchoolTime(Request $request){
        $school_time = new SchoolTime();
        $lessons = $request->input('lessons');//课程设置json数据
        $school_id = $request->input('school_id');
        $school_year = $request->input('school_year');
        $school_timestart = $request->input('school_timestart');
        $school_timeend = $request->input('school_timeend');
        $term = $request->input('term');
        //var_dump($lessons);die;
        
        //return response()->json($lessons);ajax返回json的方法
        $oUser = \Auth::user();
        if($oUser->plan == 0){
            $status = 0;
        }else{
            $status = 1;
        }
        $data_zhu = array('school_id'=>$school_id,'school_year'=>$school_year,'school_timestart'=>$school_timestart,'school_timeend'=>$school_timeend,'term'=>$term,'status'=>$status,'mainid'=>'0');
        $res_repeat=$school_time->where('school_year',$school_year)->where('school_id',$school_id)->where('term',$term)->first();
        if(!$res_repeat){
            $pid = $school_time->insertGetId($data_zhu);
        }else{
            $addsuc = array('school_year'=>$school_year,'term'=>$term,'state'=>'false');
                $addsuc = json_encode($addsuc);
                return response()->json($addsuc);
        }
        $createtime = date('Y-m-d h:i:s',time());
        
        $i=0;
        foreach($lessons as $lesson){
            
                $mainid = 0;
            
            $data[] = array('school_id'=>$school_id,'school_year'=>$school_year,'school_timestart'=>$school_timestart,'school_timeend'=>$school_timeend,'type'=>$lesson['type'],'course_number'=>$lesson['course_number'],'course_timestart'=>$lesson['course_timestart'],'course_timeend'=>$lesson['course_timeend'],'term'=>$term,'status'=>$status,'mainid'=>$pid);
            $i++;
        }
        
        //var_dump($data);die;
        
        /*$data = array('0'=>array('school_id'=>'2','school_year'=>'2016','school_timestart'=>'2017-08-01 10:16:57','school_timeend'=>'2017-08-01 10:16:57','type'=>'1','course_number'=>'1','course_timestart'=>'2017-08-01 10:16:57','course_timeend'=>'2017-08-01 10:16:57','create_time'=>'2017-08-01 10:16:57','term'=>'1','status'=>'1','mainid'=>'0'),
                '1'=>array('school_id'=>'2','school_year'=>'2016','school_timestart'=>'2017-08-01 10:16:57','school_timeend'=>'2017-08-01 10:16:57','type'=>'1','course_number'=>'1','course_timestart'=>'2017-08-01 10:16:57','course_timeend'=>'2017-08-01 10:16:57','create_time'=>'2017-08-01 10:16:57','term'=>'1','status'=>'1','mainid'=>'1')
            );
        $data = json_encode($data);
        $data = json_decode($data,true);*/
        
        
            $res = $school_time->insert($data);
            if($res){
                $addsuc = array('pid'=>$pid,'school_year'=>$school_year,'term'=>$term,'state'=>'true');
                $addsuc = json_encode($addsuc);
                return response()->json($addsuc);
                //echo '添加成功';die;
                //return back()->withInput()->withErrors(['msg' => '添加成功',]);
            }
            
       
        
       
        
    }
    
    public function editSchoolTime(Request $request){
        //$school_year = $request->input('school_year');
        //$school_year = '2011';
        //$term = $request->input('term');
        $mid = $request->input('mid');
        //echo $mid;die;
        //$term = '2';
        $school_time = new SchoolTime();
        $data = $school_time->select('id','school_year','term','school_timestart','school_timeend')->where('id',$mid)->first()->toarray();
        
        $data_child = $school_time->select('id','type','course_number','course_timestart','course_timeend')->where('mainid',$data['id'])->get()->toarray();
        //var_dump($data_child);die;
        foreach($data_child as $data_childs){
            $data_edit['study_year'] =$data;
            $data_edit['course_message'][] = $data_childs;  
        }       
        $data_edit = json_encode($data_edit);
        return response()->json($data_edit);
        
    }
    
    public function doEditTime(Request $request){
        
        $lessons = $request->input('lessons');//课程设置json数据
        $mid = $request->input('mid');
        $school_id = $request->input('school_id');
        $school_year = $request->input('school_year');
        $school_timestart = $request->input('school_timestart');
        $school_timeend = $request->input('school_timeend');
        $term = $request->input('term');
        $oUser = \Auth::user();
        if($oUser->plan == 0){
            $status = 0;
        }else{
            $status = 1;
        }
      
        
        foreach ($lessons as $lesson){
            $data[] = array('id'=>$lesson['id'],'school_id'=>$school_id,'school_year'=>$school_year,'school_timestart'=>$school_timestart,'school_timeend'=>$school_timeend,'type'=>$lesson['type'],'course_number'=>$lesson['course_number'],'course_timestart'=>$lesson['course_timestart'],'course_timeend'=>$lesson['course_timeend'],'term'=>$term,'status'=>$status,'mainid'=>$mid);
        }
        $obj = SchoolTime::find($mid);
        $obj->school_year = $school_year;
        $obj->school_timestart = $school_timestart;
        $obj->school_timeend = $school_timeend;
        $obj->term = $term;
        $ress = $obj->save();
        //var_dump($data);die;
        /*$data = array('0'=>array('id'=>'32','school_year'=>'2011','school_timestart'=>'2017-08-01 10:16:57','school_timeend'=>'2017-08-01 10:16:57','type'=>'1','course_number'=>'3','course_timestart'=>'2017-08-01 10:16:57','course_timeend'=>'2017-08-01 10:16:57','create_time'=>'2017-08-01 10:16:57','term'=>'2'),
                '1'=>array('id'=>'33','school_year'=>'2011','school_timestart'=>'2017-08-01 10:16:57','school_timeend'=>'2017-08-01 10:16:57','type'=>'1','course_number'=>'2','course_timestart'=>'2017-08-01 10:16:57','course_timeend'=>'2017-08-01 10:16:57','create_time'=>'2017-08-01 10:16:57','term'=>'2'),
                '2'=>array('id'=>'34','school_year'=>'2011','school_timestart'=>'2017-08-01 10:16:57','school_timeend'=>'2017-08-01 10:16:57','type'=>'1','course_number'=>'1','course_timestart'=>'2017-08-01 10:16:57','course_timeend'=>'2017-08-01 10:16:57','create_time'=>'2017-08-01 10:16:57','term'=>'2')
            );*/
//        dd($data);
//        $id = '32';
//        $obj = SchoolTime::find($id);
//            $obj->school_year = '2019';
//            $obj->save;die;
//        SchoolTime::where('id','32')->update(['school_year'=>'2019']);die;
        foreach ($data as $datas){
            $id = $datas['id'];
            if($id!='0'){
                $obj = SchoolTime::find($id);
                
                $obj->school_year = $datas['school_year'];
                $obj->school_timestart = $datas['school_timestart'];
                $obj->school_timeend = $datas['school_timeend'];
                $obj->type = $datas['type'];
                $obj->course_number = $datas['course_number'];
                $obj->course_timestart = $datas['course_timestart'];
                $obj->course_timeend = $datas['course_timeend'];
                $obj->term = $datas['term'];
                $res = $obj->save();
            }else{
                $obj = new SchoolTime();
                $obj->school_id = $school_id;
                $obj->school_year = $datas['school_year'];
                $obj->school_timestart = $datas['school_timestart'];
                $obj->school_timeend = $datas['school_timeend'];
                $obj->type = $datas['type'];
                $obj->course_number = $datas['course_number'];
                $obj->course_timestart = $datas['course_timestart'];
                $obj->course_timeend = $datas['course_timeend'];
                $obj->term = $datas['term'];
                $obj->mainid = $datas['mainid'];
                $obj->status = $datas['status'];
                
                $res = $obj->save();
            }
            if($ress){
                $addsucs = array('pid'=>$mid,'school_year'=>$school_year,'term'=>$term,'state'=>'true');
                $addsuc = json_encode($addsucs);
                return response()->json($addsuc);
            }
            
            //return $mid.'-'.$school_year.'-'.$term;
            
            
        }
        
    }
    
    public function deleteTime(Request $request){
        $mid = $request->input('mid');
        //$mid = '51';
        $res = SchoolTime::where('id',$mid)->delete();
        //var_dump($res);
        if($res){
            $res_child = SchoolTime::where('mainid',$mid)->delete();
            if($res_child){
                $static = array('state'=>'true');
                $static = json_encode($static);
                return response()->json($static);
                
                //echo 'true';
            }
        }
       
        
    }
    public function trash(){
        $oObjs = SchoolTime::where('mainid','0')->onlyTrashed()->get();
    }
    public function doTrash($id){
        $type = \Request::input('type');
        if($type){
//            var_dump($type);
            echo "1";
            $oNotice = SchoolTime::withTrashed()->find($id);
            $oNotice->restore();
//            $oNotice->save();
            return redirect()->back();
        }else{
            echo "2";
            $ids=explode(',',$id);
            foreach($ids as $val){
                $oNotice = SchoolTime::withTrashed()->find($val);
                $oNotice->forceDelete();
            }
            return redirect()->back();
        }
//        return back();
    }
    
    public function isChoice(Request $request){
        $school_id = $request->input('school_id');
        $sid = $request->input('sid');
        //echo $school_id.'='.$sid;
        //$sid = '51';
        $SchoolTime = SchoolTime::where('school_id',$school_id)->where('mainid','0')->get();
        foreach($SchoolTime as $schooltimes){
            $obj = SchoolTime::find($schooltimes->id);
            if($schooltimes->id == $sid){     
                $obj->is_choice = '1';
            }else{
                $obj->is_choice = '0';
            }
            
            $obj->save();
            
        }
        return $sid;
        
    }
    
    public function update(Request $request)
    {

        $id=$this->school_id;
        $oSchool = School::find($id);
        if($oSchool) {
            if ($request->hasFile('logo')) {
                if ($request->file('logo')->isValid()){
                    $file = $request->file('logo');
                    $file_name = time().str_random(6).$file->getClientOriginalName();
                    \Storage::disk('oss')->put($file_name, file_get_contents($file->getRealPath()));
                    if(\Storage::disk('oss')->exists($file_name)) {
                        $oSchool->logo = $file_name;
                    } else {
                        return back()->withInput()->withErrors(['msg' => '文件上传失败',]);
                    }
                } else {
                    return back()->withInput()->withErrors(['msg' => '文件上传失败',]);
                }
            }
            $arr=array(
                $request->input('xmz_rank'),
                $request->input('ztz_rank'),
                $request->input('zy_rank'),
                $request->input('sp_rank'),
                $request->input('ly_rank'),
                $request->input('kp_rank'),
                $request->input('kt_rank'),
            ); 
			$arr1=array(
                $request->input('xmz_up'),
                $request->input('ztz_up'),
                $request->input('zy_up'),
                $request->input('sp_up'),
                $request->input('ly_up'),
                $request->input('kp_up'),
                $request->input('kt_up'),
            );  
			$arr2=array(
                $request->input('xmz_down'),
                $request->input('ztz_down'),
                $request->input('zy_down'),
                $request->input('sp_down'),
                $request->input('ly_down'),
                $request->input('kp_down'),
                $request->input('kt_down'),
            );
            $total=0;
            foreach($arr as $val){
                $total=$total+intval($val);
            }
            if($total>100){
                return back()->withInput()->withErrors([
                    'msg' => '成绩权重之和不能大于100',
                ]);
            }
            //$admin_user_id=$request->input('admin_user_id',0);
            //$oSchool->server_user_id = $request->input('server_user_id');
            //$oSchool->admin_user_id = $request->input('admin_user_id');
            $oSchool->score_rank = json_encode($arr);//成绩权重
            $oSchool->up = json_encode($arr1);//成绩权重
            $oSchool->down = json_encode($arr2);//成绩权重
            $oSchool->pass = $request->input('pass');
            $oSchool->name = $request->input('name');
            $oSchool->short_name = $request->input('short_name');
            $oSchool->province = $request->input('province');
            $oSchool->city = $request->input('city');
            $oSchool->address = $request->input('address');
            $oSchool->identifier = $request->input('identifier');
            $oSchool->email_suffix = $request->input('email_suffix');
            $oSchool->host_suffix = $request->input('host_suffix');
            $oSchool->start_time = $request->input('start_time');
            $oSchool->end_time = $request->input('end_time');
            $oSchool->contact_man = $request->input('contact_man','');
            $oSchool->contact_phone = $request->input('contact_phone','');
            $oSchool->is_over = (time()-strtotime($oSchool->end_time))<0? 0: 1;
            $oSchool->save();
            //User::where('id',$admin_user_id)->update(['school_id'=>$this->school_id]);//更新用户所属学校
            //拷贝教学方案
            return back()->withInput()->withErrors(['msg' => '修改成功',]);
        } else {
            $msg = ["msg"=> ["参数错误，非法操作"], ];
            return back()->withInput()->withErrors($msg);
        }
    }

    public function pluginConfig($name){

        $plugin = plugin($name);

        if ($plugin && $plugin->isEnabled() && $plugin->hasConfigView()) {
            $id = \Session::get('school_id');
//            $data = Option::where('sid',$id)->where('option_name',$name)->first();yuanlaide 
            $data = Option::where('sid',$id)->where('option_value',$name)->first();
            return $plugin->getConfigView($data);
        } else {
            abort(404);
        }
    }
}
