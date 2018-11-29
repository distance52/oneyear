<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/4/23
 * Time: 18:36
 */

namespace App\Http\Controllers\Api;

use App\Models\Specialty;
use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;

class SpecialtyController extends Controller
{
    /**
     * 根据学校id获取某个学校，某个id的子类
     * @return \Illuminate\Http\JsonResponse
     */
    public function getListByPid(Request $request,$school_id){
        $parentid = $request->input('parentid',0);
        $data=Specialty::where(['school_id'=>$school_id,'parentid'=>$parentid])->get();
        return response()->json($data);
    }
}