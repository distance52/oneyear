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
class CategoryController  extends Controller
{
    /*
     * 服务商
     * */
    public function index(Request $request)
    {
        $data=new ProviderCategory;
        $info=[];
        if(isset($_GET['id'])){
            $info=$data->find($request->id);//修改
        }
        if(isset($_GET['cid'])){
            $info=$data->find($request->cid);//修改
        }
        if(!empty($info)&&$info['pid']!=0){
            $arr=ProviderCategory::where('id',$info['pid'])->first();
        }else{
            $arr=$info;
        }
        $data=$data->where(['pid'=>0])->orderBy('id','desc')->paginate(6);
        foreach($data as $k=>&$v){
            $v['erji']=ProviderCategory::where(['pid'=>$v['id']])->get()->toArray();
        }
        return view('default.provider.category.index',compact('data','info','arr'));
    }


    /*
     * 服务商store 添加完成界面
     * */
    public function create(Request $request)
    {
        if($request->isMethod('post')){
            if($request->type==1){
                $arr=ProviderCategory::find($request->id);
                if ($request->hasFile('logo')) {
                    $arr->logo=uploadAvatar($request->file('logo'));
                }
                $arr->name=$request->name;
                $arr->desc=$request->desc;
                $arr->save();
            }else{
                if($request->id==""){
                    $data['pid']=0;
                }else $data['pid']=$request->id;
                if ($request->hasFile('logo')) {
                    $data['logo']=uploadAvatar($request->file('logo'));
                }
                $data['name']=$request->name;
                $data['desc']=$request->desc;
                ProviderCategory::insert($data);
            }

        }
        return redirect("/provider/category")->with('msg', '创建成功');

    }
    /*
     * 服务商 删除
     * */
    public function destroy($id)
    {
        $noDelete=ProviderCategory::where(['pid'=>$id])->get()->toArray();

        if(!empty($noDelete)){
            return response()->json(['msg'=>'该分类下有子分类，不能删除']);
        }else{
            ProviderCategory::where(['id'=>$id])->delete();
            return redirect("/provider/category")->with('msg', '删除成功');
        }

    }



}