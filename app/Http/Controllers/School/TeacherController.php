<?php

namespace App\Http\Controllers\School;
use App\Models\Squad;
use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Teacher;
use App\Models\Suqad;


class TeacherController extends BaseController
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
        $email=$name= $begintime=$endtime=$where='';
        \Request::has('email') &&  $aSearch['email']=$email = \Request::input('email');
        \Request::has('name') &&  $aSearch['name']=$name = \Request::input('name');
        \Request::has('begintime') && $aSearch['begintime']=$begintime = \Request::input('begintime');
        \Request::has('endtime') &&  $aSearch['endtime']=$endtime = \Request::input('endtime');
        //
        $oObjs = User::with('school')->where(['plat'=>2,'school_id'=>$school_id]);
        if($name!=''){
            $oObjs = $oObjs->where('name','like', '%'.$aSearch['name'].'%');
        }
        if($email!=''){
            $oObjs = $oObjs->where('email','like', '%'.$aSearch['email'].'%');
        }
        if($begintime!=''){
            $oObjs->where("created_at",">=",strtotime($begintime));
        }
        if($endtime!=''){
            $oObjs->where("created_at","<=",strtotime($endtime));
        }
        $oObjs = $oObjs->with('teacher')->orderBy('id','desc');
		$num['a'] = $oObjs->count();
		$oObjs=$oObjs->paginate(20);
		$num['b'] = $oObjs->count();
        foreach($oObjs as &$val){
            $squad_name='';
            $teacher_id=Teacher::where('user_id',$val->id)->value('id');
            
            !empty($teacher_id) && $squad_name=Squad::where('teacher_id',$teacher_id)->value('name');
            $val->squad_name=$squad_name;
        }
		if(view()->exists(session('mode').'.schoolplat.teacher.list')){
			return View(session('mode').'.schoolplat.teacher.list', compact('oObjs','school_id','aSearch','num'));
		}else{
			return View('default.schoolplat.teacher.list', compact('oObjs','school_id','aSearch','num'));
		}
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
		if (view()->exists(session('mode').'.schoolplat.teacher.create')){
			return View(session('mode').'.schoolplat.teacher.create', compact('school_list'));
		}else{
			return View('default.schoolplat.teacher.create', compact('school_list'));
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
        $data=array(
            'name'=>$request->input('name'),
            'email'=>$request->input('email'),
            'mobile'=>$request->input('mobile'),
            'password'=>$request->input('password', '123456'),
            'plat'=>2,
            'school_id'=>$school_id
        );
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
                'user_id'=>$newUser->id,
                'school_id'=>$school_id,
                'name'=>$request->input('name',''),
                'dept'=>$request->input('dept',''),
                'speciality'=>$request->input('speciality',''),
                'qq'=>$request->input('qq',''),
                'desc'=>'',
            );
            Teacher::createTeacher($data);
            return redirect('/user/teacher')->withErrors(['msg' => '添加成功',]);
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
            $oObj = User::where(['id'=>$id,'plat'=>2,'school_id'=>$school_id])->first();
            if($oObj){
               $teacher=Teacher::where(['user_id'=>$id])->first();
			   if (view()->exists(session('mode').'.schoolplat.teacher.show')){
					return View(session('mode').'.schoolplat.teacher.show', compact('oObj','teacher'));
				}else{
					return View('default.schoolplat.teacher.show', compact('oObj','teacher'));
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
        $school_id=$this->school_id;
        $oObj = User::where(['id'=>$id,'plat'=>2,'school_id'=>$school_id])->first();
        $teacher=Teacher::where(['user_id'=>$id])->first();
		 if (view()->exists(session('mode').'.schoolplat.teacher.edit')){
					return View(session('mode').'.schoolplat.teacher.edit', compact('oObj','teacher'));
				}else{
					return View('default.schoolplat.teacher.edit', compact('oObj','teacher'));
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
        $school_id=$this->school_id;
        $oUser = User::where(['id'=>$id,'plat'=>2,'school_id'=>$school_id])->first();
        if($oUser) {
            $data=array();
            $data['id'] = $oUser->id;
            $data['name'] = $request->input('name', '');
            $data['email'] = $request->input('email', '');
            $data['mobile'] = $request->input('mobile', '');
            if($request->input('password')!=''){
                $data['password'] = $request->input('password', '123456');
            }
            $result=User::updateUser($data);
            if($result['status']==0){
                return back()->withInput()->withErrors([ 'msg' => $result['info'],]);
            }
            if($oUser->id){
                $oTeacher=Teacher::where(['user_id'=>$oUser->id])->first();
                //存在则修改，否则则更新
                if(!empty($oTeacher)){
                    $data=array(
                        'id'=>$oTeacher->id,
                        'user_id'=>$oUser->id,
                        'school_id'=>$school_id,
                        'name'=>$request->input('name',''),
                        'dept'=>$request->input('dept',''),
                        'speciality'=>$request->input('speciality',''),
                        'email'=>$request->input('contact_email',''),
                        'qq'=>$request->input('qq',''),
                        'desc'=>'',
                    );
                    Teacher::createTeacher($data);
                }
                else{
                    $data=array(
                        'user_id'=>$oUser->id,
                        'school_id'=>$school_id,
                        'name'=>$request->input('name',''),
                        'dept'=>$request->input('dept',''),
                        'speciality'=>$request->input('speciality',''),
                        'email'=>$request->input('contact_email',''),
                        'qq'=>$request->input('qq',''),
                        'desc'=>'',
                    );
                    Teacher::createTeacher($data);
                }
                return redirect('/user/teacher')->withErrors([
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
        $school_id=$this->school_id;
        if(!$id) {
            $msg = ["msg"=> ["参数错误，非法操作"],];
            return back()->withInput()->withErrors($msg);
        } else {
            $id=explode(',',$id);
            User::whereIn('id', $id)->delete();
            Teacher::whereIn('user_id',$id)->delete();;//删除老师表
            return back();
        }
    }
	public function teacher_score($id)
    {
		$oObj = User::where(['id'=>$id,'plat'=>2])->first();
		$teacher=Teacher::where(['user_id'=>$id])->first();
		$oTeachers = new Teacher;
		$score_num = $oTeachers->statistical_score($teacher->id);//统计得分
		$oObjs = \DB::table('teacher_log_scores')->where('user_id',$id)->orderBy('addtime','desc');
		$num['a'] = $oObjs->count();
		$oObjs=$oObjs->paginate(12);
		$num['b'] = $oObjs->count();
		$type = [1=>'批改作业',2=>'创建环节',3=>'创建模块',4=>'创建单元',5=>'创建方案'];
        if (view()->exists(session('mode').'.schoolplat.teacher.score')){
				return View(session('mode').'.schoolplat.teacher.score', compact('oObj','oObjs','score_num','num','type'));
			}else{
				return View('default.schoolplat.teacher.score', compact('oObj','oObjs','score_num','num','type'));
			}
    }
	public function updata_score()
    {
		$teachers=Teacher::get();
		$oteacher = New Teacher;
		foreach($teachers as $teacher)
		{
			$oteacher->total_score($teacher->id);
		}
        return back();
    }
}
