<?php

namespace App\Http\Controllers\Administrator\System;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\Role;

use Illuminate\Routing\UrlGenerator;

class PermissionController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index($role_id)
    {

        $oRole = Role::whereId((int)$role_id)->first();
        if(!$oRole) {
            // 参数错误
            return redirect('error')->with(['msg'=>'参数错误', 'href'=>app(UrlGenerator::class)->previous()]);
        }
        //
        $oRole->permissions = $oRole->permissions()->pluck('id')->toArray(); //查出所有关联角色的菜单权限id
         //dd($oRole->permissions);
        $oObjs = Permission::where('permission_id',0)->with('permissions')->get();//查出所有顶级权限分类
		if (view()->exists(session('mode').'.system.permission.list')){
			return View(session('mode').'.system.permission.list', compact('oObjs','role_id','oRole'));
		}else{
			return View('default.system.permission.list', compact('oObjs','role_id','oRole'));
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
        //
        $oRole = Role::whereId($request->input('role_id'))->first();
        if(!$oRole) {
            // 参数错误
            return redirect('error')->with(['msg'=>'参数错误', 'href'=>app(UrlGenerator::class)->previous()]);
        }
        // dd($request->input('permisson_id'));
        $oRole->permissions()->sync($request->input('permisson_id'));
        return back();
    }
}
