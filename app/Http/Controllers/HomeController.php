<?php

namespace App\Http\Controllers;

use App\Http\Requests;
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

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        // 获取教学方案、资源、用户数
        $count = [
            'plans' => Plan::count(),
            'cells' => Cell::count(),
            'modules' => Module::count(),
            'nodes' => Node::count(),
            'files' => File::count(),
            'videos' => File::where('type',1)->count(),
            'pics' => File::where('type',3)->count(),
            'words' => File::where('type',6)->count(),
            'users' => User::count(),
            'teachers' => User::where('plat',2)->count(),
            'students' => User::where('plat',3)->count(),
            'schools' => School::count(),
        ];
        $news = [
            'teachers' => User::where('plat',2)->orderBy('id','desc')->take(5)->with('school')->get(),
            'students' => User::where('plat',3)->orderBy('id','desc')->take(5)->with('school')->get(),
            'squads' => Squad::orderBy('id','desc')->take(5)->with('school')->get(),
        ];
        //
        $notices = Notice::orderBy('id','desc')->take(5)->get(['id','title','view']);
		if (view()->exists(session('mode').'.home')){
			return View(session('mode').'.home', compact('count','news','notices'));
		}else{
			return view('default.home', compact('count','news','notices'));
		}
    }
}
