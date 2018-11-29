<?php

namespace App\Http\Controllers\Administrator\Member;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use App\Models\SqlLog;
use App\Models\User;

class LogController extends Controller
{

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request,$plat) {
        $aSearch = [];
        $email=$name= $begintime=$endtime=$ip=$where='';
        \Request::has('email') &&  $aSearch['email']=$email = \Request::input('email');
        \Request::has('name') &&  $aSearch['name']=$name = \Request::input('name');
        \Request::has('ip') &&  $aSearch['ip']=$ip = \Request::input('ip');
        \Request::has('begintime') && $aSearch['begintime']=$begintime = \Request::input('begintime');
        \Request::has('endtime') &&  $aSearch['endtime']=$endtime = \Request::input('endtime');
        $oObjs=SqlLog::where(['plat'=>$plat,'type'=>'login']);
        if($email!=''){
             $users=User::where("email","like",'%'.$email.'%')->pluck('id');
            if(count($users)){
                $oObjs = $oObjs->whereIn('user_id',$users);
            }else{
                $oObjs = $oObjs->whereIn('id',array(0));//不存在
            }
        }
        if($name!=''){
            $oObjs->where("name","like",'%'.$name.'%');
        }
        if($ip!=''){
            $oObjs->where("ip","like",'%'.$ip.'%');
        }
        if($begintime!=''){
            $oObjs->where("created_at",">=",$begintime);
        }
        if($endtime!=''){
            $oObjs->where("created_at","<=",$endtime);
        }
        $results=$oObjs->orderBy('id','desc');
		$num['a'] = $results->count();
		$results=$results->paginate(12);
		$num['b'] = $results->count();
        if (view()->exists(session('mode').'.users.log')){
			return View(session('mode').'.users.log', compact('results','aSearch','plat','num'));
		}else{
			return View('default.users.log', compact('results','aSearch','plat','num'));
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
        if($oObj = SqlLog::find($id)) {
            $oObj->delete();
            return back();
        }
    }
}
