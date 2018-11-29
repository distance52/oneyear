<?php

namespace App\Http\Controllers\Student;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Session;
use App\Models\Squad;
use App\Models\School;
use App\Models\Student;
use App\Models\GroupStudent;
use App\Models\SquadStruct;
use App\Models\StudentFinalScore;

class ScoreController extends BaseController
{
	public function index($squad_id){

		$student_id=$this->student_id;
		//查询出该学校配置的各积分占比
		$school=School::where('id',$this->school_id)->first();
		$rank=$school->score_rank;
		$up=$school->up;
		$down=$school->down;
		$pass=$school->pass;

		//班内成绩
//      $squad_id = $squad_id;
		$student_ids = \DB::table('squad_structs')->where('squad_id',$squad_id)->where('type',1)->pluck('struct_id');
		$obj = Student::whereIn('id',$student_ids);
		$num = $obj->count();

		//统计这个班所有学生和国内的每一项的成绩
		$scores = StudentFinalScore::whereIn('student_id',$student_ids)->get()->toArray();
		$scoresos = [];

		if(!empty($student_ids[0])){
			$scoresos[1] = $this->sum_score($student_ids,1,$num);
			$scoresos[2] = $this->sum_score($student_ids,2,$num);
			$scoresos[3] = $this->sum_score($student_ids,3,$num);
			$scoresos[4] = $this->sum_score($student_ids,4,$num);
			$scoresos[5] = $this->sum_score($student_ids,5,$num);
			$scoresos[6] = $this->sum_score($student_ids,6,$num);
			$scoresos[8] = $this->sum_score($student_ids,8,$num);
		}
		$rk_float=array();
		if(empty($rank)){
			$rank=[0,0,0,0,0,0];
			$rank_new=[0,0,0,0,0,0];
		}else{
			$rank=json_decode($rank,true);
			foreach($rank as $k=>$v){
				$rk_float[$k]=0.01*$v;
			}
			$rank_new=$rank;
			$rank=$rk_float;
		}
		if(empty($up)){
			$ups=[100,100,100,100,100,100,100];
		}else{
			$ups=json_decode($up,true);
		}
		if(empty($down)){
			$downs=[20,20,20,20,20,20,20];
		}else{
			$downs=json_decode($down,true);
		}

		$pass = empty($pass) ? 0 : 0.01*$pass;

		$sscore  = 0;
		foreach($scores as $v){
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
					if($v['score'] >= $scoresos[$v['type']]){
						$v['score'] = $up;
					}else{
						$v['score'] = round($v['score']/$scoresos[$v['type']]*100);
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
					if(isset($v['score8']) && !empty($rank[6])){
						$v['score8']*=$rank[6];
					}
					break;
			}

			$score1[$v['student_id'].'_'.$v['type']]=$v['score'];
			$sscore +=$v['score'];
		}
		$nscore = $sscore/$num*$pass/0.6*0.01;
		$obj=array();
		//将各积分的比率转换为小数

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

			$obj['score'.$i]=isset($score1[$student_id.'_'.$i])?$score1[$student_id.'_'.$i]:$down;
			$obj['scores'.$i]=isset($score[$student_id.'_'.$i])?$score[$student_id.'_'.$i]:0;
		}
		if($nscore){
			$obj['totalscores']=$obj['score1']+$obj['score2']+$obj['score3']+$obj['score4']+$obj['score5']+$obj['score6']+$obj['score7']+$obj['score8'];
			$obj['totalscore']= $obj['totalscores']/$nscore;
			if($obj['totalscore']>=90){
				$obj['totalscore'] = 'A';
			}elseif($obj['totalscore']>=75){
				$obj['totalscore'] = 'B';
			}elseif($obj['totalscore']>=60){
				$obj['totalscore'] = 'C';
			}else{
				$obj['totalscore'] = 'D';
			}

		}else{
			$obj['totalscores']=0;
			$obj['totalscore']="D";
		}
		$score3 = [];
		foreach($student_ids as $student_idv)
		{
			if(empty($score3[$student_idv])){
				$score3[$student_idv]=0;
			}
			for($i=1;$i<=8;$i++){
				if(!empty($score1[$student_id.'_'.$i])){
					$score3[$student_idv] +=$score1[$student_id.'_'.$i];
				}
			}

		}
		$results = [];
		if($score3[$student_id]>=$sscore/$num){
			asort($score3);
			$results['a'] = 1;
		}else{
			arsort($score3);
			$results['a'] = 2;
		}
		$score3 = array_keys($score3);
		for($i=0;$i<count($score3);$i++){
			if($score3[$i]==$student_id){
				$results['b'] = round($i/$num*100);
			}
		}
		if (view()->exists(session('mode').'.studentPlat.score.list')){
			return View(session('mode').'.studentPlat.score.list', compact('obj','rank_new','results','squad_id'));
		}else{
			return View('default.studentPlat.score.list', compact('obj','rank_new','results','squad_id'));
		}
	}
	public function sum_score($arr,$type,$num)
	{
		$scoresos = StudentFinalScore::whereIn('student_id',$arr)->where('type',$type)->sum('score');
		$scoresos = $scoresos/$num;
		return $scoresos;
	}
}
