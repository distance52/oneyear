<?php

namespace App\Http\Controllers\Administrator\Management;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use App\Models\Labora;
use App\Models\Apply;
use App\Models\Visitor;
use App\Models\User;
use App\Models\AreaStation;
use App\Models\AreaStationApply;

class ManagementController extends Controller{
    protected $_limit = 10;
    protected $_school_id = '';
    protected  $_labor_type_cn = ['0'=>'公共房间','1'=>'入项房间'];
    protected  $_station_type_cn = ['0'=>'公共','1'=>'入项'];
    public $confirm_cn = ['0'=>'未到前台确认','1'=>'已到前台确认'];
    public $status_cn = ['1'=>'待审核','2'=>'审核通过','3'=>'未通过'];
    public $doc_type_cn = ['1'=>'身份证','2'=>'学生证','3'=>'教师证'];
    protected $_labor_ob;
    protected $_apply_ob;
    protected $_visitor_ob;
    public function __construct(Request $request)
    {
        if(empty(\Auth::user())) return redirect('error')->with(['msg'=>'请先登录！', 'href'=>'/login']);
        $this->middleware('auth');
        //获取学校id
        $this->_school_id = intval(trim(\Auth::user()->school_id));
        //根据学校id 初始化查询对象
        $this->_labor_ob = Labora::where('delete_flag',1)->where('school_id',$this->_school_id);
        //初始化申请列表对象
        $this->_apply_ob = Apply::where('delete_flag',1)->where('school_id',$this->_school_id);
        //初始化访客登记对象
//        $visitor_ob = new Visitor();
//        $this->_visitor_ob = $visitor_ob->where('delete_flag',1)->where('school_id',$this->_school_id);
    }

    public function test1(){

        $test1 = $this->_apply_ob->where('start_date','>','2018')->get()->toArray();
        $test2=  $this->_apply_ob->where('start_dat','>','2019')->get()->toArray();
        dd($test1);
    }
    public function test2(){
        $apply_ob = Apply::where('delete_flag',1)->where('school_id',$this->_school_id);
        $test2=  $apply_ob->where('start_date','>','2019')->get()->toArray();
        $test1 = $apply_ob->where('start_date','>','2018')->get()->toArray();
        dd($test1);
        /**
         *若果是 new apply(); 就不会有这样的问题。
         */
    }
    /**
     * 房间显示列表.
     */
    public function index(Request $request)
    {
        $lab_name = trim($request->input('lab_name',''));
        $labora_ob = Labora::where('delete_flag',1);
        //所属学校
        $labora_ob = $labora_ob->where('school_id',$this->_school_id);
        if(!empty($lab_name)) $labora_ob = $labora_ob->where('lab_name','like',"%$lab_name%");
        $labora = $labora_ob->orderBy('id','DESC')->paginate($this->_limit);
        foreach($labora as $tmp){
            $tmp->labor_type_cn = $this->_labor_type_cn[$tmp->labor_type];
            $tmp->created_by_cn = '';
            //创建人姓名
            if($tmp->created_by){
                $data = User::find($tmp->created_by);
                if($data)  $tmp->created_by_cn = $data->name;
            }
        }
        return View('default.management.index',compact('labora'));
    }
    //房间添加
    public function add(Request $request)
    {
        $id = intval(trim($request->input('id','')));
        $title = $id ? '编辑房间' : '添加房间';
        $labora = '';
        if(!empty($id)) $labora = $this->_labor_ob ->find($id);
        if($request->method()=='GET') return View('default.management.add',compact('labora','title'));
        if($request->method()=='POST'){
            $data = $request->input();
            if($data['_token']) unset($data['_token']);
            if(empty($labora)){//添加
                if(isset($data['id']) && !empty($data['id'])) unset($data['id']);
                $data['created_by'] = \Auth::user()->id;
                $data['school_id'] = $this->_school_id;
                Labora::create($data);
            }else{//编辑
                $data['updated_by'] = \Auth::user()->id;
                Labora::where('id',$labora->id)->update($data);
            }
            return redirect('success')->with(['msg'=>'操作成功！', 'href'=>'/management/index']);
        }
    }
    //房间删除
    public function delete(Request $request)
    {
        $id = intval(trim($request->input('id','')));
        $labora = '';
        if(!empty($id)) $labora = $this->_labor_ob ->find($id);
        if(empty($labora)) return redirect('error')->with(['msg'=>'Not Found！', 'href'=>'/management/index']);
        $labora->delete_flag = 0;
        $labora->save();
        return redirect('success')->with(['msg'=>'操作成功！', 'href'=>'/management/index']);
    }
    //房间入驻审核列表
    public function list(Request $request)
    {
        $user_name = trim($request->input('user_name',''));//申请人
        $lab_name = trim($request->input('lab_name',''));//房间名称
        //根据学校id 所列条件查出列表
        $apply_ob = new Apply();
        $apply_ob =$apply_ob->leftJoin('laboras','applys.labora_id','=','laboras.id')
            ->select('applys.*','laboras.lab_name');
        if($user_name) $apply_ob =$apply_ob->where('applys.user_name','like',"%$user_name%");
        if($lab_name) $apply_ob =$apply_ob->where('laboras.lab_name','like',"%$lab_name%");
        $apply = $apply_ob->where('applys.delete_flag','1')->where('applys.school_id',$this->_school_id)
            ->orderBy("applys.id",'DESC')
            ->paginate($this->_limit);
        foreach($apply as $tmp_list){
            $tmp_list->confirm_cn = $this->confirm_cn[$tmp_list->confirm];
            $tmp_list->status_cn = $this->status_cn[$tmp_list->status];
        }
        return View('default.management.list',compact('apply'));
    }
    //房间审核详情页面
    public function  detail(Request $request){
        $id = intval(trim($request->input('id')));//审核记录id
        if(empty($id)) return response()->json(['status'=>false,'msg'=>'参数错误']);
        $apply = $this->_apply_ob->find($id);
        if(empty($apply)) return response()->json(['status'=>false,'msg'=>'Not Found Apply!']);
        $apply_ob = new Apply();
        $data = $apply_ob->check_apply($id,$this->_school_id);
        $apply->conflict = 'no';
        $apply->conflict_cn = '';
        $apply->lab_name = Labora::find($apply->labora_id)->lab_name;
        //公共房间名称
        if($data['status'] == false){//有冲突
            $apply->conflict = 'yes';
            $apply->conflict_cn = $data['msg'];
        }
        return response()->json(['status'=>true,'msg'=>'succ','data'=>$apply->toArray()]);
    }
    //房间入驻审核
    public function check(Request $request){
        $id = intval(trim($request->input('id')));//审核记录id
        $status = intval(trim($request->input('status')));//审核状态 2通过，3不通过
        $comment = trim($request->input('comment',''));
        //审核人id
        $ap_id = \Auth::user()->id;
        if(empty($id)) return response()->json(['status'=>false,'msg'=>'参数错误']);
        if(empty($status)) return response()->json(['status'=>false,'msg'=>'参数错误']);
        if(!in_array($status,['2','3'])) return response()->json(['status'=>false,'msg'=>'参数错误']);
        if(empty($ap_id)) return response()->json(['status'=>false,'msg'=>'用户未登录']);
        if($status == 2){
            $apply_ob = new Apply();
            $data = $apply_ob->check_apply($id,$this->_school_id);
            if($data['status'] == false) return response()->json($data);
        }
        $up_data = ['ap_id'=>$ap_id,'ap_name'=>\Auth::user()->name,
            'status'=>$status ,'comment'=>$comment];
        Apply::where('id',$id)->update($up_data);
        //@发送邮件通知审核结果未做
        return response()->json(['status'=>true,'msg'=>'succ']);
    }
    //删除申请记录。返回时带上参数未做
    public function deleteApply(Request $request){
        $id = intval(trim($request->input('id')));
        $act = trim($request->input('act'));//del con
        $apply = '';
        if(!empty($id)) $apply = $this->_apply_ob ->find($id);
        if(empty($apply)) return redirect('error')->with(['msg'=>'Not Found！', 'href'=>'/management/list']);
        if($act == 'del'){
            $apply->delete_flag = 0;
            $apply->save();
        }
        if($act == 'con'){
            $apply->confirm = 1;
            $apply->save();
        }
        return redirect('success')->with(['msg'=>'操作成功！', 'href'=>'/management/list']);
    }
    //工位管理
    public function station(Request $request)
    {
        $area_name = trim($request->input('area_name',''));
        $station_ob = new AreaStation();
        //先查询 父级id是0的
        $station_ob = $station_ob->where('delete_flag',1)->where('school_id',$this->_school_id)->where('upid',0);
        if(!empty($area_name)) $station_ob = $station_ob->where('area_name','like',"%$area_name%");
        $area_station = $station_ob->orderBy('id','DESC')->paginate($this->_limit);
        foreach($area_station as $station){
            //获取这个 区域房间中  的工位。
            $tmp = AreaStation::getStationsByupid($station->id);
            $station->station = $tmp;
            $station->station_type_cn = $this->_station_type_cn[$station->type];
        }
        return View('default.management.station',compact('area_station'));
    }
    //工位管理--添加工位
    public function addStation(Request $request){
        $id =intval(trim($request->input('id','')));
        $num = intval(trim($request->input('num','')));
        if($num>100) return response()->json(['status'=>false,'msg'=>'一次添加工位不能超过100个']);
        if($num<1) return response()->json(['status'=>false,'msg'=>'添加工位数不能少于1个']);
        $ob = new AreaStation();
        $area_ob = $ob->where('delete_flag',1)->where('school_id',$this->_school_id)->where('upid',0)->find($id);
        if(empty($area_ob)) return response()->json(['status'=>false,'msg'=>'区域房间不存在！']);
        $tmp_ob = $ob->where('upid',$area_ob->id)->selectRaw("max(station_name) as max_num")->first();
        $max_num = intval($tmp_ob->max_num);
        //添加工位，从$max_num +$num 开始，防止station_name名字重复
        $start = $max_num+1;
        $end = $max_num+$num;
        for($station_name =$start;$station_name<=$end;$station_name++){
            $tmp_data = ['upid'=>$area_ob->id,'station_name'=>$station_name,'school_id'=>$this->_school_id];
            AreaStation::create($tmp_data);
        }
        return response()->json(['status'=>true,'msg'=>'succ']);
    }
    //工位管理--添加/编辑
    public function stationEdit(Request $request){
        return response()->json(['status'=>true,'msg'=>'qwe']);

        $id =intval(trim($request->input('id','')));
        $num = intval(trim($request->input('num','')));
        $data = $request->input();
        if($num>100) return response()->json(['status'=>false,'msg'=>'一次添加工位不能超过100个']);
        if($num<1) return response()->json(['status'=>false,'msg'=>'添加工位数不能少于1个']);
        $ob = new AreaStation();
        $area_ob = $ob->where('delete_flag',1)->where('school_id',$this->_school_id)->where('upid',0)->find($id);
        if(empty($area_ob)){
            //添加区域
            $area_data = [];
            $area_data['area_name'] = $data['area_name'];
            $area_data['area_des'] = $data['area_des'];
            $area_data['upid'] = 0;
            $area_data['type'] = $data['type'];
            $area_data['created_by'] = \Auth::user()->id;
            $area_data['school_id'] = $this->_school_id;
            $tmp_ob = AreaStation::create($area_data);
            //添加工位
            for($station_name =1;$station_name<=$num;$station_name++){
                $tmp_data = ['upid'=>$tmp_ob->id,'station_name'=>$station_name,'school_id'=>$this->_school_id];
                AreaStation::create($tmp_data);
            }
        }else{
            //编辑区域
            $edit_data = [];
            $edit_data['area_name'] = $data['area_name'];
            $edit_data['area_des'] = $data['area_des'];
            $edit_data['type'] = $data['type'];
            AreaStation::where('id',$id)->update($edit_data);
            //删除原来的工位  再添加现有的工位数
            //这里逻辑有问题
/*            AreaStation::where('upid',$area_ob->id)->delete();
            for($station_name =1;$station_name<=$num;$station_name++){
                $tmp_data = ['upid'=>$area_ob->id,'station_name'=>$station_name,'school_id'=>$this->_school_id];
                AreaStation::create($tmp_data);
            }*/
        }
        return response()->json(['status'=>true,'msg'=>'succ']);
    }
    //工位管理的->删除
    public function stationDelete(Request $request){
        $id = intval(trim($request->input('id','')));
        //如果删除的是   工位：直接删除
        //如果删除的是   区域： delete=0；
        $ob = new AreaStation();
        $area_ob = $ob->where('delete_flag',1)->where('school_id',$this->_school_id)->where('upid',0)->find($id);
        if(empty($area_ob)){//如果  是空的，可能是工位删除.
            $station_ob = $ob->find($id);
            if(empty($station_ob)) return response()->json(['status'=>false,'msg'=>'id不存在']);
            $re =$station_ob->delete();
        }else{//不是空 删除区域， delete_flag=0
            $area_ob->delete_flag = 0;
            $re = $area_ob->save();
        }
        if($re){
            return response()->json(['status'=>true,'msg'=>'删除成功']);
        }else{
            return response()->json(['status'=>false,'msg'=>'删除失败!']);
        }
    }
    //工位预约审核
    public function stationEnroll(Request $request)
    {
        $user_name = trim($request->input('user_name',''));
        $apply_ob = new AreaStationApply();
        $apply_ob = $apply_ob->where('delete_flag',1)->where('school_id',$this->_school_id);
        if(!empty($user_name)) $apply_ob->where('user_name','like',"%$user_name%");
        //获取申请列表
        $apply_list = $apply_ob->orderBy('id','DESC')->paginate($this->_limit);
        //获取申请列表中的 工位名称
        $area_ob = new AreaStation();
        foreach($apply_list as $tmp){
            $tmp->statuc_cn = $this->status_cn[$tmp->status];
            $area_list = $area_ob->where('school_id',$this->_school_id)->find($tmp->station_id);
            if(empty($area_list)){//申请中的 工位id不存在
                $tmp->station_name_cn = '';
                continue;
            }
            if($area_list->upid ==0){//申请 的是 一个区域，不是工位
                $tmp->station_name_cn = $area_list->area_name;
                continue;
            }
            if($area_list->upid !=0){//申请 的是 一个工位
                $tmp_station_name = $area_list->station_name .'号工位';
                $tmp_area_list = $area_ob->find($area_list->upid);
                $tmp->station_name_cn = $tmp_area_list->area_name .'-'.$tmp_station_name;
            }
        }
        return View('default.management.stationEnroll',compact('apply_list'));
    }
    //工位审核
    public function stationCheck(Request $request){

        $id = intval(trim($request->input('id')));//审核记录id
        $status = intval(trim($request->input('status')));//审核状态 2通过，3不通过
        $comment = trim($request->input('comment',''));
        //审核人id
        $ap_id = \Auth::user()->id;
        if(empty($id)) return response()->json(['status'=>false,'msg'=>'参数错误']);
        if(empty($status)) return response()->json(['status'=>false,'msg'=>'参数错误']);
        if(!in_array($status,['2','3'])) return response()->json(['status'=>false,'msg'=>'参数错误']);
        if(empty($ap_id)) return response()->json(['status'=>false,'msg'=>'用户未登录']);
        if($status == 2){
            $apply_ob = new AreaStationApply();
            $data = $apply_ob->check_apply($id,$this->_school_id);
            if($data['status'] == false) return response()->json($data);
        }
        $up_data = ['ap_id'=>$ap_id,'ap_name'=>\Auth::user()->name,
            'status'=>$status ,'comment'=>$comment];
        AreaStationApply::where('id',$id)->update($up_data);

        //@发送邮件通知审核结果未做
        return response()->json(['status'=>true,'msg'=>'succ']);
    }
    //工位删除
    public function enrollDelete(Request $request){

        $id = intval(trim($request->input('id','')));
        $apply_ob = new AreaStationApply();
        $apply_ob = $apply_ob->where('delete_flag',1)->where('school_id',$this->_school_id);
        $apply = '';
        if(!empty($id)){
            $apply = $apply_ob->find($id);
        }
        if(empty($apply)) return redirect('error')->with(['msg'=>'找不到该申请记录！', 'href'=>'/management/stationEnroll']);
        $apply->delete_flag = 0;
        $apply->save();
        return redirect('success')->with(['msg'=>'操作成功！', 'href'=>'/management/stationEnroll']);
    }
    //日历
    public function calendar(Request $request)
    {
        $start_date = trim($request->input('start_date',date('Y-m-d')));
        $end_date = date('Y-m-d',strtotime($start_date)+86400*6);

        //获取所有公共的 会议室
        $labor_ob = new Labora();
        $labor_ob = $labor_ob->where('delete_flag',1)->where('school_id',$this->_school_id);
        $laboras = $labor_ob->where('labor_type','0')->get()->toArray();
        $data = [];
        //获取每一天的占用会议室情况
        $apply_ob = new Apply();
        for($start =strtotime($start_date);$start<=strtotime($end_date);$start+=86400){
            $tmp = date('Y-m-d',$start);
            $tmp_data= $apply_ob->whereRaw("delete_flag=1 AND school_id='$this->_school_id' AND status=2
                AND date_format(start_date,'%Y-%m-%d')<='$tmp'
                AND '$tmp'<= end_date"
            )->get();
            foreach($tmp_data as $tmp_list){
                $tmp_list->confirm_cn  = $this->confirm_cn[$tmp_list->confirm];
            }
            $tmp_data = $tmp_data->toArray();
            $data[] = $tmp_data;
        }
        return View('default.management.calendar',compact('laboras','data','start_date','end_date'));
    }
//访客登记
public function visitor(Request $request)
    {
        $vis_name = trim($request->input('vis_name',''));
        $visitor_ob = new Visitor();
        $visitor_ob = $visitor_ob->where('delete_flag',1)->where('school_id',$this->_school_id);
        if(!empty($vis_name)) $visitor_ob = $visitor_ob->where('vis_name','like',"%$vis_name%");
        $visitor_list = $visitor_ob->orderBy('id','DESC')->paginate($this->_limit);
        foreach($visitor_list as $list){
            $list->doc_type_cn = $this->doc_type_cn[$list->doc_type];
            if( $list->leave=="0000-00-00 00:00:00")$list->leave = '';
        }
        return View('default.management.visitor',compact('visitor_list'));
    }
    //访客登记添加和编辑
    public function visitorAdd(Request $request)
    {
        $id = intval(trim($request->input('id','')));
        $visitor = '';
        $visitor_ob = new Visitor();
        $visitor_ob = $visitor_ob->where('delete_flag',1)->where('school_id',$this->_school_id);
        if(!empty($id)){
            $visitor = $visitor_ob->find($id);
        }
        $data = $request->input();
        if(isset($data['_token'])) unset($data['_token']);
        if($request->method() == 'POST'){
            if(empty($request->input('cer_no')) || empty($request->input('vis_name')))
                return redirect('error')->with(['msg'=>'身份证号或者姓名不能为空！', 'href'=>'/management/visitorAdd']);
            if(empty($visitor)){//新增
                if(isset($data['id'])) unset($data['id']);
                $data['created_at'] = $data['created_at'] ? date('Y-m-d H:i:s',strtotime($data['created_at'])) : date('Y-m-d H:i:s',time());
                Visitor::create($data);
            }else{//修改
                Visitor::where('id',$id)->update($data);
            }
            return redirect('success')->with(['msg'=>'操作成功！', 'href'=>'/management/visitor']);
        }
        $title = empty($id) ? '添加' : '编辑' ;
        return View('default.management.visitorAdd',compact('title','visitor'));
    }
    public function visitorDelete(Request $request){
        $id = intval(trim($request->input('id','')));
        $visitor = '';
        $visitor_ob = new Visitor();
        $visitor_ob = $visitor_ob->where('delete_flag',1)->where('school_id',$this->_school_id);
        if(!empty($id)){
            $visitor = $visitor_ob->find($id);
        }
        if(empty($visitor)) return redirect('error')->with(['msg'=>'找不到该访客记录！', 'href'=>'/management/visitor']);
        $visitor->delete_flag = 0;
        $visitor->save();
        return redirect('success')->with(['msg'=>'操作成功！', 'href'=>'/management/visitor']);
    }
}