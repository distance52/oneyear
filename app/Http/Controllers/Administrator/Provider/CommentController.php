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
class CommentController  extends Controller
{
    /*
     * 服务商
     * */
    public function index(Request $request)
    {
        $aSearch = [];
        $name='';
        \Request::has('name') &&  $aSearch['name']=$name = \Request::input('name');
        $data=ProviderReservation::where(['status'=>3,'type'=>1]);
        $data=$data->with(
            ['school'=>function($query){$query->select('id','name');}],
            ['provider'=>function($query){$query->select('id','name');}],
            ['user'=>function($query)use($name){$query->select('id','name');}]);
        if($name!=""){
            $data=$data->whereHas('user',function($query)use($name,$data){
                $query->where('name','like','%'.$name.'%');
            });
        }
        $data = $data->orderBy('id','desc')->paginate(12);
        return view('default.provider.comment.index',compact('data','aSearch'));
    }
    public function destroy($id){
        $desc=ProviderReservation::find($id);
        $desc->type=0;
        $desc->save();
        return redirect('/provider/comment');
    }






}