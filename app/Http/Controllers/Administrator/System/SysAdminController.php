<?php

namespace App\Http\Controllers\Administrator\System;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Role;
use App\Services\Plugin\PluginManager;

class SysAdminController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $aSearch=[];
        $email=$name=$where='';
        \Request::has('email') &&  $aSearch['email']=$email = \Request::input('email');
        \Request::has('name') &&  $aSearch['name']=$name = \Request::input('name');
        $oObjs = User::with('role')->where('plat',0);
        if($name!=''){
            $oObjs = $oObjs->where('name','like', '%'.$name.'%');
        }
        if($email!=''){
            $oObjs = $oObjs->where('email','like', '%'.$email.'%');
        }
        $oObjs = $oObjs->orderBy('id','desc');
		$num['a'] = $oObjs->count();
		$oObjs=$oObjs->paginate(12);
		$num['b'] = $oObjs->count();
		if (view()->exists(session('mode').'.system.system.list')){
			return View(session('mode').'.system.system.list', compact('oObjs','aSearch','num'));
		}else{
			return View('default.system.system.list', compact('oObjs','aSearch','num'));
		}
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $role_list=Role::get(['id','name']);
		if (view()->exists(session('mode').'.system.system.create')){
			return View(session('mode').'.system.system.create', compact('role_list'));
		}else{
			return View('default.system.system.create', compact('role_list'));
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
        $data=array(
            'name'=>$request->input('name'),
            'email'=>$request->input('email'),
            'mobile'=>$request->input('mobile'),
            'password'=>$request->input('password', '123456'),
            'role_id'=>$request->input('role_id', 0),
            'plat'=>0,
            'school_id'=>0
        );
        $newUser=$oUser->createUser($data);
        if($newUser['status']==1){
            return redirect('/system/sysmanadmin')->withErrors(['msg' => '添加成功',]);
        }
        else{
            return back()->withErrors([ 'msg' => $newUser['info'],]);
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
            $oObj = User::where(['id'=>$id,'plat'=>0])->first();
            if($oObj){
				if (view()->exists(session('mode').'.system.system.show')){
					return View(session('mode').'.system.system.show', compact('oObj'));
				}else{
					return View('default.system.system.show', compact('oObj'));
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
        $oObj = User::where(['id'=>$id,'plat'=>0])->first();
        if($oObj){
            $role_list=Role::get(['id','name']);
			if (view()->exists(session('mode').'.system.system.edit')){
			return View(session('mode').'.system.system.edit', compact('oObj','role_list'));
			}else{
				return View('default.system.system.edit', compact('oObj','role_list'));
			}
        }
        else{
            return back()->withInput()->withErrors(['msg' => '管理员不存在',]);
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
        $oUser = User::where(['id'=>$id,'plat'=>0])->first();
        if($oUser) {
            $data=array();
            $data['id'] = $oUser->id;
            $data['name'] = $request->input('name', '');
            $data['email'] = $request->input('email', '');
            $data['mobile'] = $request->input('mobile', '');
            if($request->input('password')!=''){
                $data['password'] = $request->input('password', '123456');
            }
            $data['role_id'] = $request->input('role_id',0);
            $result=User::updateUser($data);
            if($result['status']==0){
                return back()->withErrors([ 'msg' => $result['info'],]);
            }
            return redirect('/system/sysmanadmin')->withErrors([
                'msg' => '修改成功',
            ]);
        } else {
            return back()->withErrors([ 'msg' => '用户不存在',]);
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
            return back();
        }
    }

    public function plugin(PluginManager $plugins){
        $installed = $plugins->getPlugins();
        return view('default.system.system.plugin',compact('installed'));
    }
    
    public function setPlugin($type, PluginManager $plugins){
        $name = \Request::input('name');
        $plugin = plugin($name);
        if($plugin){
            switch ($type){
                case 'enabled';
                    $plugins->enable($name);
                    return back()->with('开启成功');
                    break;
                case 'disabled';
                    $plugins->disable($name);
                    return back()->with('禁用成功');
                    break;
                case 'uninstall';
                    $plugins->uninstall($name);
                    return back()->with('卸载成功');
                    break;
                default:

            }
        }
        return back()->withInput()->withErrors('找不到对应模块');

        
    }
    
}
