<?php

namespace App\Http\Controllers\Administrator\System;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;

class UcenterController extends Controller
{
    public function index() {
        $oUcenter = config('ucenter');
		if (view()->exists(session('mode').'.system.ucenter')){
			return View(session('mode').'.system.ucenter', compact('oUcenter'));
		}else{
			return View('default.system.ucenter', compact('oUcenter'));
		}
    }

    public function store(Request $request) {
        //
        $file_path = storage_path('app/ucenter.data');
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
        return redirect('system/w_ucenter');

    }
}
