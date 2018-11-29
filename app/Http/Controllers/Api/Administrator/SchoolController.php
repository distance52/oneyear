<?php

namespace App\Http\Controllers\Api\Administrator;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use App\Models\Squad;
class SchoolController extends Controller
{
    /**
     * 获取一个学校所有班级列表
     * @param $school_id
     */
    public function getSquadList($school_id){
        $squad=Squad::where('school_id',$school_id)->get(['id','name']);
        return response()->json($squad);
    }
}
