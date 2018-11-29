<?php

namespace App\Http\Controllers\School;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Student;
use App\Models\School;
use App\Models\Squad;
use App\Models\SquadStruct;

class StudentController extends BaseController
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
        $email=$name=$sno=$where='';
        \Request::has('name') &&  $aSearch['name']=$name = \Request::input('name');
        \Request::has('sno') &&  $aSearch['sno']=$sno = \Request::input('sno');
        \Request::has('email') &&  $aSearch['email']=$email = \Request::input('email');
        $oObjs = User::with('student')->where(['plat'=>3,'school_id'=>$school_id]);
        if($name!=''){
            $oObjs->where("name","like",'%'.$name.'%');
        }
        if($sno!=''){
            $user_ids=Student::where('sno','like','%'.$sno.'%')->pluck('id');
            $oObjs->whereIn("id",$user_ids);
        }
        if($email!=''){
            $oObjs->where("email","like",'%'.$email.'%');
        }
        $oObjs = $oObjs->orderBy('id','desc');
		$num['a'] = $oObjs->count();
		$oObjs=$oObjs->paginate(20);
		$num['b'] = $oObjs->count();
        foreach($oObjs as &$val){
            $val->squad_name=$val['student']['squad_id']>0 ? Squad::where('id',$val->student['squad_id'])->value('name'):'暂未设置';
        }
		if(view()->exists(session('mode').'.schoolplat.student.list')){
			return View(session('mode').'.schoolplat.student.list', compact('oObjs','aSearch','num'));
		}else{
			return View('default.schoolplat.student.list', compact('oObjs','aSearch','num'));
		}
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
		if(view()->exists(session('mode').'.schoolplat.student.create')){
			return View(session('mode').'.schoolplat.student.create');
		}else{
			return View('default.schoolplat.student.create');
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
        $school_id=$this->school_id;
        $oUser = new User();

        $sno=$request->input('sno');
        if($sno=='' || strlen($sno)<6){
            return back()->withInput()->withErrors([ 'msg' => '学号不小于6位不能为空',]);
        }
        $suffix=School::where('id',$school_id)->first(['email_suffix','host_suffix']);
        if($suffix->email_suffix==''){
            return back()->withInput()->withErrors([ 'msg' => '该学校的登录邮箱后缀还未设置',]);
        }
        if($suffix->host_suffix==''){
            return back()->withInput()->withErrors([ 'msg' => '该学校的二级域名前缀还未设置',]);
        }
        $stu=Student::where(['sno'=>$sno,'school_id'=>$this->school_id])->first();
        if(!empty($stu)){
            return back()->withInput()->withErrors([ 'msg' => '同一所学校的学号不能重复',]);
        }
        $login_email=$sno.'@'.$suffix->email_suffix;
        $data=array(
            'name'=>$request->input('name'),
            'username'=>$suffix->host_suffix.$sno,//用户名为学校前缀+学号
            'email'=>$login_email,
            'password'=>$request->input('password', '123456'),
            'plat'=>3,
            'school_id'=>$this->school_id
        );
        if($request->input('mobile')!=''){
            $data['mobile']=$request->input('mobile');
        }
        $oUserMobile=$oUser->where('mobile',$request->input('mobile'))->pluck('mobile')->toArray();
        if(!empty($oUserMobile)){
            return back()->withInput()->withErrors([ 'msg' => '手机号已存在']);
        }
        $newUser=$oUser->createUser($data);
        //未通过验证或创建失败直接报错
        if(!$newUser['status']){
            return back()->withInput()->withErrors([ 'msg' => $newUser['info'],]);
        }
        $newUser=$newUser['data'];
        if($newUser){
            $data=array(
                'school_id'=>$school_id,
                //'squad_id'=>0,
                'sno'=>$request->input('sno',''),
                'user_id'=>$newUser->id,
                'name'=>$request->input('name',''),
                'academy'=>$request->input('academy',0),
                'dept'=>$request->input('dept',0),
                'major'=>$request->input('major',0),
                'year'=>$request->input('year',0),
                'qq'=>$request->input('qq',''),
                'desc'=>'',
            );
            $new_student = Student::createStudent($data);
			if($new_student){
					$squadStruct = new SquadStruct;
					$squadStruct->squad_id = $request->input('squad_id',0);
					$squadStruct->struct_id = $new_student;
					$squadStruct->type = 1;
					$squadStruct->save();
			}
            return redirect('/user/student')->withErrors(['msg' => '添加成功',]);
        }
        else{
            return back()->withInput()->withErrors([ 'msg' => '创建用户失败',]);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $school_id=$this->school_id;
        if(!$id) {
            $msg = ["msg"=> ["参数错误，非法操作"],];
            return back()->withInput()->withErrors($msg);
        } else {
            $oObj = User::where(['id'=>$id,'plat'=>3,'school_id'=>$school_id])->first();
           
            if($oObj){
                $student=Student::where(['user_id'=>$id])->first();
				if(view()->exists(session('mode').'.schoolplat.student.show')){
					return View(session('mode').'.schoolplat.student.show', compact('oObj','student'));
				}else{
					return View('default.schoolplat.student.show', compact('oObj','student'));
				}
            }
            else{
                $msg = ["msg"=> ["用户信息不存在"],];
                return back()->withInput()->withErrors($msg);
            }
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
        $school_id=$this->school_id;
        if(!$id){
            return back()->withInput()->withErrors(['msg' => '参数缺失',]);
        }
        $oObj = User::where(['id'=>$id,'plat'=>3,'school_id'=>$school_id])->first();
        if($oObj){
            $student=Student::where(['user_id'=>$id])->first();
			if(view()->exists(session('mode').'.schoolplat.student.edit')){
				return View(session('mode').'.schoolplat.student.edit', compact('oObj','student'));
			}else{
				return View('default.schoolplat.student.edit', compact('oObj','student'));
			}
        }
        else {
            return back()->withInput()->withErrors(['msg' => '学生不存在',]);
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
        if(!$id){
            return back()->withInput()->withErrors([ 'msg' => '参数缺失',]);
        }
        $school_id=$this->school_id;
        $oUser = new User();
        $oUser = $oUser->where(['id'=>$id,'plat'=>3])->first();
        if($oUser) {
            $data=array();
            $data['id'] = $oUser->id;
            $data['name'] = $request->input('name', '');
            $data['email'] = $request->input('email', '');
            $data['mobile'] = $request->input('mobile', '');
            if($request->input('password')!=''){
                $data['password'] = $request->input('password');
            }
//            $data['school_id'] = $request->input('school_id', 0);
            $result=User::updateUser($data);
            if($result['status']==0){
                return back()->withInput()->withErrors([ 'msg' => $result['info'],]);
            }
            if($oUser->id){
                $oStudent=Student::where(['user_id'=>$oUser->id])->first();
                $data=array(
                    'school_id'=>$school_id,
                    //'squad_id'=>$request->input('squad_id',0),
                    'sno'=>$request->input('sno',''),
                    'user_id'=>$oUser->id,
                    'name'=>$request->input('name',''),
                    'academy'=>$request->input('academy',0),
                    'dept'=>$request->input('dept',0),
                    'major'=>$request->input('major',0),
                    'year'=>$request->input('year',0),
                    'qq'=>$request->input('qq',''),
                    'phone'=>$request->input('phone',''),
                    'desc'=>'',
                );
                empty($oStudent) || $data['id']=$oStudent->id;//如果找到则更新
                $new_student = Student::createStudent($data);
				if($new_student){
					$squadStruct = new SquadStruct;
					$squadStruct->squad_id = $request->input('squad_id',0);
					$squadStruct->struct_id = $new_student;
					$squadStruct->type = 1;
					$squadStruct->save();
				}
                return redirect('/user/student')->withErrors([
                    'msg' => '修改成功',
                ]);
            }
            else{
                return back()->withInput()->withErrors([ 'msg' => '更新失败',]);
            }
        } else {
            return back()->withInput()->withErrors([ 'msg' => '用户不存在',]);
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
            $msg = ["msg"=> ["参数错误，非法操作"],];
            return back()->withInput()->withErrors($msg);
        } else {
            $id=explode(',',$id);
            User::whereIn('id', $id)->delete();
            $student = Student::whereIn('user_id',$id);
			$students = $student->pluck('id');
			$student->delete();//删除学生表
			SquadStruct::whereIn('struct_id',$students)->where('type',1)->delete();
            return back();
        }
    }
}
