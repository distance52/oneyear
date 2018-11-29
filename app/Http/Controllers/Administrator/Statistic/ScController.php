<?php

namespace App\Http\Controllers\Administrator\Statistic;

use Illuminate\Http\Request;

use Illuminate\Routing\UrlGenerator;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Redis as Redis;
use App\Models\Tag;
use App\Models\SqlLog;
use App\Models\User;
use App\Models\Squad;
use App\Models\Teaching\Plan;
use App\Models\Teaching\Cell;
use App\Models\Teaching\Module;
use App\Models\Teaching\Node;
use App\Models\File;
use App\Models\School;

class ScController extends Controller
{

    public function index(){
        return redirect("/statistic/scjy");
    }

    public function scjy(){
        return view('default.statistic.sc.index');
    }

    public function analysis(){

        $laest_login_count = SqlLog::where('type','login')->where('created_at','>',date('Y-m-d H:i:s' ,time()-3600))->groupBy('user_id')->get()->count();
        $today_register_count = User::where('created_at','>',date('Y-m-d H:i:s',strtotime("today")))->count();
        $today_register_teacher_count = User::where('plat',2)->where('created_at','>',date('Y-m-d H:i:s',strtotime("today")))->count();
        $today_register_student_count = User::where('plat',3)->where('created_at','>',date('Y-m-d H:i:s',strtotime("today")))->count();
        $today_register_squad_count = Squad::where('created_at','>',date('Y-m-d H:i:s',strtotime("today")))->count();

        $adminKey = "dataStatis:admin:".date('Ymd');
        if(Redis::exists($adminKey)){
            $arr = json_decode(Redis::get($adminKey),true);
            $count = $arr['count'];
            $news = $arr['news'];
        }else{
            $count = [
                'plans' => Plan::count(),
                'cells' => Cell::count(),
                'modules' => Module::count(),
                'nodes' => Node::count(),
                'files' => File::count(),
                'videos' => File::where('type',1)->count(),
                'pics' => File::where('type',3)->count(),
                'words' => File::where('type',6)->count(),
                'users' => User::count(),
                'teachers' => User::where('plat',2)->count(),
                'students' => User::where('plat',3)->count(),
                'schools' => School::count(),
            ];
            $news = [
                'teachers' => User::where('plat',2)->orderBy('id','desc')->take(5)->with('school')->get(),
                'num1' => User::where('plat',2)->with('school')->count(),
                'students' => User::where('plat',3)->orderBy('id','desc')->take(5)->with('school')->get(),
                'num2' => User::where('plat',3)->with('school')->count(),
                'squads' => Squad::orderBy('id','desc')->take(5)->with('school')->get(),
                'num3' => Squad::with('school')->count(),
            ];
        }

        $arr = "<ul>
		            <li style=\"width: 207.6px;\"><div>最近活跃用户数</div><div class=\"w-statis-num\">{$laest_login_count}</div>
		            	<div></div>
		            	</li>
		            	<li style=\"width: 207.6px;\"><div>今日注册数</div><div class=\"w-statis-num\">{$today_register_count}</div>
			            	<div>用户总数{$count['users']}+$today_register_count</div>
			            </li>
		            	<li style=\"width: 207.6px;\"><div>今日新增教师</div><div class=\"w-statis-num\">{$today_register_teacher_count}</div>
			            	<div>教师总数{$count['teachers']}+$today_register_teacher_count</div>
			            </li>
		            	<li style=\"width: 207.6px;\"><div>今日新增学生</div><div class=\"w-statis-num\">{$today_register_student_count}</div>
			            	<div>学生总数{$count['students']}+$today_register_student_count</div>
		            	</li>
		            	<li style=\"width: 207.6px;\"><div>今日新增班级</div><div class=\"w-statis-num\">{$today_register_squad_count}</div>
			            	<div>班级总数{$news['num3']}+$today_register_squad_count</div>
		            	</li>
		            </ul>";

        return $arr;
    }

    public function resource(Request $request){

        $type = $request->input('type',0);
        $start = $request->input('start',0);
        $end = $request->input('end',0);

        $date = $this->transTime($start, $end);
        $key = "dataStatis:admin:";
        $data =[] ;
        if($type == 'user'){


            $user =[];
            $school =[];
            $teacher =[];
            $student =[];
            foreach ($date as $day){

                if(Redis::exists($key.$day)){
                    $arr = json_decode(Redis::get($key.$day), true);

                    $user[] = $arr['count']['users'];
                    $school[] = $arr['count']['schools'];
                    $teacher[] = $arr['count']['teachers'];
                    $student[] = $arr['count']['students'];
                }else{
                    $user[] = 0;
                    $school[] = 0;
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
                    $file[] = $arr['count']['files'];
                    $image[] = $arr['count']['pics'];
                    $doc[] = $arr['count']['words'];
                    $video[] = $arr['count']['videos'];
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
                    $plan[] = $arr['count']['plans'];
                    $cell[] = $arr['count']['cells'];
                    $module[] = $arr['count']['modules'];
                    $node[] = $arr['count']['nodes'];
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