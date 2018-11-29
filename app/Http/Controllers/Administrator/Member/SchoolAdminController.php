<?php

namespace App\Http\Controllers\Administrator\Member;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\School;

class SchoolAdminController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $aSearch = [];
        $email=$name=$school=$mobile=$where='';
        \Request::has('email') &&  $aSearch['email']=$email = \Request::input('email');
        \Request::has('name') &&  $aSearch['name']=$name = \Request::input('name');
        \Request::has('school') &&  $aSearch['school']=$school = \Request::input('school');
        \Request::has('mobile') &&  $aSearch['mobile']=$mobile = \Request::input('mobile');
        $oObjs = User::with('school')->orderBy('id','desc')->where('plat',1);
        if($name!=''){
            $oObjs = $oObjs->where('name','like', '%'.$name.'%');
        }
        if($email!=''){
            $oObjs = $oObjs->where('email','like', '%'.$email.'%');
        }
		if($mobile!=''){
            $oObjs = $oObjs->where('mobile','like', '%'.$mobile.'%');
        }
		if($school!=''){
			//获取学校id号
			$shcoolid = School::where('name','like', '%'.$school.'%')->pluck('id');
		    $oObjs = $oObjs->whereIn('school_id',$shcoolid);
        }
        $num['a'] = $oObjs->count();
		$oObjs=$oObjs->paginate(12);
		$num['b'] = $oObjs->count();
        //
		if (view()->exists(session('mode').'.users.school.list')){
			return View(session('mode').'.users.school.list', compact('oObjs','aSearch','num'));
		}else{
			return View('default.users.school.list', compact('oObjs','aSearch','num'));
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
		if (view()->exists(session('mode').'.users.school.create')){
			return View(session('mode').'.users.school.create', compact('school_list'));
		}else{
			return View('default.users.school.create', compact('school_list'));
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
        $data=array(
            'name'=>$request->input('name'),
            'email'=>$request->input('email'),
            'mobile'=>$request->input('mobile'),
            'password'=>$request->input('password', '123456'),
            'plat'=>1,
            'school_id'=>$school_id
        );
        $newUser=$oUser->createUser($data);
        //未通过验证或创建失败直接报错
        if(!$newUser['status']){
            return back()->withInput()->withErrors([ 'msg' => $newUser['info'],]);
        }
        $newUser=$newUser['data'];
        if($newUser){
            $id = User::where('email',$data['email'])->orderBy('id','desc')->value('id');
            if($school_id>0 && $id){
                $oSchool=School::where('id',$school_id)->first();
                //先删除这个用户担任的所有学校管理员
                School::where('admin_user_id',$id)->update(['admin_user_id'=>0]);
                $oSchool->admin_user_id=$id;
                $oSchool->save();
            }
            return redirect('/member/schoolmanadmin')->withErrors(['msg' => '添加成功',]);
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
            $oObj = User::where(['id'=>$id,'plat'=>1])->first();
            if($oObj){
                //$teacher=Teacher::where(['user_id'=>$id])->first();
				if (view()->exists(session('mode').'.users.school.show')){
					return View(session('mode').'.users.school.show', compact('oObj'));
				}else{
					return View('default.users.school.show', compact('oObj'));
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
        $oObj = User::where(['id'=>$id,'plat'=>1])->first();
        if($oObj){
            $school_list=School::get(['id','name']);
			if (view()->exists(session('mode').'.users.school.edit')){
				return View(session('mode').'.users.school.edit', compact('oObj','school_list'));
			}else{
				return View('default.users.school.edit', compact('oObj','school_list'));
			}
        }
        else{
            return back()->withInput()->withErrors(['msg' => '学校管理员不存在',]);
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
        $oUser = User::where(['id'=>$id,'plat'=>1])->first();
        if($oUser) {
            $data=array();
            $data['id'] = $oUser->id;
            $data['name'] = $request->input('name', '');
            $data['email'] = $request->input('email', '');
            $data['mobile'] = $request->input('mobile', '');
            $school_id=$request->input('school_id',0);
            if($request->input('password')!=''){
                $data['password'] = $request->input('password', '123456');
            }
            $data['school_id'] = $school_id;
            $result=User::updateUser($data);
            if($result['status']==0){
                return back()->withInput()->withErrors([ 'msg' => $result['info'],]);
            }
            if($school_id>0){
                //先删除这个用户担任的所有学校管理员
                School::where('admin_user_id',$oUser->id)->update(['admin_user_id'=>0]);
                $oSchool=School::where('id',$school_id)->first();
                $oSchool->admin_user_id=$oUser->id;
                $oSchool->save();
            }
            return redirect('/member/schoolmanadmin')->withErrors([
                'msg' => '修改成功',
            ]);
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
			School::where('admin_user_id',$id)->update(['admin_user_id'=>0]);
            User::whereIn('id', $id)->delete();
            return back();
        }
    }
}
