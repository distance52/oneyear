<?php

namespace App\Http\Controllers\Administrator\Member;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use Illuminate\Routing\UrlGenerator;
use App\Models\User;
use App\Models\School;
use App\Models\Student;
use App\Models\Squad;
use App\Models\SquadStruct;

class StudentController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $aSearch = [];
        $email=$school_name=$name=$sno=$where='';
        \Request::has('email') &&  $aSearch['email']=$email = \Request::input('email');
        \Request::has('name') &&  $aSearch['name']=$name = \Request::input('name');
        \Request::has('sno') &&  $aSearch['sno']=$sno = \Request::input('sno');
        \Request::has('school_name') &&  $aSearch['school_name']=$school_name = \Request::input('school_name');
        $oObjs = User::with('school','student')->where('plat',3);
        if($name!=''){
            $oObjs = $oObjs->where('name','like', '%'.$name.'%');
        }
		if($sno!=''){
			$studentid = Student::where('sno','like', '%'.$sno.'%')->pluck('user_id');
			//dd($studentid);
		    $oObjs = $oObjs->whereIn('id',$studentid);
        }
        if($email!=''){
            $oObjs = $oObjs->where('email','like', '%'.$email.'%');
        }
        if($school_name!=''){
            $school_id=School::where('name','like', '%'.$school_name.'%')->pluck('id');
            if(count($school_id)){
                $oObjs = $oObjs->whereIn('school_id',$school_id);
            }
            else{
                $oObjs = $oObjs->whereIn('id',array(0));//不存在
            }
        }
        $oObjs = $oObjs->orderBy('id','desc');
		$num['a'] = $oObjs->count();
		$oObjs=$oObjs->paginate(12);
		$num['b'] = $oObjs->count();
        foreach($oObjs as &$val){
            if(isset($val->student)){
				$squad_ids = SquadStruct::where('type',1)->where('struct_id',$val->student->id)->pluck('squad_id');
                $squad_info=Squad::whereIn('id',$squad_ids)->get();
				foreach($squad_info as $v)
				{
					if($v->name){
						$val->squad_name .= $v->name."(".$v->income_year.")、";
					}	
				}
				$val->squad_name = rtrim($val->squad_name,'、');
            }
            else{
                $val->squad_name='未设置班级';
            }
        }
		if (view()->exists(session('mode').'.users.student.list')){
			return View(session('mode').'.users.student.list', compact('oObjs','aSearch','num'));
		}else{
			return View('default.users.student.list', compact('oObjs','aSearch','num'));
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
		if (view()->exists(session('mode').'.users.student.create')){
			return View(session('mode').'.users.student.create', compact('school_list'));
		}else{
			return View('default.users.student.create', compact('school_list'));
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
        $oUser = new User();
        $school_id=$request->input('school_id',0);
        $suffix=School::where('id',$school_id)->first(['email_suffix','host_suffix']);
        if($school_id==0){
            return back()->withInput()->withErrors([ 'msg' => '请选择学生所在学校',]);
        }
        if($suffix->email_suffix==''){
            return back()->withInput()->withErrors([ 'msg' => '该学校的登录邮箱后缀还未设置',]);
        }
        if($suffix->host_suffix==''){
            return back()->withInput()->withInput()->withErrors([ 'msg' => '该学校的二级域名前缀还未设置',]);
        }
        //$sno_regix='/^[0-9]+$/';//学号只能纯数字
        $sno_regix='/^[0-9]{6,}$/';//学号只能纯数字并大于6位
        if($request->input('sno')=='' || !preg_match($sno_regix,$request->input('sno'))){
            //return redirect('error')->with(['msg'=>'学号不能为空且只能为纯数字', 'href'=>app(UrlGenerator::class)->previous()]);
            return back()->withInput()->withErrors([ 'msg' => '学号不能为空且只能为纯数字并且大于6为',]);
        }
        $login_email=$request->input('email');
        if($login_email==''){
            $login_email=$request->input('sno').'@'.$suffix->email_suffix;
        }
        $stu=Student::where(['sno'=>$request->input('sno'),'school_id'=>$school_id])->first();
        if(!empty($stu)){
            return back()->withInput()->withErrors([ 'msg' => '同一所学校的学号不能重复',]);
        }
        $username=$suffix->host_suffix.$request->input('sno');//用户名为学校前缀+学号
        $data=array(
            'name'=>$request->input('name'),
            'email'=>$login_email,
            'username'=>$username,
            'password'=>$request->input('password', '123456'),
            'sno'=>$request->input('sno',''),
            'plat'=>3,
            'school_id'=>$school_id
        );
        if($request->input('mobile')!=''){
            $data['mobile']=$request->input('mobile');
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
                //'squad_id'=>$request->input('squad_id',0),
                'sno'=>$request->input('sno',''),
                'user_id'=>$newUser->id,
                'name'=>$request->input('name',''),
                'academy'=>$request->input('academy',0),
                'dept'=>$request->input('dept',0),
                'major'=>$request->input('major',0),
                'year'=>$request->input('year',0),
                'qq'=>$request->input('qq',''),
                'phone'=>$request->input('phone',''),
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
            return redirect('/member/student')->withErrors([ 'msg' => '添加成功',]);
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
        if(!$id) {
            $msg = ["msg"=> ["参数错误，非法操作"],];
            return back()->withInput()->withErrors($msg);
        } else {
            $oObj = User::where(['id'=>$id,'plat'=>3])->first();
            if($oObj){
                $student=Student::where(['user_id'=>$id])->first();
				if (view()->exists(session('mode').'.users.student.show')){
					return View(session('mode').'.users.student.show', compact('oObj','student'));
				}else{
					return View('default.users.student.show', compact('oObj','student'));
				}
            }
            else{
                $msg = ["msg"=> ["用户不存在"],];
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
        if(!$id){
            return back()->withErrors([ 'msg' => '参数缺失',]);
        }
        $oObj = User::where(['id'=>$id,'plat'=>3])->first();
        if($oObj){
            $school_list=School::get(['id','name']);
            $student=Student::where(['user_id'=>$id])->first();
			if (view()->exists(session('mode').'.users.student.edit')){
				return View(session('mode').'.users.student.edit', compact('oObj','school_list','student'));
			}else{
				return View('default.users.student.edit', compact('oObj','school_list','student'));
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
            return back()->withErrors([ 'msg' => '参数缺失',]);
        }
        $oUser = User::where(['id'=>$id,'plat'=>3])->first();
        if($oUser) {
            $data=array();
            $data['id'] = $oUser->id;
            $data['name'] = $request->input('name', '');
            $data['email'] = $request->input('email', '');
            $data['mobile'] = $request->input('mobile', '');
            if($request->input('password')!=''){
                $data['password'] = $request->input('password', '123456');
            }
            $data['school_id'] = $request->input('school_id',0);
            $result=User::updateUser($data);
            if($result['status']==0){
                return back()->withInput()->withErrors([ 'msg' => $result['info'],]);
            }
            if($oUser->id){
                $oStudent=Student::where(['user_id'=>$oUser->id])->first();
                $data=array(
                    'school_id'=>$request->input('school_id',0),
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
                return redirect('/member/student')->withErrors([
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
