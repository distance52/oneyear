<?php

namespace App\Http\Controllers\School;
use Illuminate\Http\Request;
use App\Models\Exampaper;
use App\Models\StudentFinalScore;
use App\Http\Requests;
use App\Http\Controllers\Controller;
use App\Models\Group;
use App\Models\GroupScore;
use App\Models\GroupStudent;
use App\Models\Score;

class GroupController extends BaseController
{

    private function _groupPingfen($group_id){
        $nums=Score::where('group_id',$group_id)->where('type',4)->pluck('num');
        $num_ids=$nums->unique();
        $pingfen_table=array();
        $exampaper_ids=Score::where('group_id',$group_id)->where('type',4)->whereIn('num',$num_ids)->pluck('exampaper_id');
        $exampaper_ids=$exampaper_ids->unique();
        $exampaper=Exampaper::whereIn('id',$exampaper_ids)->get(['id','name']);
        $exampapers=$exampaper->keyBy('id');
        $i=1;
        foreach($num_ids as $key=>$val){
            $arr['name']='第'.$i.'次评分';
            //这个评分表的得分
            $count=Score::where(['group_id'=>$group_id,'type'=>4,'num'=>$val])->count();
            $score=Score::where(['group_id'=>$group_id,'type'=>4,'num'=>$val])->sum('number');
            $arr['score']=$count>0?round($score/$count):0;
            $arr['addtime']=Score::where('group_id',$group_id)->where('type',4)->where('num',$val)->value('end_time');
            array_push($pingfen_table,$arr);
            $i++;
        }
        $totalScore=0;
        if(!empty($pingfen_table)){
            $total=array_sum(array_column($pingfen_table,'score'));
            $pingfen_time=count($exampaper_ids);
            $score=($pingfen_time)>0?round($total/$pingfen_time):0;
            $totalScore=$score;
        }
        return array('totalScore'=>$totalScore,'pingfenTable'=>$pingfen_table);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $school_id=$this->school_id;
        $type=$request->input('type',0);
        $aSearch = [];
        $name=$school_name=$squad_name=$where='';
        \Request::has('school_name') &&  $aSearch['school_name']=$school_name = \Request::input('school_name');
        \Request::has('name') &&  $aSearch['name']=$name = \Request::input('name');
        \Request::has('squad_name') &&  $aSearch['squad_name']=$squad_name = \Request::input('squad_name');
        $oObjs=Group::where(['school_id'=>$school_id,'type'=>$type]);
        if($name!=''){
            $oObjs = $oObjs->where('name','like', '%'.$name.'%');
        }
        if($squad_name!=''){
            $squad_id = Squad::where('name','like', '%'.$squad_name.'%')->pluck('id');
            $oObjs = $oObjs->whereIn('squad_id',$squad_id);
        }
        if($school_name!=''){
            $school_id=School::where('name','like', '%'.$school_name.'%')->pluck('id');
            $oObjs = $oObjs->whereIn('school_id',$school_id);
        }
        $oObjs=$oObjs->with('squad');
		$num['a'] = $oObjs->count();
		$oObjs=$oObjs->paginate(20);
		$num['b'] = $oObjs->count();
        foreach($oObjs as &$val){
            $pingfen=$this->_groupPingfen($val->id);
            $val->studentNum=GroupStudent::where(['group_id'=>$val->id,'type'=>$type])->count();
            $val->totalScore=$pingfen['totalScore'];
        }
		if (view()->exists(session('mode').'.schoolplat.group.list')){
			return View(session('mode').'.schoolplat.group.list', compact('oObjs','type','aSearch','num'));
		}else{
			return View('default.schoolplat.group.list', compact('oObjs','type','aSearch','num'));
		}
	}

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $school_id=$this->school_id;
        if(!$id){
            return back()->withInput()->withErrors(['msg' => '参数缺失',]);
        }
        $oObj = Group::where(['school_id'=>$school_id,'id'=>$id])->first();
        if($oObj){
            $group_student=GroupStudent::where(['group_id'=>$oObj->id])->with('student')->get();
            $type=1;
            if($oObj->type==1){
                $type=2;
            }
            foreach($group_student as &$v){
                $score=StudentFinalScore::where('student_id',$v->student_id)->where('type',$type)->value('score');
                $v['group_score']=empty($score)?0:$score;//该学生的项目组/专题组得分
            }
            $pingfen=$this->_groupPingfen($id);
            $pingfen_table=$pingfen['pingfenTable'];
            $oObj->totalScore=$pingfen['totalScore'];
			if (view()->exists(session('mode').'.schoolplat.group.show')){
				return View(session('mode').'.schoolplat.group.show', compact('oObj','group_student','squad_id','pingfen_table'));
			}else{
				return View('default.schoolplat.group.show', compact('oObj','group_student','squad_id','pingfen_table'));
			}
        }
        else {
            return back()->withInput()->withErrors(['msg' => '分组不存在',]);
        }
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
