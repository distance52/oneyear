<?php

namespace App\Http\Controllers\Administrator\School;

use App\Models\Student;
use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use App\Models\Squad;
use App\Models\School;
use App\Models\GroupStudent;
use App\Models\StudentFinalScore;
class ScoreController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $aSearch = [];
        $name= $school_name=$squad_name=$type=$where='';
        \Request::has('school_name') &&  $aSearch['school_name']=$school_name = \Request::input('school_name');
        \Request::has('name') &&  $aSearch['name']=$name = \Request::input('name');
        \Request::has('squad_name') &&  $aSearch['squad_name']=$squad_name = \Request::input('squad_name');
        \Request::has('type') &&  $aSearch['type']= $type = \Request::input('type');
        $oObjs=Student::with('user');
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
        $oObjs=$oObjs->orderBy('id','desc');
		$num['a'] = $oObjs->count();
		$student_ids=$oObjs->pluck('id');
        $school_ids=$oObjs->pluck('school_id');
		$oObjs=$oObjs->paginate(20);
		$num['b'] = $oObjs->count();
       
        //查询出该学校配置的各积分占比
        $ranks=School::whereIn('id',$school_ids)->get(['id','score_rank','up','down','pass']);
        $rank_school=array();
        foreach($ranks as $val){
            $rk=json_decode($val->score_rank,true);
            if(empty($rk)){
                $rank_school[$val->id]=[0,0,0,0,0,0];
            }
            else{
                //将各积分的比率转换为小数
                $rk_float=array();
                foreach($rk as $k=>$v){
                    $rk_float[$k]=0.01*$v;
                }
                $rank_school[$val->id]=$rk_float;
            }
		if(empty($val->up)){
            $ups[$val->id]=[100,100,100,100,100,100,100];
        }else{
            $ups[$val->id]=json_decode($val->up,true);
        }	
		if(empty($val->down)){
            $downs[$val->id]=[0,0,0,0,0,0,0];
        }else{
            $downs[$val->id]=json_decode($val->down,true);
        }
		$pass[$val->id] = 0.01*$val->pass;
        }
		$squad_ids=Student::pluck('squad_id');
		$squad_score = [];
		if(!empty($squad_ids)){
			foreach($squad_ids as $squad_id)
			{
				if(empty($squad_score[$squad_id])){
					$obj = Student::where('squad_id',$squad_id);
					$student_idss =$obj->pluck('id');
					$numm =$obj->count();
					$squad_score[$squad_id] = $this->sum_score($student_idss,$numm);
				}
			}
		}
        //统计这个班所有学生和国内的每一项的成绩
        $scores=StudentFinalScore::whereIn('student_id',$student_ids)->get()->toArray();
        $score=array();
		$score1=[];
		$sscore = [];
		$numm = [];
        foreach($scores as $v){
            $score[$v['student_id'].'_'.$v['type']]=$v['score'];
			
			$student = Student::where('id',$v['student_id'])->first();
			switch($v['type'])
			{
				case 1:$up=$ups[$student->school_id][0];$down=$downs[$student->school_id][0];break;
				case 2:$up=$ups[$student->school_id][1];$down=$downs[$student->school_id][1];break;
				case 3:$up=$ups[$student->school_id][2];$down=$downs[$student->school_id][2];break;
				case 4:$up=$ups[$student->school_id][3];$down=$downs[$student->school_id][3];break;
				case 5:$up=$ups[$student->school_id][4];$down=$downs[$student->school_id][4];break;
				case 6:$up=$ups[$student->school_id][5];$down=$downs[$student->school_id][5];break;
				case 8:$up=$ups[$student->school_id][6];$down=$downs[$student->school_id][6];break;
			}
			
			if($v['type'] != 7){    
				if($v['score']){
					if($v['score'] >= $squad_score[$student->squad_id][$v['type']]){
						$v['score'] = $up;
					}else{
						$v['score'] = round($v['score']/$squad_score[$student->squad_id][$v['type']]*100);
					}
				}else{
					$v['score'] = $down;
				}
			}
			if(!empty($student->school_id)){
				if(!empty($rank_school[$student->school_id])){
					switch($v['type'])
					{
						case 1:$v['score']*=$rank_school[$student->school_id][0];break;
						case 2:$v['score']*=$rank_school[$student->school_id][1];break;
						case 3:$v['score']*=$rank_school[$student->school_id][2];break;
						case 4:$v['score']*=$rank_school[$student->school_id][3];break;
						case 5:$v['score']*=$rank_school[$student->school_id][4];break;
						case 6:$v['score']*=$rank_school[$student->school_id][5];break;
						case 8:
							if(!empty($rank_school[$student->school_id][6])){
								$v['score8']*=$rank_school[$student->school_id][6];
							}
							break;
					}
				}
			}
			
			$score1[$v['student_id'].'_'.$v['type']]=$v['score'];
			if(empty($numm[$student->squad_id])){
				$sscore[$student->squad_id] = 0;
				$numm[$student->squad_id] = 0;
			}
			$sscore[$student->squad_id] +=$v['score'];
			$numm[$student->squad_id] += 1;
        }
        $return=array();
		
        foreach($oObjs as $val){
            $arr=array();
            $arr['name']=$val->user?$val->user->name:'未知';
            $arr['id']=$val->id;
            for($i=1;$i<=8;$i++){
				switch($i)
				{
					case 1:$down=$downs[$val->school_id][0]*$rank_school[$val->school_id][0];break;
					case 2:$down=$downs[$val->school_id][1]*$rank_school[$val->school_id][1];break;
					case 3:$down=$downs[$val->school_id][2]*$rank_school[$val->school_id][2];break;
					case 4:$down=$downs[$val->school_id][3]*$rank_school[$val->school_id][3];break;
					case 5:$down=$downs[$val->school_id][4]*$rank_school[$val->school_id][4];break;
					case 6:$down=$downs[$val->school_id][5]*$rank_school[$val->school_id][5];break;
					case 8:
						if(!empty($rank_school[$val->school_id][6])){
							$down=$downs[$val->school_id][6]*$rank_school[$val->school_id][6];
						}else{
							$down = 0;
						};break;
				}
				
                $arr['score'.$i]=isset($score1[$val->id.'_'.$i])?$score1[$val->id.'_'.$i]:$down;
                $arr['scores'.$i]=isset($score[$val->id.'_'.$i])?$score[$val->id.'_'.$i]:0;
            }
            if($val->school_id==0){
                $arr['totalscore']=0;
            }
            else{
                 $arr['totalscore']=$arr['score1']+$arr['score2']+$arr['score3']+$arr['score4']+$arr['score5']+$arr['score6']+$arr['score7']+$arr['score8'];
            }
			
			if($arr['totalscore']==0){
					$arr['ranking'] = 'D';
				}else{
					$nscore = $sscore[$val->squad_id]/$numm[$val->squad_id]*$pass[$val->school_id]/0.6*0.01;
					$arr['ranking'] = $arr['totalscore']/$nscore;
					if($arr['ranking']>=90){
						$arr['ranking'] = 'A';
					}elseif($arr['ranking']>=75){
						$arr['ranking'] = 'B';
					}elseif($arr['ranking']>=60){
						$arr['ranking'] = 'C';
					}else{
						$arr['ranking'] = 'D';
					}
				}
			
            array_push($return,$arr);
        }
        $pager= $oObjs->appends($aSearch)->links();
		if (view()->exists(session('mode').'.school.score.list')){
			return View(session('mode').'.school.score.list', compact('return','type','aSearch','pager','num'));
		}else{
			return View('default.school.score.list', compact('return','type','aSearch','pager','num'));
		}
    }
	public function sum_score($arr,$num)
	{
		$scoresos[1] = StudentFinalScore::whereIn('student_id',$arr)->where('type',1)->sum('score');
		$scoresos[1] = $scoresos[1]/$num;
		$scoresos[2] = StudentFinalScore::whereIn('student_id',$arr)->where('type',2)->sum('score');
		$scoresos[2] = $scoresos[2]/$num;
		$scoresos[3] = StudentFinalScore::whereIn('student_id',$arr)->where('type',3)->sum('score');
		$scoresos[3] = $scoresos[3]/$num;
		$scoresos[4] = StudentFinalScore::whereIn('student_id',$arr)->where('type',4)->sum('score');
		$scoresos[4] = $scoresos[4]/$num;
		$scoresos[5] = StudentFinalScore::whereIn('student_id',$arr)->where('type',5)->sum('score');
		$scoresos[5] = $scoresos[5]/$num;
		$scoresos[6] = StudentFinalScore::whereIn('student_id',$arr)->where('type',6)->sum('score');
		$scoresos[6] = $scoresos[6]/$num;
		$scoresos[8] = StudentFinalScore::whereIn('student_id',$arr)->where('type',8)->sum('score');
		$scoresos[8] = $scoresos[8]/$num;
		return $scoresos;
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
        //
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
