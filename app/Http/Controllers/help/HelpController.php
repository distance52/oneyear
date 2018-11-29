<?php

namespace App\Http\Controllers\help;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Requests;
use App\Models\Ext_help;
use App\Models\User;

class HelpController extends Controller
{
    public function index(Request $request)
    {
       $oBasic = config('basic');
       return View('help.index', compact('oBasic'));
    }

	public function show(Request $request,$id)
    {
    	$type = $request->type;
    	$oObjs = Ext_help::where('id',$id)->first();
      	$oBasic = config('basic');
        return View('help.show', compact('oObjs','type','oBasic'));
    }

    public function lists(Request $request)
    {

    	$type = $request->type;
    	$oObjs = null;
    	if($request->name){
    		$oObjs = Ext_help::where('title','like','%'.$request->name.'%')->get();
    	}else{
	    	if($request->route){
	    		$oObjs = Ext_help::where('route','like',$request->route.'%')->get();
	    	}else{
	    		$oObjs = Ext_help::where('hot',1)->get();
	    	}	
    	}
    	$oBasic = config('basic');
       return View('help.list', compact('type','oObjs','oBasic'));
    }

    public function save(Request $request)
    {
    	
    	$oObjs = Ext_help::find($request->id);
    	switch($request->reason)
    	{
    		case 1:
    			$oObjs->answer1 += 1;
    		break;
    		case 2:
    			$oObjs->answer2 += 1;
    		break;
    		case 3:
    			$oObjs->answer3 += 1;
    		break;
    		case 4:
    			$oObjs->answer4 += 1;
    		break;
    		case 5:
    			$oObjs->answer5 += 1;
    		break;

    	}
    	$oObjs->save();
        return response()->json('ok')->setStatusCode(200);
    }
   
}
