<?php

namespace App\Http\Controllers\Api\Administrator;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use App\Models\Teaching\Node;
use App\Models\Tag;

class NodeController extends Controller
{
    //
    public function create(Requests\NodeRequest $request) {
        $oUser = \Auth::user();
        $oObj = new Node();
        $oObj->school_id = $oUser->school_id;
        $oObj->user_id = $oUser->id;
        $oObj->type = $request->input('type', 0);
        $oObj->name = $request->input('name', '');
        $oObj->info_id = $request->input('info_id', 0);
        $oObj->exampaper_id = $request->input('exampaper_id', 0);
        $oObj->desc = $request->input('desc', '');
        $oObj->times = $request->input('times', '0'); // 课时
        $oObj->show_name = $request->input('show_name', '');
        $oObj->show_time = $request->input('show_time')? $request->input('show_time'): date('Y-m-d H:i:s');
        $oObj->save();
        // 后期删掉
        $oObj->id = Node::where('user_id',$oUser->id)->orderBy('id','desc')->value('id');
        if($request->input('tags','')) {
            $oTag = new Tag;
            $oTag->syncTags($request->input('tags',''), $oObj);
        }
        return response()->json(array('id'=>$oObj->id,'name'=>$oObj->name));
    }
}
