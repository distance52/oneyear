<?php

namespace App\Http\Controllers\Administrator\System;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use App\Models\Permission;
// 系统公告
class MenuController extends Controller
{

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $result=Permission::where('permission_id',0)->with('permissions')->select('id','name','permission_id','alias','sort')->get();
        //var_dump($result);die;
        if (view()->exists(session('mode').'.system.menu.list',compact('result'))){
                return View(session('mode').'.system.menu.list',compact('result'));
        }else{
                 return View('default.system.menu.list',compact('result'));
        }
       
    }
    /**
     * 显示创建平台公告的模板/显示修改
     * @return [type] [description]
     */
    public function create()
    {
        $permission_id = '';
        $obj = Permission::where('permission_id',0)->with('permissions')->select('id','name','permission_id','alias','sort')->get();
        if (view()->exists(session('mode').'.system.menu.create',compact('obj','permission_id'))){
                return View(session('mode').'.system.menu.create',compact('obj','permission_id'));
        }else{
                return View('default.system.menu.create',compact('obj','permission_id'));
        }
    }
    public function create_child($id)
    {
        //echo $id;die;
        $permission_id = Permission::where('id',$id)->first();
        $obj = Permission::where('permission_id',0)->with('permissions')->select('id','name','permission_id','alias','sort')->get();
        if (view()->exists(session('mode').'.system.menu.create',compact('obj','permission_id'))){
                return View(session('mode').'.system.menu.create',compact('obj','permission_id'));
        }else{
                return View('default.system.menu.create',compact('obj','permission_id'));
        }
    }
    /**
     * Store a newly created resource in storage.
     * 提交创建
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $menu = new permission;
        $menu->permission_id = $request->input('permission_id');
        $menu->name = $request->input('name');
        $menu->alias = $request->input('alias');
        $menu->style = $request->input('style');
        $menu->sort = '0';
        $menu->created_at = date('Y-m-d h:i:s',time());
        $menu->save();
        return redirect('/system/menu')->withInput()->withErrors(['msg' => '添加成功',]);       
    }
    
    /**
     * [edit description]
     * @param  [type] $id [description]
     * @return [type]     [description]
     */
    public function edit($id)
    {
        $oObj = Permission::where('id',$id)->first();
	$obj = Permission::where('permission_id',0)->with('permissions')->select('id','name','permission_id','alias','sort')->get();	
		
		if (view()->exists(session('mode').'.system.menu.edit')){
			return View(session('mode').'.system.menu.edit', compact("oObj","obj"));
		}else{
			return View('default.system.menu.edit', compact("oObj","obj"));
		}
    }
    /**
     * Update the specified resource in storage.
     * 保存通知信息的更改
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $menu = Permission::find($id);
        $menu->permission_id = $request->input('permission_id');
        $menu->name = $request->input('name');
        $menu->alias = $request->input('alias');
        $menu->style = $request->input('style');
        $menu->sort = '0';
        $menu->updated_at = date('Y-m-d h:i:s',time());
        $menu->save();
        return redirect('/system/menu')->withInput()->withErrors(['msg' => '修改成功',]); 
       
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $res = Permission::where('permission_id',$id)->first();
        if($res){
            return redirect('/system/menu')->withInput()->withErrors(['msg' => '该菜单含有子菜单，不能删除，请先删除子菜单',]);
        }else{
            $oNotice = Permission::find($id);
            $oNotice->delete();
            return redirect('/system/menu')->withInput()->withErrors(['msg' => '删除成功',]);
        }
        
        
    }

}
