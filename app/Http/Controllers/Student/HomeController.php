<?php

namespace App\Http\Controllers\Student;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Models\Student;
use DB;
use App\Models\Squad;
use App\Http\Controllers\Controller;
use Illuminate\Routing\UrlGenerator;
use App\Models\NodeSquad;
use App\Models\Teaching\Plan;
use App\Models\Teaching\Module;
use App\Models\Teaching\Node;

class HomeController extends Controller
{
    public function index() {
//        $token = get_access_token();
//        $jsaip_url="https://api.weixin.qq.com/cgi-bin/ticket/getticket?access_token=".$token."&type=jsapi";
//
//        $jsapi  =json_decode(file_get_contents($jsaip_url), true);
//        $jsapiTicket = $jsapi['ticket'];
//        $nonceStr = "";
//        $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
//        for ($i = 0; $i < 16; $i++)
//        {
//            $nonceStr .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
//        }
//        $timestamp=time();
//        $url=env("APP_PRO")."$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
//        $string="jsapi_ticket=$jsapiTicket&noncestr=$nonceStr&timestamp=$timestamp&url=$url";
//        $signature=sha1($string);
//        $oBind = config('login_bind');
//        $signPackage = array(
//            "appId"     => $oBind['weixinmob_key'],
//            "nonceStr"  => $nonceStr,
//            "timestamp" => $timestamp,
//            "url"       => $url,
//            "signature" => $signature,
//            "rawString" => $string
//        );
        $signPackage=[];
        if (view()->exists(session('mode').'.studentPlat.home')){
            return View(session('mode').'.studentPlat.home',compact('signPackage'));
        }else{
            return View('default.studentPlat.home',compact('signPackage'));
        }
    }

    public function indexs() {
    	$oUser = \Auth::user();
    	$oStruct = \DB::table('squad_structs')->where('type',1)->where('struct_id',$oUser->student->id)->pluck('squad_id');
    	$oSquad = [];
        foreach ($oStruct as $k=>$v) {
            $squad =  Squad::where('id',$v)->first();
//            dd($squad->type);
            if($squad&&$squad['type'] != 10){ //验证非删除状态
                $oSquad[$k]['id'] = $v;
                $oSquad[$k]['name'] = $squad->name;
                $squadNode = NodeSquad::where('squad_id',$v)->orderBy('id');
                $all_counts = $squadNode->count();

                $squadNode = $squadNode->where('type',1);
                $node_counts = $squadNode->count();
                if($node_counts && $all_counts){
                    $oSquad[$k]['time'] = ceil(($node_counts/$all_counts)*100).'%';
                }else{
                    $oSquad[$k]['time']= 0;
                }
                $squadNode = $squadNode->where('node_id','<>',0)->first();
                if(!$squadNode){
                    $squadNode = NodeSquad::where('squad_id',$v)->where('type',1)->orderBy('id')->first();
                    if($squadNode){
                        $squadNode_id = $squadNode->module_id;

                        $oSquad[$k]['node_name'] = Module::find($squadNode_id)->first()->name;
                    }else{
                        $oSquad[$k]['node_name'] = '暂未设置';
                    }
                }else{
                    $oSquad[$k]['node_name']= Node::find($squadNode->node_id)->first()->name;
                }
            }
        }

		if (view()->exists(session('mode').'.studentPlat.homes')){
			return View(session('mode').'.studentPlat.homes',compact('oSquad'));
		}else{
			return View('default.studentPlat.homes',compact('oSquad'));
		}
    }
    public function weixinscanattendance(){
//        $http = new \App\Http\Controllers\NoticeSend\NoticeHttp;
        $token = get_access_token();
        $jsaip_url="https://api.weixin.qq.com/cgi-bin/ticket/getticket?access_token=".$token."&type=jsapi";

        $jsapi  =json_decode(file_get_contents($jsaip_url), true);
        $jsapiTicket = $jsapi['ticket'];
        $nonceStr = "";
        $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
        for ($i = 0; $i < 16; $i++)
        {
            $nonceStr .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
        }
        $timestamp=time();
        $url=env("APP_PRO")."$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
        $string="jsapi_ticket=$jsapiTicket&noncestr=$nonceStr&timestamp=$timestamp&url=$url";
        $signature=sha1($string);
        $oBind = config('login_bind');
        $signPackage = array(
            "appId"     => $oBind['weixinmob_key'],
            "nonceStr"  => $nonceStr,
            "timestamp" => $timestamp,
            "url"       => $url,
            "signature" => $signature,
//            "rawString" => $string
        );
        return View('default.studentPlat.scan.index',compact('signPackage'));
//        $oUser = \Auth::user();
//        $oStudent = Student::where('user_id',$oUser->id)->first();
//        $oObjs = \DB::table('sign_log')->where('student_id',$oStudent->id)->orderBy('time','desc')->get();
//
    }
}
