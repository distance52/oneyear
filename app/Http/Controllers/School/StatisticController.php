<?php

namespace App\Http\Controllers\School;


use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Teacher;
use App\Models\Teaching\Plan;
use App\Models\Teaching\Cell;
use App\Models\Teaching\Module;
use App\Models\Teaching\Node;
use App\Models\File;
use App\Models\School;
use App\Models\Squad;
use Illuminate\Support\Facades\Redis as Redis;

class StatisticController extends BaseController
{

    public function index(){
//        dd(\Session::all());
        return view('default.other.statistic.index');
    }

    public function analysis(){

        $school_id = $this->school_id;
//        $laest_login_count = SqlLog::where('type','login')->where('created_at','>',date('Y-m-d H:i:s' ,time()-3600))->groupBy('user_id')->get()->count();
        $today_register_count = User::where('school_id',$school_id)->where('created_at','>',date('Y-m-d H:i:s',strtotime("today")))->count();
        $today_register_teacher_count = User::where('school_id',$school_id)->where('plat',2)->where('created_at','>',date('Y-m-d H:i:s',strtotime("today")))->count();
        $today_register_student_count = User::where('school_id',$school_id)->where('plat',3)->where('created_at','>',date('Y-m-d H:i:s',strtotime("today")))->count();
        $today_register_squad_count = Squad::where('school_id',$school_id)->where('created_at','>',date('Y-m-d H:i:s',strtotime("today")))->count();


        $adminKey = "dataStatis:school:{$school_id}:".date('Ymd');
        if(Redis::exists($adminKey)){
            $arr = json_decode(Redis::get($adminKey),true);
            $count = $arr;
//            $news = $arr['news'];
        }else{
            $count = [
                'plans' => Plan::where('school_id',$school_id)->count(),
                'cells' => Cell::where('school_id',$school_id)->count(),
                'modules' => Module::where('school_id',$school_id)->count(),
                'nodes' => Node::where('school_id',$school_id)->count(),
                'files' => File::where('school_id',$school_id)->count(),
                'videos' => File::where('school_id',$school_id)->where('type',1)->count(),
                'pics' => File::where('school_id',$school_id)->where('type',3)->count(),
                'words' => File::where('school_id',$school_id)->where('type',6)->count(),
                'users' => User::where('school_id',$school_id)->count(),
                'teachers' => User::where('school_id',$school_id)->where('plat',2)->count(),
                'students' => User::where('school_id',$school_id)->where('plat',3)->count(),
            ];
        }

        $arr = "<ul>
		            	<li style=\"width: 207.6px;\"><div>今日注册数</div><div class=\"w-statis-num\">{$today_register_count}</div>
			            	<div>用户总数{$count['users']}+$today_register_count</div>
			            </li>
		            	<li style=\"width: 207.6px;\"><div>今日新增教师</div><div class=\"w-statis-num\">{$today_register_teacher_count}</div>
			            	<div>教师总数{$count['teachers']}+$today_register_teacher_count</div>
			            </li>
		            	<li style=\"width: 207.6px;\"><div>今日新增学生</div><div class=\"w-statis-num\">{$today_register_student_count}</div>
			            	<div>学生总数{$count['students']}+$today_register_student_count</div>
		            	</li>
		            	
		            </ul>";
//        $a ="<li style=\"width: 207.6px;\"><div>今日新增班级</div><div class=\"w-statis-num\">{$today_register_squad_count}</div><div>班级总数{$news['num3']}+$today_register_squad_count</div></li>";

        return $arr;
    }

    public function resource(Request $request){

        $type = $request->input('type',0);
        $start = $request->input('start',0);
        $end = $request->input('end',0);
        $school_id = $this->school_id;
        $date = $this->transTime($start, $end);
        $key = "dataStatis:school:{$school_id}:";
        $data =[] ;
        if($type == 'user'){

            $user =[];
            $school =[];
            $teacher =[];
            $student =[];
            foreach ($date as $day){

                if(Redis::exists($key.$day)){
                    $arr = json_decode(Redis::get($key.$day), true);

                    $user[] = $arr['users'];
//                    $school[] = $arr['schools'];
                    $teacher[] = $arr['teachers'];
                    $student[] = $arr['students'];
                }else{
                    $user[] = 0;
//                    $school[] = 0;
                    $teacher[] = 0;
                    $student[] = 0;
                }
            }
            $data = array('date'=>$date , 'user' => $user, 'school' => $school, 'teacher' => $teacher, 'student' => $student);

        }elseif ($type == 'resource') {

            $file =[];
            $image =[];
            $doc =[];
            $video =[];
            foreach ($date as $day){

                if(Redis::exists($key.$day)){
                    $arr = json_decode(Redis::get($key.$day), true);
                    $file[] = $arr['files'];
                    $image[] = $arr['pics'];
                    $doc[] = $arr['words'];
                    $video[] = $arr['videos'];
                }else{
                    $file[] = 0;
                    $image[] = 0;
                    $doc[] = 0;
                    $video[] = 0;
                }
            }
            $data = array('date'=>$date , 'file' => $file, 'image' => $image, 'doc' => $doc, 'video' => $video);

        }elseif ($type == 'plan'){


            $plan =[];
            $cell =[];
            $module =[];
            $node =[];
            foreach ($date as $day){

                if(Redis::exists($key.$day)){
                    $arr = json_decode(Redis::get($key.$day), true);
                    $plan[] = $arr['plans'];
                    $cell[] = $arr['cells'];
                    $module[] = $arr['modules'];
                    $node[] = $arr['nodes'];
                }else{
                    $plan[] = 0;
                    $cell[] = 0;
                    $module[] = 0;
                    $node[] = 0;
                }
            }
            $data = array('date'=>$date , 'plan' => $plan, 'cell' => $cell, 'module' => $module, 'node' => $node);

        }elseif ($type == 'data'){

        }
        return response()->json(array('status'=>true , 'data'=> $data ,'msg'=>'ok'));

    }

    protected function transTime($start_time , $end_time){
        $start_date = date('Y-m-d', strtotime($start_time));
        $end_date = date('Y-m-d', strtotime($end_time));
        $date = [];
        for($i = strtotime($start_date); $i <= strtotime($end_date); $i += 86400) {
            $date[] = date("Ymd", $i);
        }
        return $date;

    }
}