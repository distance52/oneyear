<?php

namespace App\Http\Controllers\Administrator\Member;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use App\Models\User;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $plat = $request->has('plat')? $request->input('plat'): null;
        $q = $request->has('q')? $request->input('q'): null;
        $oObjs=new User();
        if(!is_null($q)){
            $oObjs=$oObjs->where("name","like",'%'.$q.'%');
        }
        if(is_null($plat)) {
            return $oObjs->with(['school','role'])->paginate(20);
        } else {
            return $oObjs->where('plat', $plat)->with(['school','role'])->paginate(20);
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request) {
        $oUser = new User();
        $oUser->name = $request->input('name');
        $oUser->email = $request->input('email');
        $oUser->password = bcrypt($request->input('password'));
        $oUser->plat = $request->input('plat');
        if($request->has('role_id')) {
            $oUser->role_id = $request->input('role_id');
        }
        $oUser->save();
        return response()->json(null);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        if(!$id) {
            $msg = [
                "custom-msg"=> ["参数错误，非法操作"],
            ];
            return response()->json($msg)->setStatusCode(422);
        } else {
            return response()->json(User::whereId($id)->with('role','school')->first());
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $oUser = User::find($id);
        if($oUser) {
            $oUser->name = $request->input('name');
            $oUser->email = $request->input('email');
            $oUser->password = bcrypt($request->input('password'));
            $oUser->plat = $request->input('plat');
            if($request->has('role_id')) {
                $oUser->role_id = $request->input('role_id');
            }
            $oUser->save();
            return response()->json(null);
        } else {
          $msg = [
              "custom-msg"=> ["参数错误，非法操作"],
          ];
          return response()->json($msg)->setStatusCode(422);
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
        if(!$id) {
            $msg = [
              "custom-msg"=> ["参数错误，非法操作"],
          ];
          return response()->json($msg)->setStatusCode(422);
        } else {
            User::find($id)->delete();
            return response()->json(null);
        }
    }

}
