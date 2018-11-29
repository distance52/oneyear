<?php

namespace App\Http\Controllers\Administrator\Other;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use App\Models\NotifyTemplate;

class NotifyTemplateController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $oObjs = NotifyTemplate::orderBy('id','desc')->paginate(10);
		if (view()->exists(session('mode').'.other.notify_template.notify_template')){
			return View(session('mode').'.other.notify_template.notify_template', compact('oObjs'));
		}else{
			return View('default.other.notify_template.notify_template', compact('oObjs'));
		}
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
		 $oObjs = NotifyTemplate::orderBy('id','desc')->paginate(10);
		if (view()->exists(session('mode').'.other.notify_template.notify_template_create')){
			return View(session('mode').'.other.notify_template.notify_template_create',array("oData"=>(object)array()));
		}else{
			return View('default.other.notify_template.notify_template_create',array("oData"=>(object)array()));
		}
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Requests\NotifyTemplateRequest $request)
    {
        $oNotifyTemplate = new NotifyTemplate();
        $oNotifyTemplate->title = $request->input('title');
        $oNotifyTemplate->template = $request->input('template');
        $oNotifyTemplate->fields = $request->input('fields');
        $oNotifyTemplate->example = $request->input('example');
        $oNotifyTemplate->type = $request->input('type'); // 类型
        $oNotifyTemplate->save();
        return redirect('/other/notify_template')->withErrors([
            'msg' => '操作成功',
        ]);
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
        $oData = NotifyTemplate::where('id',$id)->first();
		if (view()->exists(session('mode').'.other.notify_template.notify_template_edit')){
			return View(session('mode').'.other.notify_template.notify_template_edit',compact("oData"));
		}else{
			return View('default.other.notify_template.notify_template_edit',compact("oData"));
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
        $oNotifyTemplate = NotifyTemplate::find($id);
        if($oNotifyTemplate) {
            $oNotifyTemplate->title = $request->input('title');
            $oNotifyTemplate->template = $request->input('template');
            $oNotifyTemplate->fields = $request->input('fields');
            $oNotifyTemplate->example = $request->input('example');
            $oNotifyTemplate->type = $request->input('type'); // 类型
            $oNotifyTemplate->save();
            return redirect('/other/notify_template')->withErrors([
                'msg' => '修改成功',
            ]);
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
            return back()->withInput()->withErrors([
                'msg' => '参数错误，非法操作',
            ]);
        } else {
            $oNotifyTemplate = new NotifyTemplate();
            $id=explode(',',$id);
            $oNotifyTemplate::whereIn('id', $id)->delete();
            return back();
        }
    }
}
