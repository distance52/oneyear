<?php

namespace App\Http\Controllers\Api\Administrator;

use Illuminate\Http\Request;
use App\Http\Requests;
use App\Http\Controllers\Controller;
use App\Models\Question;
use App\Models\Tag;
// 题目管理
class QuestionController extends Controller
{
    
    public function store(Request $request)
    {

        $oQuestion = new Question();

        $oUser = \Auth::user();
        $oQuestion->name = $request->input('name');
        $oQuestion->school_id = $oUser->school_id;
        $oQuestion->user_id = $oUser->id;
        $oQuestion->type = $request->input('type');
        $oQuestion->score = $request->input("score");
        $oQuestion->halfscore = $request->input("halfscore",0);
        $oQuestion->desc = $request->input("desc");
        $oQuestion->options = json_encode($request->input('options'));
        if($request->input("type") == 2) {
            $oQuestion->answer = json_encode((array)$request->input('answer_kong'));
        } elseif ($request->input("type") == 3) {
            $oQuestion->answer = json_encode($request->input('answer_judge'));
        } else {
            $oQuestion->answer = json_encode($request->input('answer'));
        }
        //dd($oQuestion);
		if(!$request->name){
			return back()->withErrors([ 'msg' => '题干不能为空',]);
        }
        $oQuestion->save();
        if($request->input('tags','')) {
            $oTag = new Tag;
            $oTag->syncTags($request->input('tags',''), $oQuestion);
        }

        return redirect('/resource/question')->withErrors([
            'msg' => '添加成功',
        ]);
    }
}
