<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;

class OtherController extends Controller
{
    //
    public function getlists($model) {
        if(!\Auth::check()) {
            $msg = [
              "custom-msg"=> ["未登录，操作失败"],
          ];
          return response()->json($msg)->setStatusCode(422);
        }
        if(!$model) {
            $msg = [
              "custom-msg"=> ["非法操作"],
          ];
          return response()->json($msg)->setStatusCode(422);
        }
        $class = $this->getClass($model);
        if(!class_exists($class)) {
             $msg = [
              "custom-msg"=> ["非法操作"],
          ];
          return response()->json($msg)->setStatusCode(422);
        }
        //
        return $this->getSearch($class, \Auth::user());
    }

    private function getClass($model) {
        $teaching = ['module','cell','node','plan'];
        $module = strtolower($model);
        if(in_array($model, $teaching)) {
            return 'App\Models\Teaching\\'.ucfirst($module);
        } else {
            return 'App\Models\\'.ucfirst(strtolower($model));
        }
    }
    // 搜索
    private function getSearch($class, $oUser) {
        $oUser = \Auth::user();
        $name = $tag = '';
        //
        \Request::has('name') && \Request::input('name') && $name = \Request::input('name');
        \Request::has('tag') && \Request::input('tag') && $tag = \Request::input('tag');
        //
        $oObjs = new $class;
        if($name) {
            $oObjs = $oObjs->where('name', 'like', '%' . $name . '%');
        }
        if($tag) {
            $oObjs = $oObjs->whereHas('tags', function($q) use ($tag) {
                $q->where('name', $tag);
            });
        }
        $oObjs = $oObjs->where('school_id', $oUser->school_id);
        return $oObjs->select('id','name')->paginate(20);
    }

    public function getTaglists() {
        if(!\Auth::check()) {
            $msg = [
              "custom-msg"=> ["未登录，操作失败"],
          ];
          return response()->json($msg)->setStatusCode(422);
        }
        $name = '';
        \Request::has('name') && \Request::input('name') && $name = \Request::input('name');
        $oObjs = new \App\Models\Tag;
        if($name) {
            $oObjs = $oObjs->where('name', 'like', $name . '%');
        }
        return $oObjs->select('id','name')->paginate(20);
    }
}
