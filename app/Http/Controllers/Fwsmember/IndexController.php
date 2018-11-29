<?php

namespace App\Http\Controllers\FwsMember;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use App\Models\Teaching\Plan;
use App\Models\Teaching\Cell;
use App\Models\Teaching\Module;
use App\Models\Teaching\Node;
use App\Models\File;
use App\Models\User;
use App\Models\School;
use App\Models\Squad;
use App\Models\Notice;
use Illuminate\Support\Facades\Redis as Redis;
use App\Models\Project;
use App\Models\ProjectSchoolProcess;
use App\Models\ProjectOverview;
use App\Models\ProjectReservation;
use App\Models\ProjectTutor;
use App\Models\ProjectSchoolTutor;
use App\Models\ProjectInvite;
use App\Models\ProjectGuide;
use App\Models\ProjectProcess;
use App\Models\ProgramCategory;
use App\Models\ProjectInfo;
class IndexController extends Controller
{
    public function index(){
        return view('default.provider.home');

    }





}
