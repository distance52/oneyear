<?php

namespace App\Http\Controllers\Administrator\System;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use App\Models\Role;

use Illuminate\Routing\UrlGenerator;

class RoleController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $oObjs = Role::where('name','!=','超级管理员');
		$num['a'] = $oObjs->count();
		$oObjs=$oObjs->paginate(20);
		$num['b'] = $oObjs->count();
		if (view()->exists(session('mode').'.system.role.list')){
			return View(session('mode').'.system.role.list', compact('oObjs','num'));
		}else{
			return View('default.system.role.list', compact('oObjs','num'));
		}
    }

    public function edit($id) {

        $oObj = Role::find($id);
        if($oObj->name == '超级管理员') {
            return redirect('error')->with(['msg'=>'参数错误', 'href'=>app(UrlGenerator::class)->previous()]);
        }
		if (view()->exists(session('mode').'.system.role.edit')){
			return View(session('mode').'.system.role.edit', compact('oObj'));
		}else{
			return View('default.system.role.edit', compact('oObj'));
		}
    }

    public function create() {
		if (view()->exists(session('mode').'.system.role.create')){
			return View(session('mode').'.system.role.create');
		}else{
			return View('default.system.role.create');
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
        $oRole = new Role;
        $oRole->name = $request->input('name');
        if($oRole->name == '超级管理员') {
            return redirect('error')->with(['msg'=>'禁止创建此角色', 'href'=>app(UrlGenerator::class)->previous()]);
        }
        $oRole->save();

        return redirect('system/role');
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

        $oRole = Role::find($id);
        if($oRole) {
            if($request->input('name') == '超级管理员') {
                return redirect('error')->with(['msg'=>'禁止修改为此名称', 'href'=>app(UrlGenerator::class)->previous()]);

            }
            $oRole->name = $request->input('name');
            $oRole->save();
        }
        return redirect('system/role');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        if($id) {
            $ids=explode(',',$id);
            if($ids) {
                foreach($ids as $one) {
                    $oRole = Role::find($one);
                    if($oRole->name == '超级管理员') { 
                        continue;
                    }
                    $oRole->permissions()->detach();
                    $oRole->delete();
                }
            }
        }
        return back();
    }
}
