<?php

namespace App\Http\Controllers\Administrator\System;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;

class OssController extends Controller
{
  //
  public function index() {
    //return response()->json(config('alioss'));
    $oOss = config('alioss');
    
		if (view()->exists(session('mode').'.system.oss')){
			return View(session('mode').'.system.oss', compact('oOss'));
		}else{
			return View('default.system.oss', compact('oOss'));
		}
  }

  public function store(Request $request) {
    //
    // dd($request);
    $file_path = storage_path('app/alioss.data');
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
    return redirect('system/w_oss');
    
  }
}
