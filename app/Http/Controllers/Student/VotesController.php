<?php

namespace App\Http\Controllers\Student;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use App\Http\Requests;
use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\SquadStruct;
use App\Models\File;
use App\Models\Score;
use App\Models\Exampaper;
use App\Models\Ext_wj_examp;
use App\Models\Ext_wj_question;
use App\Models\Ext_wj_result;
use Illuminate\Routing\UrlGenerator;
use GatewayClient\Gateway as Gateway;
use App\Models\Teacher;

error_reporting(1);
class VotesController extends BaseController
{
    /**
     * 学生端试卷预览,包括正确答案
     *
     * @return [type] [description]
     */

    public function view($id){
        $oUser = \Auth::user();
        $oScore = '';
        if($oUser->plat == 3) {
            //该学生没有此作业，无权查看
            $squad_id = SquadStruct::where('struct_id',$oUser->student->id)->pluck('squad_id')->toArray();
            $oScore = \DB::table('ext_wj_send')->where('id',$id)->whereIn('squad_id',$squad_id)->first(); 
            if(!$oScore) {
                return redirect('error')->with(['msg'=>'非法操作!', 'href'=>app(UrlGenerator::class)->previous()]);
            }
        }
        $oExam = Ext_wj_examp::find($oScore->wj_id);
        $oQuestion = Ext_wj_question::where('wj_id',$oScore->wj_id)->orderBy('sort');
        $num = $oQuestion->count();
        $oQuestion = $oQuestion->get();
        foreach($oQuestion as $oObjq)
        {
          $oObjq->answer = json_decode($oObjq->answer);
		  $oObjq->s_answer = \DB::table('ext_wj_results')->where('ws_id',$id)->where('wq_id',$oObjq->id)->where('student_id',$oUser->student->id)->first()->wq_answer;
		  if($oObjq->type==1){
			   $oObjq->s_answer = json_decode($oObjq->s_answer);
		  }
        }
        $type=['radio','checkbox'];
		$oExam->ws_id = $id;
      	if (view()->exists(session('mode').'.studentPlat.votes.view')){
      		return View(session('mode').'.studentPlat.votes.view',compact('oExam','num',"oQuestion",'type'));
      	}else{
      		return View('default.studentPlat.votes.view',compact('oExam','num',"oQuestion",'type'));
      	}
    }

    /**
     * 获取用户相关环节的作业列表.
     * @param  Request $request [description]
     * @return [type]           [description]
     */
    public function index($squad_id){
        $oUser = \Auth::user();
        $oStudent = Student::where('user_id',$oUser->id)->first();
        $squads =SquadStruct::where('type',1)->where('struct_id',$oStudent->id)->pluck('squad_id')->toArray();
        if(in_array($squad_id,$squads)){
            $votelist = \DB::table('ext_wj_send')->where('squad_id',$squad_id)->get(); 
            foreach($votelist as $vote)
            {
              $vote->title = Ext_wj_examp::where('id',$vote->wj_id)->first()->title;
			  $vote->end_time = date('Y-m-d H:i:s',$vote->time);
            }
            
            if (view()->exists(session('mode').'.studentPlat.votes.list')){
              return View(session('mode').'.studentPlat.votes.list',compact("votelist",'squad_id'));
            }else{
              return View('default.studentPlat.votes.list',compact("votelist",'squad_id'));
            }
        }else{
            return redirect('error')->with(['msg'=>'非法操作', 'href'=>app(UrlGenerator::class)->previous()]); 
        }

    }

    // private function autoSetNumber($id) {
    //     $oObj = Score::where('id',$id)->first();
    //     $oObj->student_status = $oObjs->student_status + 1;
    //     $oObj->audit_user_id = 0;
    //     $oObj->audit_time = time();
    //     $oObj->number = 0;
    //     $oObj->teacher_number = 0;
    //     $oObj->save();

    // }
    /**
     * 学生完成作业方法.
     * @return [type] [description]
     */
   //  public function exam($id,$num=0){
   //      $oExam = Exampaper::whereId($id)->with("questions")->first();
   //      $oObjs = Score::where("exampaper_id",$oExam->id)->where("student_id",$this->student_id)->whereNum($num)->first();
   //      if($oObjs) {
   //          // 如果是路演或者评分 ，已经提交的不允许再次提交
   //          $pre_url = app(UrlGenerator::class)->previous();
   //          if($oObjs->type == 4 || $oObjs->type == 5) {
   //              if($oObjs->student_status > 0) {
   //                  // 错误页面
   //                  return redirect('error')->with(['msg'=>'已经提交过了，不允许再次答题', 'href'=>$pre_url]);
   //              }
   //          }
   //          // 超过截至时间
   //          if($oObjs->end_time < strtotime("Y-m-d H:i:s")) {
   //              // 错误页面
   //              return redirect('error')->with(['msg'=>'作业已经过期了！', 'href'=>$pre_url]);
   //          }
   //          // 老师批阅的，不允许再次提交
   //          if($oObjs->teacher_status > 0) {
   //              // 错误页面
   //              return redirect('error')->with(['msg'=>'老师已经批阅改试卷了，不允许重复答题！', 'href'=>$pre_url]);
   //          }
   //          //
   //          $newscore = json_decode($oExam->scorenew,1);

   //          $questions = isset($oExam->questions)?$oExam->questions:array();
   //          foreach($questions as $q) {
   //              $total['total']['itemtotal'] += 1;
   //              $total['total']['scoretotal'] += $newscore[$q->id];
   //              if($q['halfscore'] !=0 && $q['type'] == 1){
   //                  $questionsarr['morechoise'][] = $q;
   //                  $total['morechoise']['itemtotal'] += 1;
   //                  $total['morechoise']['scoretotal'] += $newscore[$q->id];
   //                  continue;
   //              }
   //              $questionsarr[$q['type']][] = $q;
   //              $total[$q['type']]['itemtotal'] += 1;
   //              $total[$q['type']]['scoretotal'] +=  $newscore[$q->id];

   //          }
   //          $oObjs->startwhen = time();
   //          $oObjs->save();
   //          if($oObjs->whenstart  == 0){
   //              $oExam->timelimit = ($oExam->times*60);
   //          } else {
   //              $oExam->timelimit =($oExam->times*60)-(time()-$oObjs->whenstart);
   //          }
   //          $student_answer = json_decode($oObjs->content,1);
			// if (view()->exists(session('mode').'.studentPlat.exam.do')){
			// 	return View(session('mode').'.studentPlat.exam.do',compact("oObjs","oExam","total","questionsarr","student_answer"));
			// }else{
			// 	return View('default.studentPlat.exam.do',compact("oObjs","oExam","total","questionsarr","student_answer"));
			// }
   //      } else {
   //          // 这里记录到日志里面，有时候会出错
   //          // \Log::error('exam error', $id, $num, $this->student_id);
   //      }

   //  }
    /**
     * 学生交考卷方法
     * @return [type] [description]
     */
     public function handin(Request $request,$id)
	 {
		 $oUser = \Auth::user();
        // $oExam = Ext_wj_examp::whereId($id)->first();
		 $oObjs = \DB::table('ext_wj_send')->where('id',$id)->first();

		 $results = \DB::table('ext_wj_results')->where('ws_id',$id)->where('student_id',$this->student_id)->first();
         $teacher_uid = Teacher::whereId($oObjs->teacher_id)->value('user_id');

         // 过期的不允许提交
		if($oObjs->time <= time()) {
             return redirect('error')->with(['msg'=>'问卷已过期', 'href'=>app(UrlGenerator::class)->previous()]);
         }
		 if($results) {
             return redirect('error')->with(['msg'=>'你已经提交过', 'href'=>app(UrlGenerator::class)->previous()]);
         }
		$length = count($request->input('sort'));
		for($i=0;$i<$length;$i++)
		{
			$data[$i] = new Ext_wj_result;
			$data[$i]['wq_answer'] = $request->input('answer'.$i);
			if($request->input('type'.$i)==1){
				$data[$i]['wq_answer'] = json_encode($data[$i]['wq_answer']);
			}
			$data[$i]['wq_id'] = $request->input('id'.$i);
			$data[$i]['ws_id'] = $id;
			$data[$i]['student_id'] =$oUser->student->id;
            $data[$i]->save();
            $ans = $data[$i]['wq_answer'];
            if (!is_array(json_decode($ans,true))){
                $ans = json_encode(["{$ans}"]);
            }
            $message = array(
                'type'  => 'vote',
                'ws_id' => $oObjs->id,
                'wq_id' => $request->input('id'.$i),
                'data'  => $ans
            );
            Gateway::sendToUid($teacher_uid,json_encode($message));
		}
           return redirect('error')->with(['msg'=>'提交成功！', 'href'=>app(UrlGenerator::class)->previous()]);
    }

    /**
     * 计算试卷答案获取的分数
     * @param  [type] $exampaperid [description]
     * @param  [type] $answer      [description]
     * @return [type]              [description]
     */
    // private function checkmyscore($exampaperid,$answer){
    //     $exam = Exampaper::where("id",$exampaperid)->with("questions")->first();
    //     $answer = json_decode($answer,1);
    //     $res = array("totalitem"=>0,"fullscore"=>0,"totalscore"=>0,"system"=>0);
    //     $newscore = json_decode($exam->scorenew,1);
    //     $totalscore = 0;
    //     if(!empty($exam->questions)) {
    //         $res['totalitem'] = count($exam->questions);
    //         foreach($exam->questions as $question) {
    //             $res['fullscore'] += $question['score'];
    //             $rightanswer[$question['id']] = json_decode($question['answer'],1);
    //             $score[$question['id']] = array("score"=>$question['score'],"halfscore"=>$question['halfscore']);
    //             if($question['type'] == 1 && $question['halfscore'] !=0) {
    //                 //多选题
    //                 if(empty($answer[$question['id']])) continue; //答案为空,直接跳过.
    //                 $intersect  = array_intersect($answer[$question['id']] , $rightanswer[$question['id']]);
    //                 if(count($answer[$question['id']]) > count($rightanswer[$question['id']])) {
    //                     //答案数量过多,直接判错

    //                     continue;
    //                 } elseif(strlen(implode($answer[$question['id']]))  ==  strlen(implode($rightanswer[$question['id']]))){
    //                     //答案数量相同,
    //                     if(implode($intersect) == implode($rightanswer[$question['id']])){
    //                         //选择的是正确答案.
    //                         $tmpscore = explode("|",$newscore[$question['id']]);
    //                         $totalscore += $tmpscore[0];
    //                     } else {
    //                         //选择的答案里面有错误的
    //                         continue;
    //                     }
    //                 } elseif(strlen($answer[$question['id']]) < strlen(implode($rightanswer[$question['id']]))){
    //                     //存在漏选或者错选情况
    //                     $wrong = 0;
    //                     foreach($intersect  as $piece) {
    //                         !in_array($piece,$rightanswer[$question['id']]) && $wrong = 1;
    //                     }
    //                     $tmpscore = explode("|",$newscore[$question['id']]);
    //                     $totalscore += $wrong == 1?$tmpscore[1] :0;
    //                 }
    //             } elseif($question['type'] == 1 && $question['halfscore'] == 0) {
    //                 //单选题
    //                 if(empty($answer[$question['id']])) continue; //答案为空,直接跳过.
    //                 if($answer[$question['id']] == $rightanswer[$question['id']][0]) {
    //                    $totalscore += $newscore[$question['id']];
    //                 }
    //             } elseif($question['type'] == 2) {
    //                 //填空题
    //                 if(empty($answer[$question['id']])) continue; //答案为空,直接跳过.
    //                 foreach($answer as $answer_piece) {
    //                     if(in_array($answer_piece,$rightanswer[$question['id']])){
    //                         $totalscore += $newscore[$question['id']];
    //                     }
    //                 }
    //             } elseif ($question['type'] == 3) {
    //                 //判断题
    //                 if(empty($answer[$question['id']])) continue; //答案为空,直接跳过.
    //                 if($answer[$question['id']] == $rightanswer[$question['id']]) {
    //                    $totalscore += $newscore[$question['id']];
    //                 }
    //             } elseif($question['type'] == 5) {
    //                 if(empty($answer[$question['id']])) continue; //答案为空,直接跳过.
    //                 $choise_arr = ["A","B","C","D"];
    //                 $offset = array_search($answer[$question['id']],$choise_arr);
    //                 $score_list = json_decode($question['options'],1);
    //                 $totalscore += $score_list[$offset];
    //             } elseif($question['type'] == 4) {
    //                 $res['system'] = 1;
    //             } else {

    //             }

    //         }
    //     }
    //     $res['totalscore'] = $totalscore;
    //     return $res;
    // }
}
