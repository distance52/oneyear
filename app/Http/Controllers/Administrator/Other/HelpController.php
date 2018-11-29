<?php

namespace App\Http\Controllers\Administrator\Other;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use App\Models\Ext_help;
use App\Models\Tag;
use GatewayClient\Gateway as Gateway;
use App\Models\UserBind;
use App\Models\User;

// 系统公告
class HelpController extends Controller
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
        $lists = Ext_help::orderBy('id','desc');
        if($aSearch) {
            $lists = $lists->where('title','like', '%'.$aSearch['keyword'].'%');
        }
        $lists = $lists->paginate(20);
		if (view()->exists(session('mode').'.other.help.list')){
			return View(session('mode').'.other.help.list', compact('lists','aSearch'));
		}else{
			return View('default.other.help.list', compact('lists','aSearch'));
		}
    }
    /**
     * 显示创建平台公告的模板/显示修改
     * @return [type] [description]
     */
    public function create()
    {
		if (view()->exists(session('mode').'.other.help.add')){
			return View(session('mode').'.other.help.add');
		}else{
			return View('default.other.help.add');
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
        $help = new Ext_help;
        $help->title = $request->input('title');
        $help->content = $request->input('content');
		
        $help->plat = $request->input('plat');
		if(empty($help->plat)){
			
			$help->plat = [0];
		}
		$help->plat = json_encode($help->plat);

		$string = $request->input('route');
		$arr = array('1','2','3','4','5','6','7','8','9','0','/');
		$strlen = strlen($string);
		$route = '';
		for($i=0; $i<$strlen; $i++){
			if(!in_array($string[$i],$arr)){
				$route .= $string[$i];
			}
		}
		$help->route = $route;
        $help->hot = $request->reason;
        $help->save();
        // sync tags
        if($request->input('tags','')) {
            $oTag = new Tag;
            $oTag->syncTags($request->input('tags',''), $help);
        }
        return redirect('/other/help')->withInput()->withErrors(['msg' => '添加成功',]);
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
            $notice_data = Ext_help::where('id',$id)->with('tags','user')->first();
            if($notice_data){
				if (view()->exists(session('mode').'.other.help.show')){
					return View(session('mode').'.other.help.show',compact("notice_data"));
				}else{
					return View('default.other.help.show',compact("notice_data"));
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
        $oObj = Ext_help::where('id',$id)->with(['tags','user'])->first();
		$oObj->plat = json_decode($oObj->plat);
		
		$tags='';
        if($oObj->tags)
		{
            $t = json_decode($oObj->tags);
            if(!empty($t)){
                foreach($t as $tag) {
                    $tarr[] = $tag->name;
                }
                $tags = implode(",",$tarr);
            }
        }
		
		if (view()->exists(session('mode').'.other.help.edit')){
			return View(session('mode').'.other.help.edit', compact("oObj",'tag'));
		}else{
			return View('default.other.help.edit', compact("oObj",'tag'));
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
        $help = Ext_help::find($id);
        $help->title = $request->input('title');
        $help->content = $request->input('content');
        $help->plat = $request->input('plat');
		if(empty($help->plat)){
			
			$help->plat = [0];
		}
        $help->plat = json_encode($help->plat);
        
		$string = $request->input('route');
		$arr = array('1','2','3','4','5','6','7','8','9','0','/');
		$strlen = strlen($string);
		$route = '';
		for($i=0; $i<$strlen; $i++){
			if(!in_array($string[$i],$arr)){
				$route .= $string[$i];
			}
		}
		$help->route = $route;
		
        $help->save();
        return redirect('/other/help')->withInput()->withErrors(['msg' => '修改成功',]);
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
                $oNotice = Ext_help::find($val);
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
                $oNotice = Ext_help::find($val);
                $oNotice->is_show=1;
                $oNotice->save();
            }
            return back();
        }
    } 
	public function look(Request $request)
    {
		$oUser = \Auth::user();
		$string = $request->url;
		$arr = array('1','2','3','4','5','6','7','8','9','0','/');
		$strlen = strlen($string);
		$route = '';
		for($i=0; $i<$strlen; $i++){
			if(!in_array($string[$i],$arr)){
				$route .= $string[$i];
			}
		}
		$data = Ext_help::where('route',$route)->where('plat','like', '%'.$oUser->plat.'%')->first();
		return View('default.help',compact("data"));
    }

    public function trash(){
        
        $aSearch = [];
        \Request::has('keyword') && \Request::input('keyword') && $aSearch['keyword'] = \Request::input('keyword');
        $lists = Ext_help::orderBy('id','desc');
        if($aSearch) {
            $lists = $lists->where('title','like', '%'.$aSearch['keyword'].'%');
        }
        $lists = $lists->onlyTrashed()->paginate(20);
        if (view()->exists(session('mode').'.other.help.del')){
            return View(session('mode').'.other.help.del', compact('lists','aSearch'));
        }else{
            return View('default.other.help.del', compact('lists','aSearch'));
        }
    }

    public function doTrash($id){
        $type = \Request::input('type');
        if($type){
            $oNotice = Ext_help::withTrashed()->find($id);
            $oNotice->restore();
        }else{
            $ids=explode(',',$id);
            foreach($ids as $val){
                $oNotice = Ext_help::withTrashed()->find($val);
                $oNotice->forceDelete();
            }
        }
        return redirect()->back();
    }

    public function delClient($client_id){
       $isOnline =  Gateway::isOnline($client_id);
        if (!$isOnline){
            return response()->json(array('state'=>false , 'msg'=>'参数错误'));
        }
        $res = Gateway::closeClient($client_id);
        if ($res){
            return response()->json(array('state'=>true , 'msg'=>'ok'));
        }
    }
    
    public function change(){
        $id = \Auth::user()->id;
        $fromId = UserBind::where(['type'=>'weixin','toId'=>$id])->value('fromId');

        $oBind = UserBind::where(['type'=>'weixin','fromId'=>$fromId])->get();
        $mobile = User::whereId($id)->value('mobile');
        $oUser = User::where('mobile',$mobile)->get();
        $bind_list = [];
//        $plat = ['管理员', '学校管理员', '老师', '学生'];
        $plat = ['管理员', '学校管理员', '老师', '学生','其他'];
        if ($oBind) {

                $oBind->each(function ($oBind, $i) use (&$bind_list, $plat, $id) {
                    $oUser = User::whereId($oBind->toId)->first();
                    $bind_list[$i]['id'] = $oBind->toId;
                    $bind_list[$i]['name'] = $oUser->name;
                    $bind_list[$i]['email'] = $oUser->email;
                    $bind_list[$i]['mobile'] = $oUser->mobile;
                    $bind_list[$i]['plat'] = $plat[$oUser->plat];
                    $bind_list[$i]['avatar'] = getAvatar($oUser->avatar);
                    $bind_list[$i]['type'] = $oBind->type == 'weixin' ? '微信' : '其他';
                    $bind_list[$i]['online'] = $oUser->id == $id ? 1 : 0;
                });
        }

        if ($oUser){
            $oUser->each(function ($oUser) use (&$bind_list, $plat, $id) {

                $arr = [];
                $arr['id'] = $oUser->id;
                $arr['name'] = $oUser->name;
                $arr['email'] = $oUser->email;
                $arr['mobile'] = $oUser->mobile;
                $arr['plat'] = $plat[$oUser->plat];
                $arr['avatar'] = getAvatar($oUser->avatar);
                $arr['type'] = '手机';
                $arr['online'] = $oUser->id == $id ? 1 : 0;
                array_push($bind_list, $arr);
            });
        }

        $list = [];
        $lists = [];
        if($bind_list){
            foreach ($bind_list as $key => $v){
                if (!in_array($v['id'], $list)){
                    $lists[] = $bind_list[$key];
                }
                $list[$v['id']]=$v['id'];
            }
        }

        if ($lists){
            return response()->json(array('state'=>true , 'msg' => 'ok' , 'data'=>$lists));
        }else{
            return response()->json(array('state'=>true , 'msg' => 'no data' , 'data'=>null));
        }
    }

    public function doChange(Request $request){
        $id= $request->input('id',0);
        if (!$id){
            return response()->json(array('state'=>false , 'msg' => 'no data' , 'data'=>null));
        }
        $oUser = \Auth::user();
        $fromId = UserBind::where(['type'=>'weixin','toId'=>$oUser->id])->value('fromId');
        $user_fromId = UserBind::where(['type'=>'weixin','toId'=>$id])->value('fromId');
        $user = User::whereId($id)->first();
        if($fromId != $user_fromId && $oUser->mobile != $user->mobile){
            return response()->json(array('state'=>false , 'msg' => 'error' , 'data'=>null));
        }
        \Auth::logout();
        \Auth::loginUsingId($id);
        $list = ['admin','school','teacher','student'];
        $plat = User::whereId($id)->value('plat');
        $url = env('APP_PRO')."{$list[$plat]}.".env('APP_SITE');
//        return redirect($url);
        return response()->json(array('state'=>true , 'msg' => 'ok' , 'data'=>$url));

    }

}
