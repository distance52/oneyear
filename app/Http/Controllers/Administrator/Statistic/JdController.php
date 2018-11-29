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

class JdController extends Controller
{

    public function jd(){
        return view('default.statistic.jd.index');
    }

    public function select(){
        $arr = array('基地公共资源使用情况','基地入项资源使用情况','项目进展情况','项目类型分析','导师类型分析','导师能力分析','学生能力分析','创业学员日活跃度');
        return response()->json(array('msg'=>'ok','data'=>$arr,'status'=>true)) ;
    }

    public function resource(Request $request){

        $type = $request->input('type',0);
        $start = $request->input('start',0);
        $end = $request->input('end',0);

        $date = $this->transTime($start, $end);

        $data =[] ;
//        if($type == 0){
//
//        }elseif ($type == 1) {
//
//        }elseif ($type == 2){
//
//        }elseif ($type == 3){
//
//        }elseif ($type == 4){
//
//        }elseif ($type == 5){
//
//        }elseif ($type == 6){
//
//        }elseif ($type == 7){
//
//        }
        $field1 =[];
        $field2 =[];
        $field3 =[];
        $field4 =[];
        foreach ($date as $day){
            $field1[] = rand(10,199);
            $field2[] = rand(10,99);
            $field3[] = rand(10,120);
            $field4[] = rand(10,111);
        }
        $data = array('date'=>$date , 'field1' => $field1, 'field2' => $field2, 'field3' => $field3, 'field4' => $field4);
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