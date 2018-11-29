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

use Illuminate\Routing\UrlGenerator;

error_reporting(1);
class ExamController extends BaseController
{
    /**
     * 学生端试卷预览,包括正确答案
     *
     * @return [type] [description]
     */
    public function view($id){
        $num = \Request::input('num',0);
        $oExam = Exampaper::whereId($id)->with("questions")->first();
        $oObjs = Score::where("exampaper_id",$oExam->id)->where("student_id",$this->student_id)->whereNum($num)->first();

        if($oObjs->type == 4 && $oObjs->number) { // 评分
            return redirect('error')->with(['msg'=>'已经提交过，不允许再次提交', 'href'=>app(UrlGenerator::class)->previous()]);
        }

        $newscore = json_decode($oExam->scorenew,1);
        $questions = isset($oExam->questions)?$oExam->questions:array();
        $student_answer = json_decode($oObjs->content,1);

        foreach($questions as $q) {
            $total['total']['itemtotal'] += 1;
            $total['total']['scoretotal'] += $newscore[$q->id];
            if($q['halfscore'] !=0 && $q['type'] == 1){
                $questionsarr['morechoise'][] = $q;
                $total['morechoise']['itemtotal'] += 1;
                $total['morechoise']['scoretotal'] += $newscore[$q->id];
                $rightanswer[$q['id']] = json_decode($q['answer']);
                continue;
            }
            $rightanswer[$q['id']] = json_decode($q['answer']);
            $questionsarr[$q['type']][] = $q;
            $total[$q['type']]['itemtotal'] += 1;
            $total[$q['type']]['scoretotal'] +=  $newscore[$q->id];
            if($q['type'] == '4') {
                //分离出来的答案
                $fujian = explode("##",$student_answer[$q['id']]);
                $fujian = $fujian['0'];   
                $student_answer[$q['id']] = $fujian;
            } 
        }
        $showright = (strtotime($oObjs->end_time)<time())?1:0;
        if(empty($oObjs)) $showright = 0;
        $audit_score = json_decode($oObjs->audit_number,1);
        $comment = json_decode($oObjs->comment,1); 
		if (view()->exists(session('mode').'.studentPlat.exam.view')){
			return View(session('mode').'.studentPlat.exam.view',compact("oObjs","oExam","total","questionsarr","student_answer","rightanswer","showright","audit_score","comment"));
		}else{
			return View('default.studentPlat.exam.view',compact("oObjs","oExam","total","questionsarr","student_answer","rightanswer","showright","audit_score","comment"));
		}
    }

    /**
     * 获取用户相关环节的作业列表.
     * @param  Request $request [description]
     * @return [type]           [description]
     */
    public function index(Request $request){
        $oObjs = Score::where("student_id",$this->student_id);
        if($request->squad_id){
            $oObjs = $oObjs->where('squad_id',$request->squad_id);
        }
        $squad_id = $request->squad_id;
            $oObjs=$oObjs->where(function($q) {
                $q->orWhere('type',0);
                $q->orWhere('type',2);
            })
            ->with("exampaper")
            ->with("module")
            ->with("squad")
            ->with("cell")
            ->orderby("created_at","desc")
            ->get();
        if(!empty($oObjs)) {
            foreach($oObjs as $key=>&$obj) {
                if(null !== $obj->exampaper) {
                    $newscore = json_decode($obj->exampaper->scorenew,1);
                    $obj->totalitem = count($obj->exampaper->questions);
                    $obj->totalscore = array_sum($newscore);
                } else {
                    unset($oObjs->$key);
                     $obj->totalitem =0;
                     $obj->totalscore = 0;
                }
                if($obj->student_status == 0 && strtotime($obj->end_time)<time()) {
                    $this->autoSetNumber($obj->id);
                }
                //在这个地方判断结束时间已经到了但是学生没有答题的情况.更新数据.
            }
        }
		if (view()->exists(session('mode').'.studentPlat.exam.list')){
			return View(session('mode').'.studentPlat.exam.list',compact("oObjs",'squad_id'));
		}else{
			return View('default.studentPlat.exam.list',compact("oObjs",'squad_id'));
		}
    }

    private function autoSetNumber($id) {
        $oObj = Score::where('id',$id)->first();
        $oObj->student_status = $oObj->student_status + 1;
        $oObj->audit_user_id = 0;
        $oObj->audit_time = time();
        $oObj->number = 0;
        $oObj->teacher_number = 0;
        $oObj->save();

    }
    /**
     * 学生完成作业方法.
     * @return [type] [description]
     */
    public function exam($id,$num=0){
        $oExam = Exampaper::whereId($id)->with("questions")->first();
        //\Log::info('do/exam:student_id:',['student_id' => $this->student_id]);
        $oObjs = Score::where("exampaper_id",$oExam->id)->where("student_id",$this->student_id)->whereNum($num)->first();
        //\Log::info('do/exam:score:',['obj' => $oObjs]);
        if($oObjs) {
            // 如果是路演或者评分 ，已经提交的不允许再次提交
            $pre_url = app(UrlGenerator::class)->previous();
            if($oObjs->type == 4 || $oObjs->type == 5) {
                if($oObjs->student_status > 0) {
                    // 错误页面
                    return redirect('error')->with(['msg'=>'已经提交过了，不允许再次答题', 'href'=>$pre_url]);
                }
            }
            // 超过截至时间
            if($oObjs->end_time < strtotime("Y-m-d H:i:s")) {
                // 错误页面
                return redirect('error')->with(['msg'=>'作业已经过期了！', 'href'=>$pre_url]);
            }
            // 老师批阅的，不允许再次提交
            if($oObjs->teacher_status > 0) {
                // 错误页面
                return redirect('error')->with(['msg'=>'老师已经批阅改试卷了，不允许重复答题！', 'href'=>$pre_url]);
            }
            //
            $newscore = json_decode($oExam->scorenew,1);

            $questions = isset($oExam->questions)?$oExam->questions:array();
            foreach($questions as $q) {
                $total['total']['itemtotal'] += 1;
                $total['total']['scoretotal'] += $newscore[$q->id];
                if($q['halfscore'] !=0 && $q['type'] == 1){
                    $questionsarr['morechoise'][] = $q;
                    $total['morechoise']['itemtotal'] += 1;
                    $total['morechoise']['scoretotal'] += $newscore[$q->id];
                    continue;
                }
                $questionsarr[$q['type']][] = $q;
                $total[$q['type']]['itemtotal'] += 1;
                $total[$q['type']]['scoretotal'] +=  $newscore[$q->id];

            }
            $oObjs->startwhen = time();
            $oObjs->save();
            if($oObjs->whenstart  == 0){
                $oExam->timelimit = ($oExam->times*60);
            } else {
                $oExam->timelimit =($oExam->times*60)-(time()-$oObjs->whenstart);
            }
            $student_answer = json_decode($oObjs->content,1);
			if (view()->exists(session('mode').'.studentPlat.exam.do')){
				return View(session('mode').'.studentPlat.exam.do',compact("oObjs","oExam","total","questionsarr","student_answer"));
			}else{
				return View('default.studentPlat.exam.do',compact("oObjs","oExam","total","questionsarr","student_answer"));
			}
        } else {
            // 这里记录到日志里面，有时候会出错
//             \Log::error('exam error', $id, $num, $this->student_id);
             \Log::info('exam error',['id' => $id,'num'=>$num , 'student_id'=> $this->student_id]);
			 return redirect('error')->with(['msg'=>'参数错误！', 'href'=>$pre_url]);
        }

    }
    /**
     * 学生交考卷方法
     * @return [type] [description]
     */
    public function handin(Request $request,$id){
        $num = \Request::input('num',0);
        $oExam = Exampaper::whereId($id)->with("questions")->first();
        $oObjs = Score::where("exampaper_id",$oExam->id)->where("student_id",$this->student_id)->whereNum($num)->first();
        // 过期的不允许提交
        if(strtotime($oObjs->end_time) <= time()) {
            return redirect('error')->with(['msg'=>'作业已过期', 'href'=>app(UrlGenerator::class)->previous()]);
        }
        // 老师已经批改的不允许提交
        if($oObjs->teacher_status > 0) {
            return redirect('error')->with(['msg'=>'老师已经阅卷，禁止再次提交', 'href'=>app(UrlGenerator::class)->previous()]);
        }
        $questions = isset($oExam->questions)?$oExam->questions:array();
        $answer = $request->input("answer");

        //文件上传数据接收
        $question_id = $request->input("question_id");
         //文件上传;
        if ($request->hasFile('icon')) {
            if ($request->file('icon')){
                $file = $request->file('icon'); 
                foreach ($file as $key => $upload) {
                    if (!empty($upload)) {
                        $file_name = time().str_random(6).$upload->getClientOriginalName();
                        \Storage::disk('oss')->put($file_name, file_get_contents($upload->getRealPath()));
                        if(\Storage::disk('oss')->exists($file_name)) {
                            $oObjs->icon = $file_name;
                        } else {
                            return back()
                            ->withInput()
                            ->withErrors([
                                'msg' => '文件上传失败',
                                ]);
                        } $answer["$key"] = $answer["$key"]."##".$file_name;
                    }
                } 
            } else {
                return back()
                    ->withInput()
                    ->withErrors([
                        'msg' => '文件上传失败',
                    ]);
            }
        }
        foreach($questions as $q) {
            $all_answer[$q['id']] = isset($answer[$q['id']])?$answer[$q['id']]:"";
        }
       
        $oObjs->content = json_encode($all_answer);
        $scoreres = $this->checkmyscore($id,json_encode($all_answer)); 
        $oObjs->number = $scoreres['totalscore'];
        if($scoreres['system'] == 0) {
            $oObjs->audit_user_id = 0;
            $oObjs->audit_time = time();
        }
        $oObjs->student_status = $oObjs->student_status + 1;

       
        unset($oObjs->icon);
        $oObjs->save();
        $squad_id = $oObjs->squad_id;
        $url = "/course/study/list/{$squad_id}";
        return redirect($url)->withErrors([
            'msg' => '添加成功',
        ]);
    }

    /**
     * 计算试卷答案获取的分数
     * @param  [type] $exampaperid [description]
     * @param  [type] $answer      [description]
     * @return [type]              [description]
     */
    private function checkmyscore($exampaperid,$answer){
        $exam = Exampaper::where("id",$exampaperid)->with("questions")->first();
        $answer = json_decode($answer,1);
        $res = array("totalitem"=>0,"fullscore"=>0,"totalscore"=>0,"system"=>0);
        $newscore = json_decode($exam->scorenew,1);
        $totalscore = 0;
        if(!empty($exam->questions)) {
            $res['totalitem'] = count($exam->questions);
            foreach($exam->questions as $question) {
                $res['fullscore'] += $question['score'];
                $rightanswer[$question['id']] = json_decode($question['answer'],1);
                $score[$question['id']] = array("score"=>$question['score'],"halfscore"=>$question['halfscore']);
                if($question['type'] == 1 && $question['halfscore'] !=0) {
                    //多选题
                    if(empty($answer[$question['id']])) continue; //答案为空,直接跳过.
                    $intersect  = array_intersect($answer[$question['id']] , $rightanswer[$question['id']]);
                    if(count($answer[$question['id']]) > count($rightanswer[$question['id']])) {
                        //答案数量过多,直接判错

                        continue;
                    } elseif(strlen(implode($answer[$question['id']]))  ==  strlen(implode($rightanswer[$question['id']]))){
                        //答案数量相同,
                        if(implode($intersect) == implode($rightanswer[$question['id']])){
                            //选择的是正确答案.
                            $tmpscore = explode("|",$newscore[$question['id']]);
                            $totalscore += $tmpscore[0];
                        } else {
                            //选择的答案里面有错误的
                            continue;
                        }
                    } elseif(strlen($answer[$question['id']]) < strlen(implode($rightanswer[$question['id']]))){
                        //存在漏选或者错选情况
                        $wrong = 0;
                        foreach($intersect  as $piece) {
                            !in_array($piece,$rightanswer[$question['id']]) && $wrong = 1;
                        }
                        $tmpscore = explode("|",$newscore[$question['id']]);
                        $totalscore += $wrong == 1?$tmpscore[1] :0;
                    }
                } elseif($question['type'] == 1 && $question['halfscore'] == 0) {
                    //单选题
                    if(empty($answer[$question['id']])) continue; //答案为空,直接跳过.
                    if($answer[$question['id']] == $rightanswer[$question['id']][0]) {
                       $totalscore += $newscore[$question['id']];
                    }
                } elseif($question['type'] == 2) {
                    //填空题
                    if(empty($answer[$question['id']])) continue; //答案为空,直接跳过.
                    foreach($answer as $answer_piece) {
                        if(in_array($answer_piece,$rightanswer[$question['id']])){
                            $totalscore += $newscore[$question['id']];
                        }
                    }
                } elseif ($question['type'] == 3) {
                    //判断题
                    if(empty($answer[$question['id']])) continue; //答案为空,直接跳过.
                    if($answer[$question['id']] == $rightanswer[$question['id']]) {
                       $totalscore += $newscore[$question['id']];
                    }
                } elseif($question['type'] == 5) {
                    if(empty($answer[$question['id']])) continue; //答案为空,直接跳过.
                    $choise_arr = ["A","B","C","D"];
                    $offset = array_search($answer[$question['id']],$choise_arr);
                    $score_list = json_decode($question['options'],1);
                    $totalscore += $score_list[$offset];
                } elseif($question['type'] == 4) {
                    $res['system'] = 1;
                } else {

                }

            }
        }
        $res['totalscore'] = $totalscore;
        return $res;
    }
}
