<?php

namespace App\Http\Controllers\Administrator\School;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use App\Models\Group;
use App\Models\School;
use App\Models\Squad;
use App\Models\GroupStudent;
use App\Models\GroupScore;

class GroupController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $aSearch = [];
        $type=0;
        $name= $school_name=$squad_name=$type=$where='';
        \Request::has('school_name') &&  $aSearch['school_name']=$school_name = $request->input('school_name');
        \Request::has('name') &&  $aSearch['name']=$name = $request->input('name');
        \Request::has('squad_name') &&  $aSearch['squad_name']=$squad_name = $request->input('squad_name');
        \Request::has('type') &&  $aSearch['type']= $type = $request->input('type');
        $oObjs=Group::where(['type'=>$type]);
        $school_id=$request->input('school_id',0);
        $squad_id=$request->input('squad_id',0);
        if($school_id>0){
            $oObjs->where('school_id',$school_id);
        }
        if($squad_id>0){
            $oObjs->where('squad_id',$squad_id);
        }
        if($name!=''){
            $oObjs = $oObjs->where('name','like', '%'.$name.'%');
        }
        if($squad_name!=''){
            $squad_id = Squad::where('name','like', '%'.$squad_name.'%')->pluck('id');
            $oObjs = $oObjs->whereIn('squad_id',$squad_id);
        }
        if($school_name!=''){
            $school_id=School::where('name','like', '%'.$school_name.'%')->pluck('id');
            $oObjs = $oObjs->whereIn('school_id',$school_id);
        }
        $oObjs=$oObjs->orderBy('id','desc')->with('school','squad');
		$num['a'] = $oObjs->count();
		$oObjs=$oObjs->paginate(20);
		$num['b'] = $oObjs->count();
        foreach($oObjs as &$val){
            $all_score=GroupScore::where(['group_id'=>$val->id])->sum('score');
            $val->totalScore=$all_score;
        }
		if (view()->exists(session('mode').'.school.group.list')){
			return View(session('mode').'.school.group.list', compact('oObjs','type','aSearch','num'));
		}else{
			return View('default.school.group.list', compact('oObjs','type','aSearch','num'));
		}
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $oGroup = new Group();
        $oGroup->type = $request->input('type');
        $oGroup->name = $request->input('name');
        $oGroup->squad_id = $request->input('squad_id');
        $oUser = \Auth::user();
        if($oUser->plat === 0) { // 系统管理员
            $oGroup->Group_id = $request->input('Group_id');
        } else {
            $oGroup->Group_id = $oUser->Group_id;
        }
        
        $oGroup->save();
        return response()->json(null);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        if(!$id){
            return back()->withInput()->withErrors(['msg' => '参数缺失',]);
        }
        $oObj = Group::where(['id'=>$id])->first();
        if($oObj){
            $group_student=GroupStudent::where(['group_id'=>$oObj->id])->with('student')->get();
            $group_score=GroupScore::where(['group_id'=>$oObj->id])->with('student')->get();
            $all_score=GroupScore::where(['group_id'=>$oObj->id])->sum('score');
            $oObj->totalScore=$all_score;
			if (view()->exists(session('mode').'.school.group.show')){
				return View(session('mode').'.school.group.show', compact('oObj','group_student','squad_id','group_score'));
			}else{
				return View('default.school.group.show', compact('oObj','group_student','squad_id','group_score'));
			}
        }
        else {
            return back()->withInput()->withErrors(['msg' => '分组不存在',]);
        }
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //未完成，不仅仅如此
        $oGroup = Group::find($id);
        if($oGroup) {
           $oGroup->type = $request->input('type');
            $oGroup->name = $request->input('name');
            $oGroup->squad_id = $request->input('squad_id');
            $oUser = \Auth::user();
            if($oUser->plat === 0) { // 系统管理员
                $oGroup->Group_id = $request->input('Group_id');
            } else {
                $oGroup->Group_id = $oUser->Group_id;
            }
            $oGroup->save();
            return response()->json(null);
        } else {
          $msg = [
              "msg"=> ["参数错误，非法操作"],
          ];
          return response()->json($msg)->setStatusCode(422);
        }
    }

    /**
     * Remove the specified resource from storage.
     * 
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        if(!$id) {
            $msg = [
              "msg"=> ["参数错误，非法操作"],
          ];
          return response()->json($msg)->setStatusCode(422);
        } else {
            Group::find($id)->delete();
            return response()->json(null);
        }
    }
    // 批量导入学生
    public function inportStudent(Request $request) {

    }
    //
    // 批量管理学生
    public function updateStudent(Request $request) {

    }
}
