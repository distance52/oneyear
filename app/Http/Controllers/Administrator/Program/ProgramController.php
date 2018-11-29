<?php

namespace App\Http\Controllers\Administrator\Program;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use App\Models\Taggable;
use Illuminate\Http\Request;
use App\Models\Program;
use App\Models\Region;
use App\Models\Role;
use App\Models\Tagxx;
use App\Models\ProgramCategory;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Session;
use XS;
use XSDocument;
use XSIndex;

class ProgramController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }
    /*
     * 案例展示
     * $keyword 关键字搜索 模糊查询
     * */
    public function index()
    {

        $keyword= isset($_GET['keyword'])?$_GET['keyword']:"";
        $user_id = \Auth::user()->id;
        $status='';
        $type=isset($_GET['type'])?$_GET['type']:"";
        //分页改为从缓存读取总数
        $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
        $pageSize = 20;
        $start = ($page - 1 ) * $pageSize;

        $data = Program::where([]);
        if($type==3){
            $data = $data->where(function($query) use($user_id) {
                $query->where('user_id',$user_id);
            });
        }
        if (!empty($keyword)){
            $data = $data->where(function($query) use($keyword) {
                $query->where('name', 'like', '%' . $keyword . '%');
            });
        }
        if (isset($_GET['status'])){
            $status= $_GET['status'];
            if($status==3){
            }else{
                $data = $data->where(function($query) use($status) {
                    $query->where('status', $status);
                });
            }
        }
        $data = $data->with('category')->orderBy("id","desc");
        if(!$keyword||!$status||$status==3||!$type){
            if(empty( Redis::exists('xProgram:count') )){
                $count = Program::pluck('id')->count();
                Redis::set('xProgram:count' , $count);

            }else $count = Redis::get('xProgram:count');

        }else  $count=$data->pluck('id')->count();

        $data=  $data ->skip($start)->take($pageSize)->get();

        $totalPage = ceil($count / $pageSize);
        return view('default.program.index',compact('data','keyword','status','totalPage','page','type'))->render();
    }

    //回收站
    public function programTrash(Request $request){
        $keyword= isset($_GET['keyword'])?$_GET['keyword']:"";
        $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
        $pageSize = 20;
        $start = ($page - 1 ) * $pageSize;
        $where=" ";
//        $trash=new Program;
        if($keyword){
//            $trash=$trash->where("name",'like','%'.$keyword.'%');
            $where=" and name like ".$keyword." ";
        }

//        select * from  ( select * from `programs`  order by `id` desc limit 20 offset 0 ) as pro   where  `deleted_at` is not null limit 5 offset 0 ;
//        $trash=$trash->select("\DB::table('programs as pro')->orderBy('id','desc')->skip(".$start.")->take(".$pageSize.")")->skip($start)->take($pageSize)->onlyTrashed();
        $sql="select * from  ( select * from `programs`  order by `id` desc limit ".$pageSize." offset ".$start." ) as pro   where  `deleted_at` is not null ".$where." limit ".$pageSize." offset ".$start;
//        $trash=$trash->select("\DB::table('programs as pro')->orderBy('id','desc')->skip(".$start.")->take(".$pageSize.")")->skip($start)->take($pageSize)->onlyTrashed();
//        $sql=\DB::table('programs')->orderBy('id','desc')->skip($start)->take($pageSize)->get();
//        $trash=$trash->select(\DB::raw("({$sql->tosql()}) as pro"))->skip($start)->take($pageSize)->onlyTrashed();
        $trash= \DB::select($sql);

//        $id=  $trash->skip($start)->take($pageSize)->withTrashed()->orderBy("id","desc")->pluck("id");
//        $trash =$trash->whereIn("id",$id)->skip($start)->take($pageSize)->onlyTrashed();
//        $count=$trash->count();
        $count=count($trash);
//        $trash =$trash->get();
        $totalPage = ceil($count / $pageSize);
        return view('default.program.programtrash',compact('trash','totalPage','page','keyword'))->render();


//        $totalPage = ceil($count / $pageSize);

//        $trash=  $trash->onlyTrashed()->orderBy('id','desc')->skip($start)->take($pageSize)->get();


    }

    //恢复数据||清空数据
    public function doProgramTrash(Request $request,$id){
        if(!$id){
            return response()->json(array('state'=>false , 'msg' => '缺少参数' , 'data'=>null));
        }
        if($request->type==1){
            $obj = Program::whereId($id);
            $obj->restore();
            $oProgram = $obj->first();
            $this->updateIndex('add', $oProgram);
        }
        if($request->type==0){
            Program::where('id',$id)->forceDelete();
        }
        return redirect('program/index');
    }

    /*
     * 添加
     * $request 传送的数据
     * */
    public function create(Request $request){
        if ($_POST) {
            $user_id = \Auth::user()->id;
            $program = new Program;
            if ($request->hasFile('logo')) {
                if ($request->file('logo')->isValid()){
                    $file = $request->file('logo');
                    $filename=$file->getClientOriginalName();
                    $extension=$file->getClientOriginalExtension();
                    $file_name = md5(time().$filename).".".$extension;

                    \Storage::disk('oss')->put($file_name, file_get_contents($file->getRealPath()));
                    if(\Storage::disk('oss')->exists($file_name)) {
                        $program->logo = $file_name;
                    } else {
                        return back()->withInput()->withErrors(['msg' => '上传失败',]);
                    }
                } else {
                    return back()->withInput()->withErrors(['msg' => '上传失败',]);
                }

            }

            $program->user_id = $user_id;
            $program->category_id = $request->category_id;
            $program->uuid = substr(md5(rand(1,9999).time()),1,6);
            $program->name = $request->name;
            $program->intro = $request->intro;
            $program->file = $request->file;
            $program->address = $request->address;
            $program->city = $request->city;
            $program->field = $request->field;
            $program->platform = $request->platform;
            $program->company = $request->company;
            $program->financing_stage = $request->financing_stage;
            $program->description = $request->description;
            $program->status = 1;

            $program->save();
            $tag = $request->tag;
            if($tag){
                foreach ($tag as $k=>$v){
                    Taggable::where("taggable_id",$program->id)->insert(['taggable_type'=>'App\Models\Program','tag_id'=>$v]);
                }
            }
            if($program->status){
                $this->updateIndex('add', $program);
            }
            $key='xProgram:count';
            Redis::incrby($key,1);
            return redirect( '/program/index');

        }else{
            $city=Region::where("pid",0)->get();
            $ProgramCategory=ProgramCategory::select("id","name")->get();
            $tag=Tagxx::where('status',1)->select("id","name")->get();
            $role = Role::get();
            return view('default.program.create',compact('role','city','ProgramCategory','tag'))->render();
        }
    }
    /*
     * 删除
     * $id 传送的主键
     * */
    public function delete(Request $request){
        if(is_array($request->id)){
            foreach($request->id as $k=>$v) {
                $obj = Program::find($v);
                $this->delIndex($obj->id);
                $date=$obj->delete();
                $key='xProgram:count';
                Redis::decrby($key,1);
            }
            if($date){
                echo 1;
            }else{
                echo 0;
            }
        }
        else{
            $obj = Program::find($request->id);
            $this->delIndex($request->id);
            $obj->delete();
            $key='xProgram:count';
            Redis::decrby($key,1);
            return redirect("/program/index");
        }
    }
    /*
     * 修改
     * $request 传送的数据
     * */
    public function edit(Request $request,$id){
//        $id= $_GET['id'];
        if ($_POST){
//            $user=array();
            $program = Program::find($id);
            if ($request->hasFile('logo')!="") {
                if ($request->file('logo')->isValid()){
                    $file = $request->file('logo');

                    $filename=$file->getClientOriginalName();
                    $extension=$file->getClientOriginalExtension();
                    $file_name = md5(time().$filename).".".$extension;

                    \Storage::disk('oss')->put($file_name, file_get_contents($file->getRealPath()));
                    if(\Storage::disk('oss')->exists($file_name)) {
                        $res = $file_name;
                    } else {
                        return back()->withInput()->withErrors(['msg' => '上传失败',]);
                    }
                } else {
                    return back()->withInput()->withErrors(['msg' => '上传失败',]);
                }
                $program->logo = $res;
            }

            $program->name = $request->name;
            $program->intro = $request->intro;
            $program->category_id = $request->category_id;
            $program->file = $request->file;
            $program->address = $request->address;
            $program->city = $request->city;
            $program->field = $request->field;
            $program->platform = $request->platform;
            $program->company = $request->company;
            $program->financing_stage = $request->financing_stage;
            $program->description = $request->description;
            $program->save();

            $tag = $request->tag;
            if($tag){
                \DB::table("taggables")->where("taggable_id",$request->id)->delete();
                foreach ($tag as $k=>$v){
                    \DB::table("taggables")->insert(["taggable_id"=>$request->id,"tag_id"=>$v,'taggable_type'=>'App\Models\Program']);
                }
            }
            if($program->status){
                $this->updateIndex('update', $program);
            }
            return redirect( '/program/index');
        }else{

            $city=Region::get();
            $user = Program::find($id);
            $user->description=strip_tags($user->description);
            $user->description=str_replace("&nbsp;","",$user->description);

            if($user->id>302078){
//                if ($user->logo && \Storage::disk('oss')->exists($user->logo)) {
//                    $user->logo = \AliyunOSS::getUrl($user->logo, $expire = new \DateTime("+1 day"), $bucket = config('filesystems.disks.oss.bucket'));
//                } else {
//                    $user->logo = '';
//                }
                $user->logo='https://ledao.oss-cn-hangzhou.aliyuncs.com/'.$user->logo;
            }else if($user->id<=41895){
                $user->logo = "";
            }else{
                $user->logo='http://static.cnczxy.com/'.$user->logo;
            }
            $ProgramCategory=ProgramCategory::select("id","name")->get();
            $tag=Tagxx::where('status',1)->select("id",'name')->get();
            $taggable=Tagxx::where(["taggable_id"=>$id,"status"=>1])->leftJoin("taggables","tagxxs.id","=","taggables.tag_id")->pluck("taggables.tag_id")->toArray();
            return view('default.program.edit',compact('user','city','ProgramCategory','tag','taggable'))->render();
        }
    }
    /*
     * auditing 审核信息
     *
     * */
    public function auditing(Request $request){
        $data= '';
        if(is_array($request->id)){
            foreach($request->id as $k=>$v){
                $user = Program::find($v);
                $user->status=$request->status[$k];
                $data=$user->save();
                if($request->status){
                    $this->updateIndex('add' , $user);
                }else{
                    $this->delIndex($user->id);
                }
            }
        }else{
            $user = Program::find($request->id);
            $user->status=$request->status;
            $data=$user->save();
            if($request->status){
                $this->updateIndex('add' , $user);
            }else{
                $this->delIndex($user->id);
            }
        }
        if($data){
            echo 1;
        }else{
            echo 0;
        }
    }
    /*
     * 推荐 recommend
     * */
    public function recommend(Request $request){
        if(is_array($request->id)){
            foreach($request->id as $k=>$v){
                $user = Program::find($v);
                $user->recommend=$request->status;
                $data=$user->save();
            }
        }else{
            $user = Program::find($request->id);
            $user->recommend=$request->status;
            $data=$user->save();
        }
        if($data){
            echo 1;
        }else{
            echo 0;
        }
    }
    /*
     * ajax 内容管理
     * */
    public function obtainContent(Request $request){
        $user =\DB::table("programs")->where("id",$request->id)->get();
        $array=array();
        if($user){
            $array['code']=1;
            $array['msg']=$user[0];
        }else{
            $array['code']=0;
            $array['msg']="未找到";
        }
        return json_encode($array);
    }
    /*
     * program标签
     * */

    public function tag(Request $request,$id=""){
        if($_POST){
            if(isset($request->id)&&$id==""){
                $id = $request->id;
                $oTag = Tagxx::find($id);
                isset($request->name) ? $oTag->name = $request->name : '';
                isset($request->status) ? $oTag->status = $request->status : 1;
                return redirect('program/tag');
            }else{
                $tag=\DB::table("tagxxs");
                $user['status'] = $request->status;
                $user['name'] = $request->name;
                if($id){
                    $tag->where("id",$id)->update($user);
                }
                else $tag->insert($user);
                return redirect('program/tag');
            }
        }
//        $id = isset($_GET['id']) ? $_GET['id'] :'';
        if($id){
            $oTag = Tagxx::find($id);
        }
        $data = Tagxx::paginate(10);
        $page = isset($_GET['page']) ? $_GET['page'] :'1';
        $totalPage =ceil(Tagxx::count() /10);
        return view('default.program.tag',compact('data','oTag','page','totalPage'))->render();
    }

    /*
     * program分类
     * */
    public function category(Request $request,$id=""){
        if($_POST){
            if(isset($request->id)&&$id==""){
                $id = $request->id;
                $oCate = ProgramCategory::find($id);
                isset($request->name) ? $oCate->name = $request->name : '';
                // isset($request->status) ? $oCate->status = $request->status : 1;
                return redirect('program/category');
            }else{
                $user['name'] = $request->name;
//                $category=\DB::table("program_categories");
                $category=new ProgramCategory;
                if($id){
                    $category=$category->where('id',$id);
                    $category->update($user);
                }
                else $category->insert($user);
                return redirect('program/category');
            }
        }
        $page = isset($_GET['page']) ? $_GET['page'] :'1';
        $totalPage =ceil(ProgramCategory::count() /10);
        if($id){
            $oCate = ProgramCategory::find($id);
        }
        $data = ProgramCategory::paginate(10);
        return view('default.program.category',compact('data','oCate','page','totalPage'))->render();
    }

    /*
     * ajax获取二级城市
     * */
    public function ajaxCity(Request $request){
        $pid=$request->pid;
        if(empty($pid)){
            $array['code']=-1;
            $array['msg']="参数有误";
            return json_encode($array);
        }
        $data=Region::where("pid",$pid)->get()->toArray();

        if($data){
            $array['code']=1;
            $array['msg']=$data;
        }else{
            $array['code']=0;
            $array['msg']="没有数据";
        }
        return json_encode($array);
    }

    private function updateIndex($type = 'add' , $obj){
        $xs = new XS(config_path('cases.ini'));
        $data = array(
            'id' => $obj->id, // 此字段为主键，必须指定
            'uuid' => $obj->uuid,
            'category_id' => $obj->category_id,
            'name' => $obj->name,
            'display_name' => $obj->display_name,
            'intro' => $obj->intro,
            'description' => $obj->description,
            'created_at' => $obj->created_at,
            'open_state' => $obj->open_state,
            'started_at' => $obj->started_at
        );

        $doc = new XSDocument;
        $doc->setFields($data);

        if($type == 'add'){
            $xs->index->add($doc);
        }elseif ($type == 'update') {
            $xs->index->update($doc);
        }
    }

    private function delIndex($id){
        $xs = new XS(config_path('cases.ini'));
        $xs->index->del($id);
    }

}