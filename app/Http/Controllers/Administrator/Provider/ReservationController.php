<?php

namespace App\Http\Controllers\Administrator\Provider;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Session;
use App\Models\Region;
use App\Models\Provider;
use App\Models\ProviderLog;
use App\Models\ProviderCategory;
use App\Models\ProviderOnCategory;
use App\Models\ProviderReservation;
use App\Models\ProviderSchoolProcess;
use App\Models\ProviderSchoolProvider;
class ReservationController  extends Controller
{
    /*
     * 服务商预约
     * */
    public function index(Request $request)
    {
        $aSearch = [];
        $name='';
        \Request::has('name') &&  $aSearch['name']=$name = \Request::input('name');
        $data=ProviderReservation::with(
            ['school'=>function($query){$query->select('id','name');}],
            ['provider'=>function($query){$query->select('id','name');}],
            ['user'=>function($query)use($name){$query->select('id','name');}]);
        if($name!=""){
            $data=$data->whereHas('user',function($query)use($name,$data){
                $query->where('name','like','%'.$name.'%');
            });
        }
        $data = $data->orderBy('id','desc')->paginate(12);
        return view('default.provider.reservation.index',compact('data','aSearch'));
    }
    /*
     * 服务商 分类 展示
     * */
    public function show($id)
    {
        $data=ProviderReservation::where('id',$id)->with(
            ['school'=>function($query){$query->select('id','name');}],
            ['provider'=>function($query){$query->select('id','name');}],
            ['user'=>function($query){$query->select('id','name');}])
            ->first();
        return view('default.provider.reservation.show',compact('data'));
    }
    /*
     * 服务商 删除
     * */
    public function destroy($id)
    {
        $provider=ProviderReservation::find($id);
        $provider->delete();
        return redirect("/provider/reservation/index")->with('msg', '删除成功');
    }
    //预约同意拒绝
    public function saveStatus($id,Request $request){
        $data=ProviderReservation::find($id);
        if($data->status!=0){
            return response()->json(['msg'=>'已审核','status'=>false]);
        }
        $data->status=$request->status;
        $data->save();
        return response()->json(['msg'=>'ok','status'=>true]);

    }



}