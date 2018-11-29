<?php

namespace App\Http\Controllers\Administrator\Provider;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Session;
use App\Models\File;
use App\Models\Region;
use App\Models\Provider;
use App\Models\ProviderLog;
use App\Models\ProviderCategory;
use App\Models\ProviderOnCategory;
use App\Models\ProviderReservation;
use App\Models\ProviderSchoolProcess;
use App\Models\ProviderSchoolProvider;
class CatedescController  extends Controller
{
    /*
     * 服务商
     * */
    public function index(Request $request)
    {
        $aSearch=[];
        $name='';
        \Request::has('name') && \Request::input('name') && $aSearch['name'] =$name= \Request::input('name');
        $data=new ProviderCategory;
        if($name!=""){
            $data=$data->where('name','like','%'.$aSearch['name'].'%');
        }
        $data=$data->orderBy('id','desc')->paginate(10);
        foreach($data as $k=>&$v){
            $v['yiji']=ProviderCategory::where('id',$v['pid'])->value('name');
        }
        return view('default.provider.catedesc.index',compact('data','aSearch'));
    }
    /*
     * 服务商 edit修改查询
     * */
    public function edit($id)
    {
        $data=ProviderCategory::find($id);
        return view('default.provider.catedesc.edit',compact('data'));
    }
    /*
     * 服务商 update修改
     * */
    public function update($id,Request $request)
    {
        if(!$id){
            return response()->json(['msg'=>'参数错误']);
        }
        if ($request->hasFile('logo')) {
            $file=$request->file('logo');
            $filename = $file->getClientOriginalName();
            $extension = $file->getClientOriginalExtension();
            $file_name = md5(time() . $filename) . "." . $extension;
            \Storage::disk('oss')->put($file_name, file_get_contents($file->getRealPath()));
            if (\Storage::disk('oss')->exists($file_name)) {
                $request->logo = $file_name;//'https://static.cnczxy.com/'
            } else {
                $request->logo = $file_name;//file src
            }
        }
        $data=ProviderCategory::find($id);
        $data->desc=$request->desc;
        $data->logo=$request->logo;
        $data->save();
        return redirect("/provider/cateDesc")->with('msg', '修改成功');

    }

    /*
      * 服务商服务介绍 删除
      * */
    public function destroy($id)
    {
        $noDelete=ProviderCategory::where(['pid'=>$id])->get()->toArray();
        if(!empty($noDelete)){
            return response()->json(['msg'=>'该分类下有子分类，不能删除']);
        }else{
            ProviderCategory::where(['id'=>$id])->delete();
            return redirect("/provider/cateDesc")->with('msg', '删除成功');
        }

    }


    public function uploadImg(Request $request){
        if ($request->hasFile('w-form')) {
            $file=$request->file('w-form');
            $whiteList = array("png", "jpg", "bmp", "gif", "jpeg", "PNG", "JPG", "BMP", "GIF", "JPEG");
            $ext = $file->getClientOriginalExtension();
            if (!in_array($ext, $whiteList)) {
                return array('status' => false, 'msg' => '请上传正确图片格式', 'data' => null);
            }
            $filename = $file->getClientOriginalName();
            $extension = $file->getClientOriginalExtension();
            $file_name = md5(time() . $filename) . "." . $extension;
            \Storage::disk('oss')->put($file_name, file_get_contents($file->getRealPath()));
            if (\Storage::disk('oss')->exists($file_name)) {
                $url = 'https://static.cnczxy.com/'.$file_name;
            } else {
                $url = 'https://static.cnczxy.com/'.$file_name;//file src
            }
            $oUser = \Auth::user();
            $oObj  = new File();
            $oObj->school_id  = $oUser->school_id;
            $oObj->user_id    = $oUser->id;
            $oObj->name       = $file->getClientOriginalName();
            $oObj->type       = 3;// 1-视频 2-音频 3-图文 4-flash 5-office 6-其它
            $oObj->src        = $file_name ;
            $oObj->size       = $file->getClientSize();
            $oObj->url        = $url;
            $oObj->play_time  = 0;
            $oObj->from       = $request->input('from',9);
            $oObj->ext        = $file->getClientOriginalExtension();
//            $oObj->sign       = $this->getSign();
            $oObj->save();
            exit($url);
//            return response()->json(['status'=>true,'data'=>$url]);
        }

    }





}