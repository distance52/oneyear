<?php

namespace App\Http\Controllers\School;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Models\Squad;
use App\Models\School;
use App\Models\Student;
use App\Models\GroupStudent;
use App\Models\StudentFinalScore;

class ScoreController extends BaseController
{
    public function index()
    {
        $school_id=$this->school_id;
        $aSearch = [];
        $name=$squad_name=$where='';
        \Request::has('name') &&  $aSearch['name']=$name = \Request::input('name');
        \Request::has('squad_name') &&  $aSearch['squad_name']=$squad_name = \Request::input('squad_name');
        $oObjs=Student::where('school_id',$school_id);
        if($name!=''){
            $oObjs = $oObjs->where('name','like', '%'.$name.'%');
        }
        if($squad_name!=''){
            $squad_id = Squad::where('name','like', '%'.$squad_name.'%')->pluck('id');
            $oObjs = $oObjs->whereIn('squad_id',$squad_id);
        }
		$student_ids=$oObjs->pluck('id');
		
        $oObjs=$oObjs->orderBy('id','desc');
		$num['a'] = $oObjs->get()->count();
		$oObjs=$oObjs->paginate(20);
		$num['b'] = $oObjs->count();
		
		$squad_ids=Student::where('school_id',$school_id)->pluck('squad_id');
		$squad_score = [];
		if(!empty($squad_ids[0])){
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
        //查询出该学校配置的各积分占比
		$school=School::where('id',$this->school_id)->first();
        $rank=$school->score_rank;
        $up=$school->up;
        $down=$school->down;
        $pass=$school->pass;
        //统计这个班所有学生和国内的每一项的成绩
        $scores=StudentFinalScore::whereIn('student_id',$student_ids)->get()->toArray();
		$score1=[];
		
		//将各积分的比率转换为小数
        $rk_float=array();
        if(empty($rank)){
            $rank=[0,0,0,0,0,0];
        }
        else{
            $rank=json_decode($rank,true);
            foreach($rank as $k=>$v){
                $rk_float[$k]=0.01*$v;
            }
            $rank=$rk_float; 
        }
		if(empty($up)){
            $ups=[100,100,100,100,100,100,100];
        }else{
            $ups=json_decode($up,true);
        }	
		if(empty($down)){
            $downs=[0,0,0,0,0,0,0];
        }else{
            $downs=json_decode($down,true);
        }
		$pass = 0.01*$pass;
		$sscore = [];
		$numm = [];
        foreach($scores as $v)
		{
			$squad_id = Student::where('id',$v['student_id'])->first()->squad_id;
			
            $score[$v['student_id'].'_'.$v['type']]=$v['score'];
			
			switch($v['type'])
			{
				case 1:$up=$ups[0];$down=$downs[0];break;
				case 2:$up=$ups[1];$down=$downs[1];break;
				case 3:$up=$ups[2];$down=$downs[2];break;
				case 4:$up=$ups[3];$down=$downs[3];break;
				case 5:$up=$ups[4];$down=$downs[4];break;
				case 6:$up=$ups[5];$down=$downs[5];break;
				case 8:$up=$ups[6];$down=$downs[6];break;
			}
			
			if($v['type'] != 7){    
				if($v['score']){
					if($v['score'] >= $squad_score[$squad_id][$v['type']]){
						$v['score'] = $up;
					}else{
						$v['score'] = round($v['score']/$squad_score[$squad_id][$v['type']]*100);
					}
				}else{
					$v['score'] = $down;
				}
			}
			switch($v['type'])
			{
				case 1:$v['score']*=$rank[0];break;
				case 2:$v['score']*=$rank[1];break;
				case 3:$v['score']*=$rank[2];break;
				case 4:$v['score']*=$rank[3];break;
				case 5:$v['score']*=$rank[4];break;
				case 6:$v['score']*=$rank[5];break;
				case 8:
					if(!empty($rank[6])){
						$v['score8']*=$rank[6];
					}
					break;
			}
			
			$score1[$v['student_id'].'_'.$v['type']]=$v['score'];
			if(empty($numm[$squad_id])){
				$sscore[$squad_id] = 0;
				$numm[$squad_id] = 0;
			}
			$sscore[$squad_id] +=$v['score'];
			$numm[$squad_id] += 1;
        }
        $return=array();
        
        foreach($oObjs as $val){
            $arr=array();
            $arr['name']=$val->user->name;
            $arr['id']=$val->user->id;
            for($i=1;$i<=8;$i++){
				switch($i)
				{
					case 1:$up=$ups[0];$down=$downs[0]*$rank[0];break;
					case 2:$up=$ups[1];$down=$downs[1]*$rank[1];break;
					case 3:$up=$ups[2];$down=$downs[2]*$rank[2];break;
					case 4:$up=$ups[3];$down=$downs[3]*$rank[3];break;
					case 5:$up=$ups[4];$down=$downs[4]*$rank[4];break;
					case 6:$up=$ups[5];$down=$downs[5]*$rank[5];break;
					case 8:$up=$ups[6];
						if(!empty($rank[6])){
							$down=$downs[6]*$rank[6];
						}else{
							$down = 0;
						};break;
				}
				
                $arr['score'.$i]=isset($score1[$val->id.'_'.$i])?$score1[$val->id.'_'.$i]:$down;
                $arr['scores'.$i]=isset($score[$val->id.'_'.$i])?$score[$val->id.'_'.$i]:0;
            }
            if($rank){
				 $arr['totalscore']=$arr['score1']+$arr['score2']+$arr['score3']+$arr['score4']+$arr['score5']+$arr['score6']+$arr['score7']+$arr['score8'];
            }
            else{
                $arr['totalscore']=0;
            }
				if($arr['totalscore']==0){
					$arr['ranking'] = 'D';
				}else{
					$nscore = $sscore[$val->squad_id]/$numm[$val->squad_id]*$pass/0.6*0.01;
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
        $pager=$oObjs->appends($aSearch)->links();
		if(view()->exists(session('mode').'.schoolplat.score.list')){
			return View(session('mode').'.schoolplat.score.list', compact('return','aSearch','pager','num'));
		}else{
			return View('default.schoolplat.score.list', compact('return','aSearch','pager','num'));
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
}
