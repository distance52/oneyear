<?php

namespace App\Http\Controllers\Usercenter;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use App\Models\Notice;

class NoticeController extends Controller
{
    //公告列表
    public function index()
    {
        $aSearch = [];
        \Request::has('keyword') && \Request::input('keyword') && $aSearch['keyword'] = \Request::input('keyword');
        $lists = Notice::where('is_show',1)->orderBy('id','desc');
        if($aSearch) {
            $lists = $lists->where('title','like', '%'.$aSearch['keyword'].'%');
        }
        $lists = $lists->orderBy("send_time","desc")->paginate(20);
		if (view()->exists(session('mode').'.usercenter.notice.notice')){
			return View(session('mode').'.usercenter.notice.notice', compact('lists','aSearch'));
		}else{
			return View('default.usercenter.notice.notice', compact('lists','aSearch'));
		}
    }

    //公告查看
    public function show($id)
    {
        if($id){
            $notice_data = Notice::where(['id'=>$id,'is_show'=>1])->with('tags','user')->first();
            if($notice_data){
                $notice_data->increment('view');
			if (view()->exists(session('mode').'.usercenter.notice.notice_show')){
				return View(session('mode').'.usercenter.notice.notice_show', compact('notice_data'));
			}else{
				return View('default.usercenter.notice.notice_show', compact('notice_data'));
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
}
