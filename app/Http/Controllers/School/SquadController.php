<?php

namespace App\Http\Controllers\School;

use App\Models\Student;
use Illuminate\Http\Request;
use DB;
use App\Http\Requests;
use App\Http\Controllers\Controller;
use App\Models\Squad;
use App\Models\Specialty;
use App\Models\SquadStruct;
use App\Models\Teacher;

class SquadController extends BaseController
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $school_id=$this->school_id;
        $aSearch = [];
        $name= $income_year=$type=$teacher=$where='';
        \Request::has('name') &&  $name = \Request::input('name');
        \Request::has('income_year') && $income_year = \Request::input('income_year');
        \Request::has('teacher') && $teacher = \Request::input('teacher');
        \Request::has('type') && $type = \Request::input('type');
        $oObjs=Squad::where(['school_id'=>$this->school_id])->orderBy('id','desc');
        if($name!=''){
            $aSearch['name'] = $name;
            $oObjs->where("name","like",'%'.$name.'%');
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
        $oObjs = $oObjs->paginate(20);
		$num['b'] = $oObjs->count();
		if(view()->exists(session('mode').'.schoolplat.squad.list')){
			return View(session('mode').'.schoolplat.squad.list', compact('oObjs','school_id','aSearch','num'));
		}else{
			return View('default.schoolplat.squad.list', compact('oObjs','school_id','aSearch','num'));
		}
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $school_id=$this->school_id;
		if(view()->exists(session('mode').'.schoolplat.squad.create')){
			return View(session('mode').'.schoolplat.squad.create', compact('school_id'));
		}else{
			return View('default.schoolplat.squad.create', compact('school_id'));
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
        $oSquad->teacher_id = $request->input('teacher_id',0);
        $oSquad->school_id = $this->school_id;
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
        return redirect('squad/squad')->withInput()->withErrors(['msg' => '创建成功',]);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $school_id=$this->school_id;
        $oObj = Squad::where(['id'=>$id,'school_id'=>$school_id])->first();
		if($oObj->acdemy_id){
			$oObj->acdemy = Specialty::where(['id'=>$oObj->acdemy_id])->first()->name;
			
			if($oObj->dept_id){
				$oObj->dept = Specialty::where(['id'=>$oObj->dept_id])->first()->name;
				
				if($oObj->major_id){
					$oObj->major = Specialty::where(['id'=>$oObj->major_id])->first()->name;
				}
			}
		}
        if($oObj){
            if ($oObj->teach_calendar && \Storage::disk('oss')->exists($oObj->teach_calendar)) {
                $oObj->teach_calendar = \AliyunOSS::getUrl($oObj->teach_calendar, $expire = new \DateTime("+1 day"), $bucket = config('filesystems.disks.oss.bucket'));
            } else {
                $oObj->teach_calendar = '';
            }
			if(view()->exists(session('mode').'.schoolplat.squad.edit')){
				return View(session('mode').'.schoolplat.squad.edit', compact('oObj','school_id','oSchool'));
			}else{
				return View('default.schoolplat.squad.edit', compact('oObj','school_id','oSchool'));
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
            $oSquad->type = $request->input('type');
            $oSquad->name = $request->input('name');
            $oSquad->teacher_id = $request->input('teacher_id');
            $oSquad->school_id = $this->school_id;
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
            return redirect('/squad/squad')->withInput()->withErrors(['msg' => '修改成功',]);
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
            if($squad->school_id!=$this->school_id){
                return back()->withInput()->withErrors(["msg"=> ["没有权限"],]);
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
}
