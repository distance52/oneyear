<?php

namespace App\Http\Controllers\Administrator\System;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;

class EmailController extends Controller
{

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index() {
      //
        $oMail = config('mail');
        // dd($oMail);
		if (view()->exists(session('mode').'.system.email')){
			return View(session('mode').'.system.email', compact('oMail'));
		}else{
			return View('default.system.email', compact('oMail'));
		}
    }

    /**
     * Store a newly created resource in storage.
     * 提交创建|修改
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request) {
      $file_path = storage_path('app/email.data');
      //
      $fp = fopen($file_path,'w+');
      if(!$fp) {
        \Log::error('没有权限创建文件：'.$file_path);
        $msg = [
            "custom-msg"=> ["修改失败，没有权限"],
          ];
        return response()->json($msg)->setStatusCode(422);
      }
      fwrite($fp, json_encode($request->all()));
      fclose($fp);
      return redirect('system/w_email');
    }
}
