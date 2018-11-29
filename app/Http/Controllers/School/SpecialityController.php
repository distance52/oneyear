<?php

namespace App\Http\Controllers\School;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use App\Models\Specialty;
class SpecialityController extends BaseController
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $school_id=$this->school_id;
        $result=Specialty::where('school_id',$school_id)->get(['id','name','parentid','path','listorder']);
        $tree = \App::make('tree');
        $tree->icon = array('&nbsp;&nbsp;&nbsp;│ ','&nbsp;&nbsp;&nbsp;├─ ','&nbsp;&nbsp;&nbsp;└─ ');
        $tree->nbsp = '&nbsp;&nbsp;&nbsp;';
        $categorys=[];
        if(!empty($result)) {
            $result=$result->toArray();
            foreach($result as $r) {
                $r['str_manage'] = '';
                $r['str_manage'] .= '<a class="btn btn-warning btn-small" href="/setting/specialty/create?parentid='.$r['id'].'">添加</a>
                <a class="btn btn-small" href="/setting/specialty/'.$r['id'].'/edit">修改</a>
                <button class="btn btn-white btn-small btn-delete" data-url="/setting/specialty/delete/'.$r['id'].'">删除</button>';
                $categorys[$r['id']] = $r;
            }
        }
        $str  = "<tr class='level-1'>
					<td class='col-1'><input name='listorders[\$id]' type='text' size='3' value='\$listorder' class='ipt ipt-num'></td>
					<td class='col-2' style='text-align: left;'>\$spacer\$name</td>
					<td class='col-3'>\$str_manage</td>
				</tr>";
        $tree->init($categorys);
        $categorys = $tree->get_tree(0, $str);
		if(view()->exists(session('mode').'.schoolplat.specialty.list')){
			return View(session('mode').'.schoolplat.specialty.list', compact('categorys','school_id'));
		}else{
			return View('default.schoolplat.specialty.list', compact('categorys','school_id'));
		}
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request)
    {
        $school_id=$this->school_id;
        $parentid = $request->input('parentid',0);
        $result=Specialty::where('school_id',$school_id)->get(['id','name','parentid','path','listorder']);
        $string = '<select name="parentid" class="sel">';
        $string .= "<option value='0'>请选择父类</option>";
        $categorys=[];
        if(!empty($result)) {
            $result=$result->toArray();
            foreach($result as $r) {
                $r['selected'] = $parentid==$r['id'] ? 'selected' : '';
                $categorys[$r['id']] = $r;
            }
        }
        $str  = "<option value='\$id' \$selected>\$spacer \$name</option>";
        $tree = \App::make('tree');
        $tree->init($categorys);
        $string .= $tree->get_tree(0, $str);
        $string .= '</select>';
		if(view()->exists(session('mode').'.schoolplat.specialty.create')){
			return View(session('mode').'.schoolplat.specialty.create', compact('string','school_id'));
		}else{
			return View('default.schoolplat.specialty.create', compact('string','school_id'));
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
        $parentid = $request->input('parentid',0);
        $oSpecialty = new Specialty();
        $oSpecialty->name=$request->input('name');
        $oSpecialty->parentid=$parentid;
        $oSpecialty->school_id=$school_id;
        $oSpecialty->listorder=$request->input('listorder',0);
        $oSpecialty->save();
        //要删除
        $oSpecialty=Specialty::orderBy('id','desc')->first();
        $data=array('id'=>$oSpecialty->id,'parentid'=>$oSpecialty->parentid,'school_id'=>$school_id);
        $oSpecialty->afterInsert($data);//更新path
        return redirect('/setting/specialty')->withInput() ->withErrors(['msg' => '创建成功',]);
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
        $result=Specialty::get(['id','name','parentid','path','listorder']);
        $oObj = Specialty::where('school_id',$school_id)->where(['id'=>$id])->first();
        if($oObj){
            $string = '<select name="parentid" class="sel">';
            $string .= "<option value='0'>请选择父类</option>";
            $categorys=[];
            if(!empty($result)) {
                $result=$result->toArray();
                foreach($result as $r) {
                    $r['selected'] = $oObj->parentid==$r['id'] ? 'selected' : '';
                    $categorys[$r['id']] = $r;
                }
            }
            $str  = "<option value='\$id' \$selected>\$spacer \$name</option>";
            $tree = \App::make('tree');
            $tree->init($categorys);
            $string .= $tree->get_tree(0, $str);
            $string .= '</select>';
			if(view()->exists(session('mode').'.schoolplat.specialty.edit')){
				return View(session('mode').'.schoolplat.specialty.edit', compact('oObj','string','school_id'));
			}else{
				return View('default.schoolplat.specialty.edit', compact('oObj','string','school_id'));
			}
        }
        else{
            return back()->withInput()->withErrors(['msg' => '栏目不存在',]);
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
        $oSpecialty = Specialty::find($id);
        $parentid = $request->input('parentid',0);
        $oSpecialty->name=$request->input('name');
        $oSpecialty->parentid=$parentid;
        $oSpecialty->school_id=$school_id;
        $oSpecialty->listorder=$request->input('listorder',0);
        $oSpecialty->save();
        $data=array('id'=>$oSpecialty->id,'parentid'=>$oSpecialty->parentid,'path'=>$oSpecialty->path,'school_id'=>$school_id);
        $oSpecialty->afterUpdate($data);//更新path
        return redirect('/setting/specialty')->withInput() ->withErrors(['msg' => '修改成功',]);
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
            foreach($id as $val){
                $path=Specialty::where('id',$val)->value('path');
                
                $res = Specialty::where('id',$id)->first();
                $res1 = Specialty::where('parentid',$id)->first();
                if($res1){
                    $msg = ["msg"=> ["该院系下有子类，不能删除！"],];
                    return back()->withInput()->withErrors($msg);
                    //return redirect('error')->with(['msg'=>'该院系下有子类，不能删除！','href'=>app(UrlGenerator::class)->previous()]);
                }else{
                    Specialty::where("path","like",$path."%")->where('school_id',$school_id)->delete();
                }
                
            }
            return back();
        }
    }
}
