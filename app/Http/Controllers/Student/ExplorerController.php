<?php

namespace App\Http\Controllers\Student;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Session;
use App\Models\NodeQa;
use App\Models\User;
use App\Models\Student;

class ExplorerController extends BaseController
{
    public function index(){
		if (view()->exists(session('mode').'.studentPlat.explorer.index')){
			return View(session('mode').'.studentPlat.explorer.index');
		}else{
			return View('default.studentPlat.explorer.index');
		}
    }
}
