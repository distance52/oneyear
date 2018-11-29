<?php

namespace App\Http\Controllers\Administrator\Other;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use App\Models\Notice;
use App\Models\Tag;
// 系统公告
class NoticeController extends Controller
{

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $aSearch = [];
        \Request::has('keyword') && \Request::input('keyword') && $aSearch['keyword'] = \Request::input('keyword');
        $lists = Notice::orderBy('id','desc');
        if($aSearch) {
            $lists = $lists->where('title','like', '%'.$aSearch['keyword'].'%');
        }
        $lists = $lists->orderBy("send_time","desc")->paginate(20);
		if (view()->exists(session('mode').'.other.notice.notice')){
			return View(session('mode').'.other.notice.notice', compact('lists','aSearch'));
		}else{
			return View('default.other.notice.notice', compact('lists','aSearch'));
		}
    }
    /**
     * 显示创建平台公告的模板/显示修改
     * @return [type] [description]
     */
    public function create()
    {
		if (view()->exists(session('mode').'.other.notice.notice_add')){
			return View(session('mode').'.other.notice.notice_add',array("oObj"=>(object)array("is_show"=>0)));
		}else{
			return View('default.other.notice.notice_add',array("oObj"=>(object)array("is_show"=>0)));
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
        //
        $oNotice = new Notice;
        $oNotice->title = $request->input('title');
        $oNotice->content = $request->input('content');
        $oNotice->is_show = $request->input('is_show');
//        $oNotice->send_time = $request->input('send_time');
        $oNotice->send_time = date('Y-m-d H:i:s',time());
        $oNotice->user_id = \Auth::user()->id;
        $oNotice->desc = $request->input('desc');
        $oNotice->save();
        // sync tags
        if($request->input('tags','')) {
            $oTag = new Tag;
            $oTag->syncTags($request->input('tags',''), $oNotice);
        }
        return redirect('/other/notice')->withInput()->withErrors(['msg' => '添加成功',]);
    }

    /**
     * Display the specified resource.
     * 展示单条
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        if($id){
            $notice_data = Notice::where('id',$id)->with('tags','user')->first();
            if($notice_data){
				if (view()->exists(session('mode').'.other.notice.notice_show')){
					return View(session('mode').'.other.notice.notice_show',compact("notice_data"));
				}else{
					return View('default.other.notice.notice_show',compact("notice_data"));
				}
            }
            else {
                return back()->withInput()->withErrors(['msg' => '公告不存在',]);
            }
        }
        else{
            return back()->withInput()->withErrors(['msg' => '参数缺失',]);
        }
    }
    /**
     * [edit description]
     * @param  [type] $id [description]
     * @return [type]     [description]
     */
    public function edit($id)
    {
        $oObj = Notice::where('id',$id)->with(['tags','user'])->first();
        $tags='';
        if($oObj->tags){
            $t = json_decode($oObj->tags);
            if(!empty($t)){
                foreach($t as $tag) {
                    $tarr[] = $tag->name;
                }
                $tags = implode(",",$tarr);
            }
        }
        $oObj->send_time = strtotime($oObj->send_time)>time()?$oObj->send_time:date("Y-m-d H:i:s" , time()+86400);
		if (view()->exists(session('mode').'.other.notice.notice_edit')){
			return View(session('mode').'.other.notice.notice_edit', compact("oObj","tags"));
		}else{
			return View('default.other.notice.notice_edit', compact("oObj","tags"));
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

        $oNotice = Notice::find($id);
        if(!$oNotice) {
            return back()->withInput()->withErrors(['msg' => '"请求错误，没有指定id',]);
        }
        $oNotice->title = $request->input('title');
        $oNotice->content = $request->input('content');
        $oNotice->is_show = $request->input('is_show');
//        $oNotice->send_time = $request->input('send_time');
        $oNotice->send_time = date('Y-m-d H:i:s',time());
        $oNotice->user_id = \Auth::user()->id;
        $oNotice->desc = $request->input('desc');
        $oNotice->save();
        //
        if($request->input('tags','')) {
            $oTag = new Tag;
            $oTag->syncTags($request->input('tags',''), $oNotice);
        }
        return redirect('/other/notice')->withInput()->withErrors(['msg' => '修改成功',]);
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
            $msg = [
                "msg"=> ["参数错误，非法操作"],
            ];
            return back()->withInput()->withErrors($msg);
        } else {
            $ids=explode(',',$id);
            foreach($ids as $val){
                $oNotice = Notice::find($val);
                $oNotice->tags()->detach();
                $oNotice->delete();
            }
            return back();
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function audit($id)
    {
        if(!$id) {
            $msg = [
                "msg"=> ["参数错误，非法操作"],
            ];
            return back()->withInput()->withErrors($msg);
        } else {
            $ids=explode(',',$id);
            foreach($ids as $val){
                $oNotice = Notice::find($val);
                $oNotice->is_show=1;
                $oNotice->save();
            }
            return back();
        }
    }

    public function trash(){
        $aSearch = [];
        \Request::has('keyword') && \Request::input('keyword') && $aSearch['keyword'] = \Request::input('keyword');
        $lists = Notice::orderBy('id','desc');
        if($aSearch) {
            $lists = $lists->where('title','like', '%'.$aSearch['keyword'].'%');
        }
        $lists = $lists->onlyTrashed()->orderBy("send_time","desc")->paginate(20);
        if (view()->exists(session('mode').'.other.notice.notice_del')){
            return View(session('mode').'.other.notice.notice_del', compact('lists','aSearch'));
        }else{
            return View('default.other.notice.notice_del', compact('lists','aSearch'));
        }
    }

    public function doTrash($id){
        $type = \Request::input('type');
        if($type){
            $oNotice = Notice::withTrashed()->find($id);
            $oNotice->restore();
        }else{
            $ids=explode(',',$id);
            foreach($ids as $val){
                $oNotice = Notice::withTrashed()->find($val);
                $oNotice->forceDelete();
            }
        }
        return redirect()->back();
    }
}
