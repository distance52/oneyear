<?php

namespace App\Http\Controllers\Api\Teacher;

use App\Models\GroupStudent;
use App\Models\SquadStruct;
use Guzzle\Service\Resource\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use App\Models\Group;
use App\Models\Student;
class GroupController extends Controller
{
    /**
     * 获取某个班级的分组
     * @param $squad_id
     * @return \Illuminate\Http\JsonResponse
     */
    public function getGroup($squad_id,$type)
    {
        $groupInfo = Group::where(['squad_id'=>$squad_id,'type'=>$type])->get(['id','name']);
        return response()->json($groupInfo);
    }

    /**
     * 更新某个学生的分组
     * @return \Illuminate\Http\JsonResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function saveGroup($squad_id,$type){
        $id=\Request::input('id',0);
        $group_id=\Request::input('group_id',0);
//        $group=Group::where('id',$group_id)->first();
        $squad_list = SquadStruct::where('struct_id',$id)->where('type',1)->pluck('squad_id')->toArray();

//        return response()->json($squad_list)->setStatusCode(400);
//        $squad=Student::where('id',$id)->value('squad_id');
        if( !in_array($squad_id, $squad_list)){
            $msg = [
                "msg"=> ["该学生不在这个班级"],
            ];
            return response()->json($msg)->setStatusCode(400);
        }

        //如果传递过来的分组是0，则表示把该学生移出分组
        if($group_id==0){
            GroupStudent::where(['student_id'=>$id,'type'=>$type])->delete();
        }
        else{
//            $oGroupStudent=GroupStudent::where(['student_id'=>$id,'type'=>$type])->first();
            $oGroupStudent=GroupStudent::where(['student_id'=>$id,'type'=>$type])
                ->with(['group' => function($query) use($squad_id){
                $query->where('squad_id',$squad_id);
            }])->orderBy('id','desc')->first();

            if(isset($oGroupStudent->group)){
                $oGroupStudent->group_id = $group_id;
                $oGroupStudent->save();
            }
            else{
                $oGroupStudent=new GroupStudent();
                $oGroupStudent->type=$type;
                $oGroupStudent->student_id=$id;
                $oGroupStudent->group_id=$group_id;
                $oGroupStudent->save();
            }
        }
        return response()->json(null)->setStatusCode(200);
//        $msg = [
//            "msg"=> "操作成功",
//            'status'=>'success'
//        ];
//        return response()->json($msg)->setStatusCode(200);
    }
}
