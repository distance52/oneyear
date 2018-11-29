<?php

namespace App\Http\Controllers\Administrator\Other;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use app\common\helpers\Url;
use App\Models\Taggable;
use Illuminate\Http\Request;
use App\Models\Feedback;
use App\Models\File;
use phpDocumentor\Reflection\DocBlock\Tag;
use Illuminate\Support\Facades\Redis;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Helpers;
use App\common\services\Oss;
use Illuminate\Support\Facades\Session;


class FeedbackController extends Controller
{
//    public function __construct()
//    {
//        $this->middleware('auth');
//    }
    /*
     * 意见反馈
     * $keyword 关键字搜索 模糊查询
     * */
    public function index()
    {

        $keyword= isset($_GET['keyword'])?$_GET['keyword']:"";
//        $user_id = \Auth::user()->id;
        $type=isset($_GET['type'])?$_GET['type']:"";
        $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
        $pageSize = 20;
        $start = ($page - 1 ) * $pageSize;
        $data = new Feedback;
        $count=new Feedback;
        if (!empty($keyword)){
            $data = $data->where(function($query) use($keyword) {
                $query->where('name', 'like', '%' . $keyword . '%');
            });
            $count = $count->where(function($query) use($keyword) {
                $query->where('name', 'like', '%' . $keyword . '%');
            });
        }
        if (isset($_GET['type'])){
            $status= $_GET['type'];
            if($status!=0){
                $data = $data->where(function($query) use($status) {
                    $query->where('type', $status);
                });
                $count = $count->where(function($query) use($status) {
                    $query->where('type',$status);
                });
            }

        }

        $count=$count->pluck('id')->count();
        $totalPage = ceil($count / $pageSize);
// ->where(['deleted_at'=>null])       $data = $data->with('category')->orderBy("id","desc")->skip($start)->take($pageSize)->get();
        $data = $data->orderBy("time","desc")->skip($start)->take($pageSize)->get();

        foreach($data as $k=>&$v){
            if(!empty($v->file_id)){
                $file_id=json_decode($v->file_id,true);
                $v->file_id=File::where('type',1)->whereIn("id",$file_id)->pluck('url');
            }
        }
        return view('default.feedback.index',compact('data','keyword','type','totalPage','page'))->render();
    }
    //回收站
    public function feedbackTrash(Request $request){
        $type= isset($_GET['type'])?$_GET['type']:"";
        $trash=new Feedback;
        if(!empty($type)){
            $trash=$trash->where("type",$type);
        }
        $trash=$trash->onlyTrashed()->orderBy("id","desc")->paginate(20);
        foreach($trash as $k=>&$v){
            if(!empty($v->file_id)){
                $file_id=json_decode($v->file_id,true);
                $v->file_id=File::where('type',1)->whereIn("id",$file_id)->pluck('url');
            }
        }

        return view('default.feedback.feedbacktrash',compact('trash','type'))->render();
    }
    //恢复数据||清空数据
    public function doFeedbackTrash(Request $request,$id){
        if(!$id){
            return response()->json(array('state'=>false , 'msg' => '缺少参数' , 'data'=>null));
        }
        if($request->type==1){
            Feedback::where('id',$id)->restore();
        }
        if($request->type==0){
            Feedback::where('id',$id)->forceDelete();
            $file_id=Feedback::where('id',$id)->pluck("file_id");

            file::where('type',1)->whereIn("id",json_decode($file_id,true))->forceDelete();

        }
        return redirect()->back();
    }

    /*
     * 删除
     * $id 传送的主键
     * */
    public function delete(Request $request){
        if(is_array($request->id)){
            foreach($request->id as $k=>$v) {
                $user = Feedback::find($v);
//                $user->deleted_at=Date("Y-m-d H:i:s",time());
                $date=$user->delete();
            }
            if($date){
                echo 1;
            }else{
                echo 0;
            }
        }
        else{
            Feedback::where('id',$request->id)->delete();
            return redirect("/other/feedback");
        }
    }



}
