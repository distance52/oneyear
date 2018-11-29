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
class ProviderController  extends Controller
{
    public function listSquad(){
        $count=[];
        $count['providerCount']=Provider::count('id');
        $count['studentCount']=ProviderReservation::count('id');
        return view('default.provider.provider.listsquad',compact('data'));

    }

    /*
     * 服务商
     * */
    public function index()
    {
        $aSearch = [];
        $name=$where='';
//        \Request::has('email') &&  $aSearch['email']=$email = \Request::input('email');
        \Request::has('name') &&  $aSearch['name']=$name = \Request::input('name');
        $data= Provider::where('type',1) ;
//            if($email!=""){
//                $data=$data->where('email','like', '%'.$email.'%');
//            }
            if($name!=''){
                $data = $data->where('name','like', '%'.$name.'%');
            }
        $data=$data->orderBy('id','desc')->paginate(12);
            foreach($data as $k=>&$v){
                $cate=ProviderOnCategory::where(['provider_id'=>$v['id'],'status'=>1])->pluck('category_id')->toArray();
                $v['cate']=implode("、",ProviderCategory::whereIn('id',$cate)->where("pid",'!=',0)->pluck('name')->toArray());
            }
        return view('default.provider.provider.index',compact('data','aSearch'));
    }
    /*
     * 服务商 分类 展示
     * */
    public function show($id)
    {
        $data=Provider::find($id);
        $cate_id=ProviderOnCategory::where('provider_id',$id)->pluck('category_id')->toArray();
        $data['cate_name']=ProviderCategory::whereIn('id',$cate_id)->pluck('name')->toArray();
        return view('default.provider.provider.show',compact('data'));
    }
//    /*
//     * 服务商 edit修改查询
//     * */
//    public function edit($id)
//    {
//        $data=Provider::find($id);
//        $data['cate']=ProviderCategory::where('provider_id',$id)->pluck('name')->toArray();
//        return view('default.provider.provider.edit',compact('data'));
//    }
//    /*
//     * 服务商 update修改
//     * */
//    public function update($id,Request $request)
//    {
//        if(!$id){
//            return response()->json(['msg'=>'参数错误']);
//        }
//        $data=Provider::find($id);
//        ProviderOnCategory::where('provider_id',$id)->delete();
//        foreach($request->cate_id as $k=>$v){
//            $insert=new ProviderOnCategory;
//            $insert->provider_id=$id;
//            $insert->category_id=$v;
//            $insert->create();
//        }
//        unset($request->cate_id);
//        $data->save($request->all());
////        return view('default.provider.category.update',compact('data'));
//        return redirect("/Provider/provider/index")->with('msg', '修改成功');
//
//    }
    /*
     * 服务商 删除
     * */
    public function destroy($id)
    {
        $provider=Provider::find($id);
        $provider->type=0;
        $provider->save();
        ProviderSchoolProvider::where(['provider_id'=>$id])->delete();
        return redirect("/provider/provider")->with('msg', '删除成功');

    }
//    public function getProviderProcess($id,Request $request)
//    {
//        if($request->isMethod('post')){
//            $info=ProviderSchoolProcess::find($id);
//            $data['process']=json_decode($info['process'],true);
//
//            $data['all']=ProviderCategory::where(['pid'=>0])->orderBy('id','desc')->select('id','name')->get();
//            foreach($data['all'] as $k=>&$v){
//                $v['erji']=ProviderCategory::where(['pid'=>$v['id']])->select('id','name')->get()->toArray();
//            }
//            return response()->json(['data'=>$data,'status'=>true]);
//        }
//    }

        public function referPro($school_id,Request $request){
            $school=School::find($school_id);
            $provider=Provider::where(['province'=>$school['province'],'city'=>$school['city']])->get()->toArray();
            foreach($provider as $k=>$v){
                $data=ProviderSchoolProvider::where(['school_id'=>$school_id,'provider_id'=>$v['id']])->withTrashed()->first();
                if($v&&$data['deleted_at']==''){
                    ProviderSchoolProvider::updateOrCreate(['school_id'=>$school_id,'provider_id'=>$v['id']],['type'=>1]);
                }
            }
            return response()->json(['status'=>true,'data'=>'ok']);
        }



}