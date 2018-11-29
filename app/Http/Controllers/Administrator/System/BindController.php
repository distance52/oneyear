<?php

namespace App\Http\Controllers\Administrator\System;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;

class BindController extends Controller
{
    public function index() {
        //return response()->json(config('alioss'));
        $oBind = config('login_bind');
		if (view()->exists(session('mode').'.system.bind')){
			return View(session('mode').'.system.bind', compact('oBind'));
		}else{
			return View('default.system.bind', compact('oBind'));
		}
    }

    public function store(Request $request) {
        //
        $file_path = storage_path('app/login_bind.data');
        //
        $fp = fopen($file_path,'w+');
        if(!$fp) {
            \Log::error('没有权限创建文件：'.$file_path);
            $msg = [
                "custom-msg"=> ["修改失败"],
            ];
            return response()->json($msg)->setStatusCode(422);
        }
        fwrite($fp, json_encode($request->all()));
        fclose($fp);
        return redirect('system/w_bind');
    }
}
