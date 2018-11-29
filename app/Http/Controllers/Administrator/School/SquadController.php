<?php

namespace App\Http\Controllers\Administrator\School;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use App\Models\Squad;
use App\Models\School;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\SquadStudent;
use App\Models\SquadStruct;
class SquadController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $aSearch = [];
        $name= $school=$income_year=$type=$teacher=$where='';
        //var_dump(\Request::has('name'));
        \Request::has('name') &&  $name = \Request::input('name');
        \Request::has('school') && $school = \Request::input('school');
        \Request::has('income_year') && $income_year = \Request::input('income_year');
        \Request::has('teacher') && $teacher = \Request::input('teacher');
        \Request::has('type') && $type = \Request::input('type');
        $school_id= \Request::input('school_id',0);
        $teacher_id= \Request::input('teacher_id',0);
        $oObjs=Squad::orderBy('id','desc');
        if($name!=''){
            $aSearch['name'] = $name;
            $oObjs->where("name","like",'%'.$name.'%');
        }
        if($school!=''){
            $aSearch['school'] = $school;
            $school_id=School::where('name','like','%'.$school.'%')->pluck('id')->toArray();
            $oObjs->whereIn("school_id",$school_id);
        }
        if($school_id>0){
            $oObjs->where("school_id",$school_id);
        }
        if($teacher_id>0){
            $oObjs->where("teacher_id",$teacher_id);
        }
        if($income_year!=''){
            $aSearch['income_year'] = $income_year;
            $oObjs->where("income_year","=",$income_year);
        }
        if($teacher!=''){
            $aSearch['teacher']=$teacher;
            $teacher_id=Teacher::where('name','like','%'.$teacher.'%')->pluck('id')->toArray();
            $oObjs->whereIn("teacher_id",$teacher_id);
        }
        if($type!=''){
            $aSearch['type']=$type;
            $oObjs->where('type','=',$type);
        }
		$num['a'] = $oObjs->count();
        $results=$oObjs->paginate(20);
		$num['b'] = $results->count();
		if (view()->exists(session('mode').'.school.squad.list')){
			return View(session('mode').'.school.squad.list', compact('results','aSearch','num'));
		}else{
			return View('default.school.squad.list', compact('results','aSearch','num'));
		}
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        
        $school_list=School::get(['id','name']);
		if (view()->exists(session('mode').'.school.squad.create')){
			return View(session('mode').'.school.squad.create', compact('school_list'));
		}else{
			return View('default.school.squad.create', compact('school_list'));
		}
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $oSquad = new Squad();
        $oSquad->type = $request->input('type',0);
        $oSquad->name = $request->input('name');
        $oSquad->teacher_id = $request->input('teacher_id');
        $oSquad->school_id = $request->input('school_id',0);
        //选修的学院，系，专业清空
        if($oSquad->type==0){
            $oSquad->acdemy_id = 0;
            $oSquad->dept_id = 0;
            $oSquad->major_id = 0;
        }
        else{
            $oSquad->acdemy_id = $request->input('acdemy_id',0);
            $oSquad->dept_id = $request->input('dept_id',0);
            $oSquad->major_id = $request->input('major_id',0);
        }
        $oSquad->income_year = $request->input('income_year');
        if ($request->hasFile('teach_calendar')) {
            if ($request->file('teach_calendar')->isValid()){
                $file = $request->file('teach_calendar');
                $file_name = time().str_random(6).$file->getClientOriginalName();
                \Storage::disk('oss')->put($file_name, file_get_contents($file->getRealPath()));
                if(\Storage::disk('oss')->exists($file_name)) {
                    $oSquad->teach_calendar = $file_name;
                } else {
                    return back()->withInput()->withErrors(['msg' => '教学日历上传失败',]);
                }
            } else {
                return back()->withInput()->withErrors(['msg' => '教学日历上传失败',]);
            }
        }
        $oSquad->save();
        return back()->withInput()->withErrors(['msg' => '创建成功',]);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
         // 没有写完，不仅仅返回这些
        if(!$id) {
            $msg = [
              "custom-msg"=> ["参数错误，非法操作"],
          ];
          return response()->json($msg)->setStatusCode(422);
        } else {
            return response()->json(Squad::whereId($id)->first());
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
        $oObj = Squad::where(['id'=>$id])->first();
        if($oObj){
            if ($oObj->teach_calendar && \Storage::disk('oss')->exists($oObj->teach_calendar)) {
                $oObj->teach_calendar = \AliyunOSS::getUrl($oObj->teach_calendar, $expire = new \DateTime("+1 day"), $bucket = config('filesystems.disks.oss.bucket'));
            } else {
                $oObj->teach_calendar = '';
            }
            $school_list=School::get(['id','name']);
			if (view()->exists(session('mode').'.school.squad.edit')){
				return View(session('mode').'.school.squad.edit', compact('oObj','school_list'));
			}else{
				return View('default.school.squad.edit', compact('oObj','school_list'));
			}
        }
        else{
            return back()->withInput()->withErrors(['msg' => '班级不存在',]);
        }
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
        $oSquad = Squad::find($id);
        if($oSquad) {
            $oSquad->type = $request->input('type',0);
            $oSquad->name = $request->input('name');
            $oSquad->teacher_id = $request->input('teacher_id');
            $oSquad->school_id = $request->input('school_id',0);
            if($oSquad->type==0){
                $oSquad->acdemy_id = 0;
                $oSquad->dept_id = 0;
                $oSquad->major_id = 0;
            }
            else{
                $oSquad->acdemy_id = $request->input('acdemy_id',0);
                $oSquad->dept_id = $request->input('dept_id',0);
                $oSquad->major_id = $request->input('major_id',0);
            }
            $oSquad->income_year = $request->input('income_year');
            if ($request->hasFile('teach_calendar')) {
                if ($request->file('teach_calendar')->isValid()){
                    $file = $request->file('teach_calendar');
                    $file_name = time().str_random(6).$file->getClientOriginalName();
                    \Storage::disk('oss')->put($file_name, file_get_contents($file->getRealPath()));
                    if(\Storage::disk('oss')->exists($file_name)) {
                        $oSquad->teach_calendar = $file_name;
                    } else {
                        return back()->withInput()->withErrors(['msg' => '教学日历上传失败',]);
                    }
                } else {
                    return back()->withInput()->withErrors(['msg' => '教学日历上传失败',]);
                }
            }
            $oSquad->save();
            return redirect('/school/squad')->withInput()->withErrors(['msg' => '修改成功',]);
        } else {
            $msg = [
                "msg"=> ["参数错误，非法操作"],
            ];
            return back()->withInput()->withErrors($msg);
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
            return back()->withInput()->withErrors($msg);
        } else {
            $squad=Squad::where('id',$id)->first();
            if(empty($squad)){
                return back()->withInput()->withErrors(["msg"=> ["班级不存在"],]);
            }
            //班级有学生不允许删除
            $id=explode(',',$id);
            $count=SquadStruct::whereIn('squad_id',$id)->count();
            if($count>0){
                return back()->withInput()->withErrors(["msg"=> ['该班级发现学生记录，不允许删除'],]);
            }
            Squad::whereIn('id', $id)->delete();
            return back();
        }
    }

    // 批量导入学生
    public function inportStudent(Request $request) {

    }
    //
    // 批量管理学生
    public function updateStudent(Request $request) {

    }
    //通过选择学校选择对应的老师
    public function schoolTeacher(Request $request){
        $sid=$request->school_id;
        $data=Teacher::where('school_id',$sid)->with('user')->get();
        $array=array();
        if($data){
            $array['code']=1;
            $array['msg']=$data;
        }else{
            $array['code']=0;
        }
        return json_encode($array);

    }
}
