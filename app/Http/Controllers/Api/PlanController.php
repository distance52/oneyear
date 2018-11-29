<?php

namespace App\Http\Controllers\Api;

use Guzzle\Service\Resource\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use App\Models\Teaching\Plan;
use App\Models\Teaching\Cell;
use App\Models\Teaching\Module;
use App\Models\Teaching\Node;

class PlanController extends Controller
{
    //
    // 修改结构字段
    public function postStruct($id) {
        $oObj = Plan::whereId($id)->first();

        if($oObj && \Request::has('struct')) {
            //
            $oUser = \Auth::user();
            if(!($oUser->plat == 0 || (($oUser->plat ==1 || $oUser->plat ==2) && $oUser->school_id == $oObj->school_id))) {
                // 返回错误
                $msg = [
                        "custom-msg"=> ["你没有修改权限,出错"],
                    ];
                    return response()->json($msg)->setStatusCode(422);
            }
			if(!($oUser->plat<2 || $oObj->is_lock==0)) {
					// 返回错误页面
					$msg = [
                        "custom-msg"=> ["方案已经被锁定，不允许修改！"],
                    ];
                    return response()->json($msg)->setStatusCode(422);
				}
			if(!($oUser->plat < 2 || $oObj->is_private==0 || $oUser->id==$oObj->user_id)){
				  $msg = [
                        "custom-msg"=> ["方案是私人的，不允许修改！"],
                    ];
                    return response()->json($msg)->setStatusCode(422);
			}
            //
            $struct = \Request::input('struct');
            if($struct){
                $struct = json_decode($struct, true);
                if(is_array($struct) && $oObj->struct != $struct) {
                    $oObj->struct = $struct;
                    $oObj->save();
                    // 插入到新的表里面
                    $oObj->insertPlanStruct();
                } else {
                    $msg = [
                        "custom-msg"=> ["struct不是合法的json结构,出错"],
                    ];
                    return response()->json($msg)->setStatusCode(422);
                }
            }
            return response()->json(null);
        } else {
            $msg = [
                "custom-msg"=> ["参数错误，非法操作"],
            ];
            return response()->json($msg)->setStatusCode(422);
        }
    }
}
