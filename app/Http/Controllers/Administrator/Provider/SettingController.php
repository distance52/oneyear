<?php

namespace App\Http\Controllers\Administrator\Provider;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Session;
use App\Models\Region;
use App\Models\School;
use App\Models\Provider;
use App\Models\ProviderLog;
use App\Models\ProviderCategory;
use App\Models\ProviderOnCategory;
use App\Models\ProviderReservation;
use App\Models\ProviderSchoolProcess;
use App\Models\ProviderSchoolProvider;
class SettingController  extends Controller
{
    /*
     * 管理员给学校设置服务商分类
     * */
    public function index()
    {
        $aSearch=[];
        $name='';
        \Request::has('name') && \Request::input('name') && $aSearch['name'] =$name= \Request::input('name');
        $data=ProviderSchoolProcess::with('school');
        if($name!=""){
            $data=$data->whereHas('school',function($query)use($name,$data){
                $query->where('name','like','%'.$name.'%');
            });
        }
        $data=$data->paginate(12);
        foreach($data as &$v){
            $process=json_decode($v['process'],true);
            $v['process']=implode('、',ProviderCategory::whereIn('id',$process)->where("pid",'!=',0)->pluck('name')->toArray());
        }
        return view('default.provider.setting.index',compact('data','aSearch'));
    }
    public function update($id,Request $request)
    {
        $data=ProviderSchoolProcess::find($id);//duo
        $cate=$oneprocess=[];
        for($i=0;$i<count($request->process);$i++){
            $cate[]=intval($request->process[$i]);
        }
        for($i=0;$i<count($request->oneprocess);$i++){
            $oneprocess[]=intval($request->oneprocess[$i]);
        }
        $data->process=json_encode($cate);
        $data->oneprocess=json_encode($oneprocess);
        $data->save();
        return redirect('/provider/setting');
    }

//    /*
//     * 管理员给学校设置服务商 删除
//     * */
//    public function destroy($id)
//    {
//        if(!$id){
//            return response()->json(['msg'=>'参数错误']);
//        }
//        $noDelete=ProviderSchoolProcess::find($id);
//        $noDelete->delete();
//        return redirect("/provider/category/index")->with('msg', '删除成功');
//    }

    public function getSchoolProcess($id,Request $request){
        if($request->isMethod('post')){
            $info=ProviderSchoolProcess::find($id);
            $data['process']=json_decode($info['process'],true);
            $data['oneprocess']=json_decode($info['process'],true);

            $data['all']=ProviderCategory::where(['pid'=>0])->orderBy('id','desc')->select('id','name')->get();
            foreach($data['all'] as $k=>&$v){
                $v['erji']=ProviderCategory::where(['pid'=>$v['id']])->select('id','name')->get()->toArray();
            }
            return response()->json(['data'=>$data,'status'=>true]);
        }

    }



}