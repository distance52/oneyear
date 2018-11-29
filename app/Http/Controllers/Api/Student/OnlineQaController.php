<?php

namespace App\Http\Controllers\Api\Student;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use App\Models\NodeQa;
use App\Models\Teaching\Plan;
use App\Models\StudentPoint;
use App\Models\SquadStruct;
use App\Models\Teaching\PlanStruct;
use App\Http\Controllers\Student\BaseController as BaseController;

// 在线答疑API
class OnlineQaController extends BaseController
{
    //
    public function run($planStruct_id, $type,$squad_id) {
        // dd($squad_id);
        $oUser = \Auth::user();
        $oPlanStruct = PlanStruct::find($planStruct_id);
        // $squad_id=0;
        if($oUser->plat == 2 || $oUser->plat == 1) {
            $oUser->load('teacher');
            $teacher_id = $oUser->teacher->id;
			// $squad_id =SquadStruct::where('type',2)->where('struct_id',$teacher_id)->pluck('squad_id');
        } elseif($oUser->plat == 3) {
            $oUser->load('student');
            $student_id = $oUser->student->id;
            // $squad_id =SquadStruct::where('type',1)->where('struct_id',$student_id)->pluck('squad_id');
        }

//        if(\Request::isMethod('post')) {
//            $this->postRun($oUser, $squad_id, $oPlanStruct->module_id, $type);
//        }
//        $oNodeQa = $this->getRun($oUser, $squad_id, $oPlanStruct->module_id, $type);
        $data_sub = 0;
        if($type == 2) {
            if($oNodeQa) {
                foreach($oNodeQa as $qa) {
                    if($qa->user_id == \Auth::user()->id) {
                        $data_sub = 1;
                        break;
                    }
                }
            }
        }
		if (view()->exists(session('mode').'.studentPlat.qa')){
			return View(session('mode').'.studentPlat.qa', compact('oNodeQa','oUser','type','oPlanStruct','squad_id','data_sub','planStruct_id'));
		}else{
			return View('default.studentPlat.qa', compact('oNodeQa','oUser','type','oPlanStruct','squad_id','data_sub'));
		}
    }

    

    public function getRun($oUser, $squad_id, $module_id, $type) {
        if($type == 3) {

            return $oNodeQa = NodeQa::where('squad_id', $squad_id)
                ->where('module_id', $module_id)
                ->where('is_black', 0)
                ->where('parent_id', 0)
                ->where('type', 3) // 类型
                ->orderBy('id','desc')
                // ->take(10)
                ->with('user')
                ->get()
                ->map(function($item, $key) use ($module_id) {
                    $item->lists = NodeQa::where('parent_id', $item->id)
                        ->where('module_id', $module_id)
                        ->where('type', 3)
                        ->where('is_black', 0)
                        ->get();
                    return $item;
                });
        } else {

            return $oNodeQa = NodeQa::where('squad_id', $squad_id)
                ->where('module_id', $module_id)
                ->where('is_black', 0)
                ->where('type', $type) // 类型
                ->orderBy('id','desc')
                // ->take(10)
                ->with('user')
                ->get();
            }

    }

    // post
    private function postRun($oUser, $squad_id, $module_id, $type) {
		// foreach($squad_ids as $squad_id){
			
        $oNodeQa = new NodeQa;
        $oNodeQa->node_id = 0;
        $oNodeQa->squad_id = $squad_id;
        $oNodeQa->title = \Request::input('title','');
        $oNodeQa->content = \Request::input('content','');
        $oNodeQa->plan_id = \Request::input('plan_id',0);
        $oNodeQa->cell_id = \Request::input('cell_id',0);
        $oNodeQa->module_id = $module_id;
        $oNodeQa->node_id = \Request::input('node_id',0);
        $oNodeQa->user_id = $oUser->id;
        $oNodeQa->is_reply = \Request::input('is_reply', 0);
        $oNodeQa->type = $type;
        $oNodeQa->score = \Request::input('score', 0);
        $oNodeQa->ip = \Request::getClientIp();
        $oNodeQa->parent_id = \Request::input('parent_id',0);
        //
        $oStudentPoint = new StudentPoint();
        $sign ='s'. $oNodeQa->squad_id.'-n'.$oNodeQa->node_id.'-u'.\Auth::user()->id.'-t'.$oNodeQa->type.'-p'.$oNodeQa->parent_id;
        switch ($type) {
            case 1:
                $oStudentPoint->setPoints('interact', $sign,$squad_id);
                break;
            case 2:
                $oStudentPoint->setPoints('judge',$sign,$squad_id);
                break;
            case 3:
                if($oNodeQa->parent_id) {
                    $oStudentPoint->setPoints('answer_qa',$sign,$squad_id);
                } else {
                    $oStudentPoint->setPoints('ask_qa',$sign,$squad_id);
                }
                break;
        }

        //
        $imgs = [];
        if (\Request::hasFile('imgs')) {
            foreach (\Request::file('imgs') as $file) {
                if ($file->isValid()){

                   $file_name = time().str_random(6).$file->getClientOriginalName();
                   \Storage::disk('oss')->put($file_name, file_get_contents($file->getRealPath()));
                   if(\Storage::disk('oss')->exists($file_name)) {
                        $exp = new \DateTime(date("Y-m-d H:i:s",strtotime("+3 year")));
                        $url = \AliyunOSS::getUrl($file_name,$exp, $bucket = config('filesystems.disks.oss.bucket'));
                        $imgs[] = [
                            'src'=>$file_name,
                            'url'=>$url
                        ];
                   }
                }
            }
            $oNodeQa->imgs = $imgs;
        }
        //
        $oNodeQa->save();
		}

    // }
}
