<?php

namespace App\Http\Controllers\Administrator\System;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use App\Http\Requests\BasicRequest;

class BasicController extends Controller
{

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $oBasic = config('basic');
		if (view()->exists(session('mode').'.system.basic')){
			return View(session('mode').'.system.basic', compact('oBasic'));
		}else{
			return View('default.system.basic', compact('oBasic'));
		}
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Requests\BasicRequest $request)
    {
        //
        $file_path = storage_path('app/basic.data');
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
        return redirect('system/w_basic');
    }
}
