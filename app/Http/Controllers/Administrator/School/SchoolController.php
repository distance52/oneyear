<?php

namespace App\Http\Controllers\Administrator\School;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use App\Models\School;
use App\Models\User;
use App\Models\Teaching\Plan;
use App\Services\Plugin\PluginManager;
use Illuminate\Support\Facades\Redis;
use DB;
use App\Models\Option;

// 学校管理
class SchoolController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $aSearch = [];
        \Request::has('title') && \Request::input('title') && $aSearch['title'] = \Request::input('title');
        $school_name=$where='';
        \Request::has('school_name') &&  $aSearch['school_name']=$school_name = \Request::input('school_name');
        $oObjs = School::with('teachers','squads','students');
        if($school_name!=''){
            $oObjs = $oObjs->where('name','like', '%'.$school_name.'%');
        }
        $oObjs = $oObjs->with('plans')->orderBy('id','desc');
		$num['a'] = $oObjs->count();
		$oObjs=$oObjs->paginate(20);

        $num['b'] = $oObjs->count();
        foreach($oObjs as &$oObj){
            if($oObj->logo && \Storage::disk('oss')->exists($oObj->logo)) {
//                $oObj->logo = \AliyunOSS::getUrl($oObj->logo, $expire = new \DateTime("+3 year"), $bucket = config('filesystems.disks.oss.bucket'));
                $oObj->logo='http://static.cnczxy.com/'.$oObj->logo;

            } else {
                $oObj->logo='/images/default-school.png';
            }
        }
        //print_r($oObjs->toArray());
		if (view()->exists(session('mode').'.school.school.list')){
			return View(session('mode').'.school.school.list', compact('oObjs','aSearch','num'));
		}else{
			return View('default.school.school.list', compact('oObjs','aSearch','num'));
		}
    }

    public function qrcode($id)
    {
        $oObj = School::where(['id'=>$id])->first();
        if($oObj){
            $url=$oObj->host_suffix.'.sc.cnczxy.com';
			if (view()->exists(session('mode').'.school.school.qrcode')){
				return View(session('mode').'.school.school.qrcode', compact('oObjs','url'));
			}else{
				return View('default.school.school.qrcode', compact('oObjs','url'));
			}
        }
        else {
            return back()->withInput()->withErrors(['msg' => '分组不存在',]);
        }

    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $sys_plan_list=Plan::where('school_id',0)->get(['id','name']);
		if (view()->exists(session('mode').'.school.school.create')){
			return View(session('mode').'.school.school.create', compact('sys_plan_list'));
		}else{
			return View('default.school.school.create', compact('sys_plan_list'));
		}
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Requests\SchoolRequest $request)
    {
        $oSchool = new School();
        if ($request->hasFile('logo')) {
            if ($request->file('logo')->isValid()){
                $file = $request->file('logo');
                $file_name = time().str_random(6).$file->getClientOriginalName();
                \Storage::disk('oss')->put($file_name, file_get_contents($file->getRealPath()));
                if(\Storage::disk('oss')->exists($file_name)) {
                    $oSchool->logo = $file_name;
                } else {
                    return back()->withInput() ->withErrors([
                        'msg' => '文件上传失败',
                    ]);
                }
            } else {
                return back()->withInput()->withErrors([
                   'msg' => '文件上传失败',
                ]);
            }
        }
        $server_user_id=$request->input('server_user_id',0);
        $admin_user_id=$request->input('admin_user_id',0);
        if($server_user_id>0){
            $oSchool->server_user_id = $server_user_id;
        }
        //关联用户的学校
        if($admin_user_id>0){
            $oSchool->admin_user_id = $admin_user_id;
        }
        $arr=array(
            $request->input('xmz_rank'),
            $request->input('ztz_rank'),
            $request->input('zy_rank'),
            $request->input('sp_rank'),
            $request->input('ly_rank'),
            $request->input('kp_rank'),
            $request->input('kt_rank'),
        );
		$arr1=array(
            $request->input('xmz_up'),
            $request->input('ztz_up'),
            $request->input('zy_up'),
            $request->input('sp_up'),
            $request->input('ly_up'),
            $request->input('kp_up'),
            $request->input('kt_up'),
        );
		$arr2=array(
            $request->input('xmz_down'),
            $request->input('ztz_down'),
            $request->input('zy_down'),
            $request->input('sp_down'),
            $request->input('ly_down'),
            $request->input('kp_down'),
            $request->input('kt_down'),
        );
        $total=0;
        foreach($arr as $val){
            $total=$total+intval($val);
        }
        if($total>100){
            return back()->withInput()->withErrors([
                'msg' => '成绩权重之和不能大于100',
            ]);
        }
        $oSchool->score_rank = json_encode($arr);//成绩权重
        $oSchool->up = json_encode($arr1);//成绩权重
        $oSchool->down = json_encode($arr2);//成绩权重
        $oSchool->pass = $request->input('pass');
        $oSchool->name = $request->input('name');
        $oSchool->short_name = $request->input('short_name','');
        $oSchool->province = $request->input('province','');
        $oSchool->city = $request->input('city','');
        $oSchool->address = $request->input('address','');
        $oSchool->identifier = $request->input('identifier','');
        $oSchool->email_suffix = $request->input('email_suffix','');
        $oSchool->host_suffix = $request->input('host_suffix','');
        $oSchool->start_time = $request->input('start_time');
        $oSchool->end_time = $request->input('end_time');
        $oSchool->contact_man = $request->input('contact_man','');
        $oSchool->contact_phone = $request->input('contact_phone','');
        $oSchool->is_over = (time()-strtotime($oSchool->end_time))<0? 0: 1;
//        $oSchool->longitude = $request->input('lng','');
//        $oSchool->latitude = $request->input('lat','');
        $oSchool->save();
        $school_id=School::where('email_suffix',$request->input('email_suffix'))->orderBy('id','desc')->take(1)->value('id');
        //关联用户的学校
        if($admin_user_id>0){
            $oUser=User::where('id',$admin_user_id)->first();
            $oUser->school_id=$school_id;
            $oUser->save();
        }
        //拷贝教学方案
        if(is_array($request->input('plans'))){
           /* $oPlan=new Plan();
            foreach($request->input('plans') as $val){
                $oPlan->copyPlan($val,$oSchool->id);
            }*/
			$id = $oSchool->id;
			foreach($request->input('plans') as $plan){
                    $plans['type'] = 1;
                    $plans['type_id'] = $plan;
					$plans['school_id'] = $id;
					\DB::table('plan_school')->insert($plans);
					$cells = \DB::table('plan_structs')->where('plan_id',$plan)->distinct()->pluck('cell_id');
					foreach($cells as $cell){
						$cells_data['type'] = 2;
						$cells_data['type_id'] = $cell;
						$cells_data['school_id'] = $id;
						\DB::table('plan_school')->insert($cells_data);
					}
					$modules = \DB::table('plan_structs')->where('plan_id',$plan)->distinct()->pluck('module_id');
					foreach($modules as $module){
						$modules_data['type'] = 3;
						$modules_data['type_id'] = $module;
						$modules_data['school_id'] = $id;
						\DB::table('plan_school')->insert($modules_data);
					}
					$nodes = \DB::table('plan_structs')->where('plan_id',$plan)->distinct()->pluck('node_id');
					foreach($nodes as $node){
						$nodes_data['type'] = 4;
						$nodes_data['type_id'] = $node;
						$nodes_data['school_id'] = $id;
						\DB::table('plan_school')->insert($nodes_data);
						$infos = \DB::table('nodes')->where('id',$node)->distinct()->pluck('info_id');
						foreach($infos as $info)
						{
						if($info!=0){
							$infos_data['type'] = 5;
							$infos_data['type_id'] = $info;
							$infos_data['school_id'] = $id;
							\DB::table('plan_school')->insert($infos_data);
								$files = \DB::table('infos')->where('id',$info)->distinct()->pluck('file_id');
								foreach($files as $file)
								{
									$files_data['type'] = 6;
									$files_data['type_id'] = $file;
									$files_data['school_id'] = $id;
									\DB::table('plan_school')->insert($files_data);
								}
							}
						}
						$exampapers = \DB::table('nodes')->where('id',$node)->distinct()->pluck('exampaper_id');
						foreach($exampapers as $exampaper)
						{
						if($exampaper!=0){
							$exampapers_data['type'] = 7;
							$exampapers_data['type_id'] = $exampaper;
							$exampapers_data['school_id'] = $id;
							\DB::table('plan_school')->insert($exampapers_data);
								$questions = \DB::table('exampaper_questions')->where('exampaper_id',$exampaper)->distinct()->pluck('question_id');
								foreach($questions as $question)
								{
									$questions_data['type'] = 8;
									$questions_data['type_id'] = $question;
									$questions_data['school_id'] = $id;
									\DB::table('plan_school')->insert($questions_data);
								}	
							}
						}
					}
                }
        }
//			if($request->mode!='default'){
//				$file_path = storage_path('app/mode.data');
//				$fp = fopen($file_path,'w+');
//				if(!$fp) {
//				  \Log::error('没有权限创建文件：'.$file_path);
//				  $msg = [
//					"custom-msg"=> ["修改失败"],
//				  ];
//				  return response()->json($msg)->setStatusCode(422);
//				}
//				$omode = config('mode');
//				$omode['id'][$oSchool->id] = $request->mode;
//				$omode['url'][$request->host_suffix] = $request->mode;
//				fwrite($fp, json_encode($omode));
//				fclose($fp);
//
//			}
			
        return redirect('/school/school')->withErrors(['msg' => '添加成功',]);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
         // 没有写完，不仅仅返回这些
        if(!$id) {
            $msg = [
              "msg"=> ["参数错误，非法操作"],
          ];
          return response()->json($msg)->setStatusCode(422);
        } else {
            return response()->json(School::whereId($id)->first());
        }
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $oObj = School::where(['id'=>$id])->first();
        if($oObj) {
            if ($oObj->logo && \Storage::disk('oss')->exists($oObj->logo)) {
                $oObj->logo = \AliyunOSS::getUrl($oObj->logo, $expire = new \DateTime("+1 day"), $bucket = config('filesystems.disks.oss.bucket'));
            } else {
                $oObj->logo = '';
            }
			$plan_id = \DB::table('plan_school')->where('school_id',$id)->where('type',1)->pluck('type_id');
            $plan_list = Plan::whereIn('id',$plan_id)->orwhere('school_id', $id)->get(['id', 'name']);//已选教学方案
//            $sys_plan_list=Plan::whereNotIn('id',$plan_id)->get(['id','name']);//可选教学方案
            $sys_plan_list=Plan::where('school_id',0)->get(['id','name']);
            $oObj->score_rank = json_decode($oObj->score_rank, true);
            $oObj->up = json_decode($oObj->up, true);
            $oObj->down = json_decode($oObj->down, true);
			$mode = config('mode');
			if (view()->exists(session('mode').'.school.school.edit')){
				return View(session('mode').'.school.school.edit', compact('oObj','plan_list','sys_plan_list','mode'));
			}else{
				return View('default.school.school.edit', compact('oObj','plan_list','sys_plan_list','mode'));
			}
        }
        else{
            return back()->withInput()->withErrors(['msg' => '学校不存在',]);
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Requests\SchoolRequest $request, $id)
    {
        $oSchool = School::find($id);
        if($oSchool) {
            if ($request->hasFile('logo')) {
                if ($request->file('logo')->isValid()){
                    $file = $request->file('logo');
                    $file_name = time().str_random(6).$file->getClientOriginalName();
                    \Storage::disk('oss')->put($file_name, file_get_contents($file->getRealPath()));
                    if(\Storage::disk('oss')->exists($file_name)) {
                        $oSchool->logo = $file_name;
                    } else {
                        return back()->withInput()->withErrors(['msg' => '文件上传失败',]);
                    }
                } else {
                    return back()->withInput()->withErrors(['msg' => '文件上传失败',]);
                }
            }
		$arr=array(
            $request->input('xmz_rank'),
            $request->input('ztz_rank'),
            $request->input('zy_rank'),
            $request->input('sp_rank'),
            $request->input('ly_rank'),
            $request->input('kp_rank'),
            $request->input('kt_rank'),
        );
		$arr1=array(
            $request->input('xmz_up'),
            $request->input('ztz_up'),
            $request->input('zy_up'),
            $request->input('sp_up'),
            $request->input('ly_up'),
            $request->input('kp_up'),
            $request->input('kt_up'),
        );
		$arr2=array(
            $request->input('xmz_down'),
            $request->input('ztz_down'),
            $request->input('zy_down'),
            $request->input('sp_down'),
            $request->input('ly_down'),
            $request->input('kp_down'),
            $request->input('kt_down'),
        );
            $total=0;
            foreach($arr as $val){
                $total=$total+intval($val);
            }
            if($total>100){
                return back()->withInput()->withErrors([
                    'msg' => '成绩权重之和不能大于100',
                ]);
            }
            $server_user_id=$request->input('server_user_id',0);
            $admin_user_id=$request->input('admin_user_id',0);
            if($server_user_id>0){
                $oSchool->server_user_id = $server_user_id;
            }
            //关联用户的学校
            if($admin_user_id>0){
                $oSchool->admin_user_id = $admin_user_id;
                $oUser=User::where('id',$admin_user_id)->first();
                $oUser->school_id=$oSchool->id;
                $oUser->save();
            }
            $oSchool->score_rank = json_encode($arr);//成绩权重
            $oSchool->up = json_encode($arr1);//成绩权重
            $oSchool->down = json_encode($arr2);//成绩权重
            $oSchool->pass = $request->input('pass');
            $oSchool->name = $request->input('name');
            $oSchool->short_name = $request->input('short_name');
            $oSchool->province = $request->input('province');
            $oSchool->city = $request->input('city');
            $oSchool->address = $request->input('address');
            $oSchool->identifier = $request->input('identifier');
            $oSchool->email_suffix = $request->input('email_suffix');
            $oSchool->host_suffix = $request->input('host_suffix');
            $oSchool->start_time = $request->input('start_time');
            $oSchool->end_time = $request->input('end_time');
            $oSchool->contact_man = $request->input('contact_man','');
            $oSchool->contact_phone = $request->input('contact_phone','');
            $oSchool->is_over = (time()-strtotime($oSchool->end_time))<0? 0: 1;
//            $oSchool->longitude = $request->input('lng','');
//            $oSchool->latitude = $request->input('lat','');
            $oSchool->save();
            //拷贝教学方案
            if(is_array($request->input('plans'))){
                /*$oPlan=new Plan();
                foreach($request->input('plans') as $val){
                    $oPlan->copyPlan($val,$oSchool->id);
                }*/
				//方案
				foreach($request->input('plans') as $plan){
                    $plans['type'] = 1;
                    $plans['type_id'] = $plan;
					$plans['school_id'] = $id;
					\DB::table('plan_school')->insert($plans);
					$cells = \DB::table('plan_structs')->where('plan_id',$plan)->distinct()->pluck('cell_id');
					foreach($cells as $cell){
						$cells_data['type'] = 2;
						$cells_data['type_id'] = $cell;
						$cells_data['school_id'] = $id;
						\DB::table('plan_school')->insert($cells_data);
					}
					$modules = \DB::table('plan_structs')->where('plan_id',$plan)->distinct()->pluck('module_id');
					foreach($modules as $module){
						$modules_data['type'] = 3;
						$modules_data['type_id'] = $module;
						$modules_data['school_id'] = $id;
						\DB::table('plan_school')->insert($modules_data);
					}
					$nodes = \DB::table('plan_structs')->where('plan_id',$plan)->distinct()->pluck('node_id');
					foreach($nodes as $node){
						$nodes_data['type'] = 4;
						$nodes_data['type_id'] = $node;
						$nodes_data['school_id'] = $id;
						\DB::table('plan_school')->insert($nodes_data);
						$infos = \DB::table('nodes')->where('id',$node)->distinct()->pluck('info_id');
						foreach($infos as $info)
						{
						if($info!=0){
							$infos_data['type'] = 5;
							$infos_data['type_id'] = $info;
							$infos_data['school_id'] = $id;
							\DB::table('plan_school')->insert($infos_data);
								$files = \DB::table('infos')->where('id',$info)->distinct()->pluck('file_id');
								foreach($files as $file)
								{
									$files_data['type'] = 6;
									$files_data['type_id'] = $file;
									$files_data['school_id'] = $id;
									\DB::table('plan_school')->insert($files_data);
								}
							}
						}
						$exampapers = \DB::table('nodes')->where('id',$node)->distinct()->pluck('exampaper_id');
						foreach($exampapers as $exampaper)
						{
						if($exampaper!=0){
							$exampapers_data['type'] = 7;
							$exampapers_data['type_id'] = $exampaper;
							$exampapers_data['school_id'] = $id;
							\DB::table('plan_school')->insert($exampapers_data);
								$questions = \DB::table('exampaper_questions')->where('exampaper_id',$exampaper)->distinct()->pluck('question_id');
								foreach($questions as $question)
								{
									$questions_data['type'] = 8;
									$questions_data['type_id'] = $question;
									$questions_data['school_id'] = $id;
									\DB::table('plan_school')->insert($questions_data);
								}	
							}
						}
					}
                }
            }
			
//			$file_path = storage_path('app/mode.data');
//        //
//			$fp = fopen($file_path,'w+');
//			if(!$fp) {
//			  \Log::error('没有权限创建文件：'.$file_path);
//			  $msg = [
//				"custom-msg"=> ["修改失败"],
//			  ];
//			  return response()->json($msg)->setStatusCode(422);
//			}
//			$omode = config('mode');
//			if($request->mode=='default'){
//				if(!empty($omode['id'][$oSchool->id])){
//					$omode['url'] = array_except($omode['url'],[$oSchool->host_suffix]);
//					$omode['id'] = array_except($omode['id'],[$oSchool->id]);
//				}
//			}else{
//			$omode['id'][$oSchool->id] = $request->mode;
//			$omode['url'][$request->host_suffix] = $request->mode;
//			}
//			fwrite($fp, json_encode($omode));
//			fclose($fp);
			return back()->withInput()->withErrors(['msg' => '修改成功',]);
        } else {
            $msg = ["msg"=> ["参数错误，非法操作"], ];
            return back()->withInput()->withErrors($msg);
        }
    }

    /**
     * Remove the specified resource from storage.
     * 目前没有删除功能
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    // public function destroy($id)
    // {
    //     if(!$id) {
    //         $msg = [
    //           "custom-msg"=> ["参数错误，非法操作"],
    //       ];
    //       return response()->json($msg)->setStatusCode(422);
    //     } else {
    //         School::find($id)->delete();
    //         return response()->json(null);
    //     }
    // }

    public function plugin($id , PluginManager $plugins){

        $installed = $plugins->getEnabledPlugins();

        $enbled = array();
        Option::where('sid',$id)->where('option_name','plugins_enabled')->get()->each(function ($enble) use(&$enbled){
            $arr = array();
            $arr['created_at'] = $enble->created_at;
            $arr['updated_at'] = $enble->updated_at;
            $arr['end_time'] = $enble->end_time;
            $enbled[$enble->option_value] = $arr;
        });

        return view('default.school.school.plugin',compact('installed','id','enbled'));
    }

    public function setPlugin($type ,$id , PluginManager $plugins){

        $name = \Request::input('name');
        $end_time = \Request::input('end_time') ? strtotime(\Request::input('end_time')) : 0  ;

        $plugin = plugin($name);
        $key = "pluginEnabled:{$id}";
        if($plugin){
            switch ($type){
                case 'enabled';
                    $plugins->userEnable($name , $id , $end_time);

                    if(Redis::exists($key)){
                        Redis::del($key);
                    }
                    return back()->with('开启成功');
                    break;
                
                case 'disabled';
                    $plugins->userDisable($name,$id);
                    if(Redis::exists($key) && Redis::hexists($key,$name)){
                        Redis::hdel($key,$name);
                    }
                    return back()->with('禁用成功');
                    break;
                default:

            }
        }
        return back()->withInput()->withErrors('找不到对应模块');
    }

    public function config(Request $request,$name, $id){
        
        if($request->all()){
            
            if($request->config){

                $obj = Option::where('sid',$id)->where('option_name',$name)->first();
                if($obj){
                    $obj->option_value = $request->config;
                    $obj->save();
                }else{
                    $obj = new Option();
                    $obj->sid = $id;
                    $obj->option_name = $name;
                    $obj->option_value = $request->config;
                    $obj->save();
                }
                return redirect()->back()->with(['msg' => '配置成功']);
            }
        }else{
            $plugin = plugin($name);

            if ($plugin && $plugin->isEnabled() && $plugin->hasConfigView()) {
                $data = Option::where('sid',$id)->where('option_name',$name)->first();
                return $plugin->getConfigView($data);
            } else {
                abort(404);
            }
        }
    }

}
