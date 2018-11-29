<?php

namespace App\Http\Controllers\Administrator\Article;

use App\Http\Requests;
use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use App\Models\Teaching\Plan;
use App\Models\Teaching\Cell;
use App\Models\Teaching\Module;
use App\Models\Teaching\Node;
use App\Models\File;
use App\Models\User;
use App\Models\School;
use App\Models\Squad;
use App\Models\Notice;
use App\Models\Category;
use App\Models\Article;
use App\Models\Comment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;

class ArticleController extends Controller {
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    protected $_tuijianEn=['0'=>'不推荐','1'=>'推荐'];
    protected $_limit = 10;
    protected $_error = ['status'=>false,'msg'=>'error'];
    protected $_succ = ['status'=>true,'msg'=>'succ'];
    public function __construct()
    {
        $this->middleware('auth');
    }
    //显示有评论的文章
    public function comment(Request $request){
        //先获取评论的article_id
        $limit = $this->_limit;
        $title = trim($request->input('title'));

         $comment_ob =Comment::select(DB::raw('article_id,title'))
            ->where('delete_flag',1)->where('parent_id',0);
         if(!empty($title)) $comment_ob->where('title','like',"%$title%");

        $article_list =$comment_ob->groupBy('article_id')
            ->orderBy('cmt_id','DESC')
            ->paginate($limit);

        foreach($article_list as $tmp_list){
            $data = Article::select(DB::raw('`desc`,pubdate,comment_cnt'))
                ->where('id',$tmp_list->article_id)->where('delete_flag',1)
                ->get()->toArray();
            if(!empty($data)){
                $desc = $data[0]['desc'] ? $data[0]['desc'] : '该文章无简介';
                $pubdate = $data[0]['pubdate'] ? $data[0]['pubdate'] : '2018-01-01';
                $comment_cnt = $data[0]['comment_cnt'] ? $data[0]['comment_cnt'] : 0;
                $tmp_list->desc = $desc;
                $tmp_list->pubdate = date('Y-m-d H:i',strtotime($pubdate));
                $tmp_list->comment_cnt = $comment_cnt;
            }
        }
        return View('default.article.comment',compact('article_list'));
    }
    //根据 评论id删除评论
    public function commentDelete($id,Request $request){
        $id = intval(trim($id));
        if(empty($id)) return back()->withInput()->withErrors([ 'msg' => '参数错误']);/*response()->json($this->_error);*/
        $comment_ob = Comment::where('delete_flag',1)->find($id);
        if(empty($comment_ob)) return back()->withInput()->withErrors([ 'msg' => '评论不存在']);/*response()->json($this->_error);*/

        $comment_ob->delete_flag = 0;
        $comment_ob->save();
        //如果删除的是文章评论， 文章评论总数-1
        if($comment_ob->parent_id == 0){
            $article_ob = Article::where('delete_flag',1)->find($comment_ob->article_id);
            if(!empty($article_ob)){
                $article_ob->comment_cnt = $article_ob->comment_cnt-1;
                $article_ob->save();
            }
        }
        return back()->withInput()->withErrors([ 'msg' => '删除成功!']);/*response()->json($this->_succ);*/
    }
    //根据文章id获取文章下的所有的评论
    public function articleCommentList($aritcle_id,Request $request){

        $error  = ['status'=>false,'data'=>''];
        $succ = ['status'=>true,'data'=>''];
        $aritcle_id = trim(intval($aritcle_id));
        if(empty($aritcle_id)) return response()->json($error);

        $article_list = Comment::where('article_id',$aritcle_id)->where('delete_flag',1)
            ->get()->toArray();
        if(empty($article_list)) return response()->json($error);
//处理成前端需要的格式
        $tree = $this->getTree($article_list, 0);
        //自定义分页
        $page = intval(trim($request->input('page',1)));
        $limit = $this->_limit;
        $total = count($tree);
        $max_page = ceil($total/$limit);
        $page = max(1,$page);
        $page = min($page,$max_page);
        $tree = array_slice($tree,($page-1)*$limit,$limit);

        $succ['data'] = $tree;
        $succ['pager'] = ['page'=>$page,'pages'=>$max_page,'total'=>$total,'limit'=>$limit];
        return response()->json($succ);
    }
    //将数据转为树型状的数组
    protected function getTree($data, $pId)
    {
        $tree = [];
        foreach($data as $k => $v)
        {
            if($v['parent_id'] == $pId)
            {        //父亲找到儿子
                $v['parent_id'] = $this->getTree($data, $v['cmt_id']);
                $tree[] = $v;
                //unset($data[$k]);
            }
        }
        return $tree;
    }

    public function edit(Request $request){//资讯添加 和修改
        $id = trim($request->input('id'));
        $article_ob = new Article();
        $article_list_ob = $article_ob->where('delete_flag','1')->find($id);//无数据时返回null
        //图片显示。
        if(!empty($article_list_ob)&& $article_list_ob->litpic && \Storage::disk('oss')->exists($article_list_ob->litpic)) {
            $article_list_ob->img_url = \AliyunOSS::getUrl($article_list_ob->litpic, $expire = new \DateTime("+1 day"), $bucket = config('filesystems.disks.oss.bucket'));
        }
        //是POST方法，添加和修改
        if($request->method() == 'POST'){
            //文件上传。
            if ($request->hasFile('litpic')) {
                if ($request->file('litpic')->isValid()){
                    $file = $request->file('litpic');
                    $file_name = time().str_random(6).$file->getClientOriginalName();
                    \Storage::disk('oss')->put($file_name, file_get_contents($file->getRealPath()));
                    if(\Storage::disk('oss')->exists($file_name)) {
                        $filename = $file_name;
                    } else {
                        return back()->withInput()->withErrors(['msg' => '文件上传失败', ]);
                    }
                } else {
                    return back()->withInput()->withErrors(['msg' => '文件上传失败', ]);
                }
            }
            /*if ($request->hasFile('litpic')) {
                $image = $request->file("litpic");
                // 获取文件相关信息
                $originalName = $image->getClientOriginalName(); // 文件原名
                $type = $image->getClientMimeType();     // image/jpeg
                $ext = $image->getClientOriginalExtension();     // 扩展名
                $realPath = $image->getRealPath();   //临时文件的绝对路径
                // 上传文件
                $filename = date('Y-m-d-H-i-s') . '-' . uniqid() . '.' . $ext;
                // 使用我们新建的uploads本地存储空间（目录）
                //这里的uploads是配置文件的名称
                Storage::disk('uploads')->put($filename, file_get_contents($realPath));
                $image_url= env('APP_URL').'/uploads/'.$filename;
            }*/
            //上传文件的地址
            $litpic = !empty($filename) ? $filename : '';
            $data = $request->input();
            if($data['_token']) unset($data['_token']);
            if(isset($data['img_url'])) unset($data['img_url']);
            //登录用户id
            $user_id = \Auth::user()->id;
            if(empty($article_list_ob)){//新增
                if(isset($data['id'])) unset($data['id']);
                $data['litpic'] = $litpic;//封面图存放地址
                $data['created_by'] = $user_id;
                $data['pubdate'] = date('Y-m-d H:i:s');
                //文章标签,入标签库 @未做
                Article::create($data);
            }else{//修改
                foreach($data as $key=>$tmp){//图片新地址的修改
                    $article_list_ob->$key = $tmp;
                }
                if(!empty($litpic)) $article_list_ob->litpic=$litpic;
                //img_url在上面传过来
                if($article_list_ob->img_url) unset($article_list_ob->img_url);

                $article_list_ob->updated_by = $user_id;
                //文章标签，入新库 @未做
                $article_list_ob->save();
                //图片标题修改时 同步评论表和点赞表里的 文章标题 未做
            }
            return redirect('success')->with(['msg'=>'操作成功！', 'href'=>'/article/list']);
        }
        $article_tmp = [
            'type_id'=>'','title'=>'','litpic'=>'','keywords'=>'','desc'=>'','writer'=>'',
            'source'=>'','body'=>'','tuijian'=>'','domain'=>''
        ];
        $article_list = empty($article_list_ob)?$article_tmp:$article_list_ob->toArray();
        $title = $id ? '修改资讯': '添加资讯';
        $categorys = Category::orderBy('ord','DESC')->orderBy('id','ASC')->get()->toArray();//资讯分类

        return View('default.article.edit',compact('article_list','title','categorys'));
    }
    //文章标签入库，库中已存在
    protected function domain(){

    }
    //资讯列表
    public function list(Request $request)
    {
        //返回值为 obj
        $categorys = Category::orderBy('ord','DESC')->orderBy('id','DESC')
            ->get()->toArray();//分类
        $cotegory_list = [];
        foreach($categorys as $tmp){
            $cotegory_list[$tmp['id']] =$tmp['type_name'];
        }
        //分类处理。变成['id'=>'类名']
        $type_id = trim($request->input('type_id'));//分类id
        $title = trim($request->input('title'));//标题
//        $keywords = trim($request->input('keywords'));//关键词
        $name = trim($request->input('name'));//发布者
        $link = 15;
        $article_ob = new Article();
        $article_ob =$article_ob->leftJoin('users','articles.created_by','=','users.id')
            ->select('articles.*','users.name');
        if($type_id) $article_ob =$article_ob->where('articles.type_id',$type_id);
        if($title) $article_ob =$article_ob->where('articles.title','like',"%$title%");
//        if($keywords) $article_ob =$article_ob->where('articles.keywords','like',"%$keywords%");
        if($name) $article_ob =$article_ob->where('users.name','like',"%$name%");
        //处理是我的还是 回收站
        $act = trim($request->input('act'));
        if($act=='my'){
            $article_ob =$article_ob->where('articles.created_by',\Auth::user()->id);
        }elseif($act=='recycle'){
            $article_ob =$article_ob->where('articles.delete_flag',0);
        }else{
            $article_ob =$article_ob->where('articles.delete_flag',1);
        }
        $article_list =$article_ob->paginate($link);
        foreach($article_list as &$list){//处理结果集
            $list->tuijian = $this->_tuijianEn[$list->tuijian];
        }
        return View('default.article.list', compact('cotegory_list','article_list'));//返回对象
    }
    //删除资讯操作 ,或者恢复 或者彻底删除
    public function delete($id,Request $request){
        $id= trim($id);
        $act = trim($request->input('act'));
        if(empty($act)){//软删除。
            $article_ob = Article::where('delete_flag',1)->find($id);
            if(empty($article_ob)) return redirect('error')->with(['msg'=>'资讯不存在！', 'href'=>'/article/list']);
            $article_ob->delete_flag = 0;
            $article_ob->save();
            //删除资讯的时候，评论列表也要跟着删除。
/*            $comment_data = Comment::where('delete_flag',1)->where('parent_id',0)->where('article_id',$id)
                    ->get()->toArray();
            if(!empty($comment_data)){
                Comment::where('parent_id',0)->where('article_id',$id)->update(['delete_flag'=>0]);
            }*/
            return redirect("success")->with(['msg'=>'删除成功！','href'=>'/article/list']);
        }
        if($act == 'recover'){//已软删除的恢复操作
            $article_ob = Article::where('delete_flag',0)->find($id);
            if(empty($article_ob)) return redirect('error')->with(['msg'=>'不存在删除的资讯！', 'href'=>'/article/list?act=recycle']);
            $article_ob->delete_flag = 1;
            $article_ob->save();
            return redirect("success")->with(['msg'=>'恢复成功！','href'=>'/article/list?act=recycle']);
        }
        if($act == 'realdel'){//清除记录
            $article_ob = Article::where('delete_flag',0)->find($id);
            if(empty($article_ob)) return redirect('error')->with(['msg'=>'不存在删除的资讯！', 'href'=>'/article/list?act=recycle']);
            $article_ob->destroy($id);
            return redirect("success")->with(['msg'=>'清除记录成功！','href'=>'/article/list?act=recycle']);
        }
        if($act == 'tuijian'){//推荐改为不推荐，不推荐改为推荐
            $article_ob = Article::where('delete_flag',1)->find($id);
            if(empty($article_ob)) return redirect('error')->with(['msg'=>'资讯不存在！', 'href'=>'/article/list']);
            $article_ob->tuijian = $article_ob->tuijian ? 0 : 1;
            $article_ob->save();
            return redirect("success")->with(['msg'=>'清除记录成功！','href'=>'/article/list']);
        }

    }

    // 资讯分类
    public function category(Request $request)
    {
        $method = $request->method();
        $id = $request->input('id');
        $info = Category::find($id);
        $info_tmp = ['type_name'=>'','ord'=>'','type'=>'','is_recommend'=>''];

        if($method == 'GET'){//分类列表
            $categorys = Category::orderBy('ord','DESC')->orderBy('id','ASC')
                ->get()->toArray();//分类
            //分类信息
            $info = $info ? $info->toArray() : $info_tmp;
            return View('default.article.category', compact('categorys','info'));
        }
        if($method == 'POST'){
            $method = $request->input('method');
            if($method=='del'){
                if(!empty($info)){
                    Category::destroy($id);
                }
                $data = ['status'=>'succ','msg'=>'成功'];
                $json = json_encode($data);
                echo $json;exit();
            }
            if($method!='del'){
                $data = $request->input();
                unset($data['_token']);
                //获取修改者的id
                $data['created_by'] = \Auth::user()->id;

                if(empty($data['id']) && !empty($data['type_name'])){//添加时分类名不能重复
                    $tmp = Category::where('type_name',$data['type_name'])->get()->toArray();
                    if(!empty($tmp)) return redirect('error')->with(['msg'=>'分类名不能重复！', 'href'=>'/article/category']);
                    Category::create($data);
                }
                if(!empty($data['id'])){
                    $category = new Category();
                    $tmp = $category->find($data['id']);
                    if(empty($tmp)){//新增分类
                        Category::create($data);//返回对象模型
                    }else{//修改分类
                        Category::where('id',$data['id'])->update($data);//返回受影响的条数
                    }
                }
                return redirect('success')->with(['msg'=>'操作成功！', 'href'=>'/article/category']);
            }
        }
    }
}