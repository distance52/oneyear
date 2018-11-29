<?php

namespace App\Http\Controllers\Administrator\Member;

use App\Models\Teacher;
use App\Models\Squad;
use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\School;

class TeacherController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $aSearch = [];
        $email=$school_name=$name=$where='';
        \Request::has('email') &&  $aSearch['email']=$email = \Request::input('email');
        \Request::has('name') &&  $aSearch['name']=$name = \Request::input('name');
        \Request::has('school_name') &&  $aSearch['school_name']=$school_name = \Request::input('school_name');
        \Request::has('begintime') && $aSearch['begintime']=$begintime = \Request::input('begintime');
        \Request::has('endtime') &&  $aSearch['endtime']=$endtime = \Request::input('endtime');
        $oObjs = User::with('school')->where('plat',2);
        if($name!=''){
            $oObjs = $oObjs->where('name','like', '%'.$name.'%');
        }
        if($email!=''){
            $oObjs = $oObjs->where('email','like', '%'.$email.'%');
        }
        if($school_name!=''){
            $school_id=School::where('name','like', '%'.$school_name.'%')->pluck('id');
            if(count($school_id)){
                $oObjs = $oObjs->whereIn('school_id',$school_id);
            }
            else{
                $oObjs = $oObjs->whereIn('id',array(0));//不存在
            }
        }
        if(isset($begintime) && $begintime!=''){
            $oObjs->where("created_at",">=",$begintime);
        }
        if(isset($endtime) && $endtime!=''){
            $oObjs->where("created_at","<=",$endtime);
        }
        $oObjs = $oObjs->orderBy('id','desc');
		$num['a'] = $oObjs->count();
		$oObjs=$oObjs->paginate(12);
		$num['b'] = $oObjs->count();
        foreach($oObjs as &$val){
            $squad_name='';
            $teacher_id=Teacher::where('user_id',$val->id)->value('id');
            !empty($teacher_id) && $squad_name=Squad::where('teacher_id',$teacher_id)->value('name');
            $val->squad_name=$squad_name;
        }
		if (view()->exists(session('mode').'.users.teacher.list')){
			return View(session('mode').'.users.teacher.list', compact('oObjs','aSearch','num'));
		}else{
			return View('default.users.teacher.list', compact('oObjs','aSearch','num'));
		}
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $school_list=School::get(['id','name']);
		if (view()->exists(session('mode').'.users.teacher.create')){
			return View(session('mode').'.users.teacher.create', compact('school_list'));
		}else{
			return View('default.users.teacher.create', compact('school_list'));
		}
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $oUser = new User();
        $data=array(
            'name'=>$request->input('name'),
            'email'=>$request->input('email'),
            'mobile'=>$request->input('mobile'),
            'password'=>$request->input('password', '123456'),
            'plat'=>2,
            'school_id'=>$request->input('school_id')
        );
        $newUser=$oUser->createUser($data);
        //未通过验证或创建失败直接报错
        if(!$newUser['status']){
            return back()->withInput()->withErrors([ 'msg' => $newUser['info'],]);
        }
        $newUser=$newUser['data'];
        if($newUser){
            $data=array(
                'user_id'=>$newUser->id,
                'school_id'=>$request->input('school_id',0),
                'name'=>$request->input('name',''),
                'dept'=>$request->input('dept',''),
                'speciality'=>$request->input('speciality',''),
                'email'=>$request->input('contact_email',''),
                'qq'=>$request->input('qq',''),
                'desc'=>'',
            );
            Teacher::createTeacher($data);
            return redirect('/member/teacher')->withErrors(['msg' => '添加成功',]);
        }
        else{
            return back()->withInput()->withErrors([ 'msg' => '创建用户失败',]);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        if(!$id) {
            $msg = ["msg"=> ["参数错误，非法操作"],];
            return back()->withInput()->withErrors($msg);
        } else {
            $oObj = User::where(['id'=>$id,'plat'=>2])->first();
            if($oObj){
                $teacher=Teacher::where(['user_id'=>$id])->first();
				if (view()->exists(session('mode').'.users.teacher.show')){
					return View(session('mode').'.users.teacher.show', compact('oObj','teacher'));
				}else{
					return View('default.users.teacher.show', compact('oObj','teacher'));
				}
            }
            else{
                $msg = ["msg"=> ["用户不存在"],];
                return back()->withInput()->withErrors($msg);
            }
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
        $oObj = User::where(['id'=>$id,'plat'=>2])->first();
        if($oObj){
            $school_list=School::get(['id','name']);
            $teacher=Teacher::where(['user_id'=>$id])->first();
			if (view()->exists(session('mode').'.users.teacher.edit')){
				return View(session('mode').'.users.teacher.edit', compact('oObj','school_list','teacher'));
			}else{
				return View('default.users.teacher.edit', compact('oObj','school_list','teacher'));
			}
        }
        else{
            return back()->withInput()->withErrors(['msg' => '老师不存在',]);
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $oUser = User::where(['id'=>$id,'plat'=>2])->first();
        if($oUser) {
            $data=array();
            $data['id'] = $oUser->id;
            $data['name'] = $request->input('name', '');
            $data['email'] = $request->input('email', '');
            $data['mobile'] = $request->input('mobile', '');
            if($request->input('password')!=''){
                $data['password'] = $request->input('password', '123456');
            }
            $data['school_id'] = $request->input('school_id',0);
            $result=User::updateUser($data);
            if($result['status']==0){
                return back()->withInput()->withErrors([ 'msg' => $result['info'],]);
            }
            if($oUser->id){
                $oTeacher=Teacher::where(['user_id'=>$oUser->id])->first();
                //存在则修改，否则则更新
                if(!empty($oTeacher)){
                    $data=array(
                        'id'=>$oTeacher->id,
                        'user_id'=>$oUser->id,
                        'school_id'=>$request->input('school_id',0),
                        'name'=>$request->input('name',''),
                        'dept'=>$request->input('dept',''),
                        'speciality'=>$request->input('speciality',''),
                        'email'=>'',
                        'qq'=>$request->input('qq',''),
                        'desc'=>'',
                    );
                    Teacher::createTeacher($data);
                }
                else{
                    $data=array(
                        'user_id'=>$oUser->id,
                        'school_id'=>$request->input('school_id',0),
                        'name'=>$request->input('name',''),
                        'dept'=>$request->input('dept',''),
                        'speciality'=>$request->input('speciality',''),
                        'email'=>'',
                        'qq'=>$request->input('qq',''),
                        'desc'=>'',
                    );
                    Teacher::createTeacher($data);
                }
                return redirect('/member/teacher')->withErrors([
                    'msg' => '修改成功',
                ]);
            }
            else{
                return back()->withInput()->withErrors([ 'msg' => '更新失败',]);
            }
        } else {
            return back()->withInput()->withErrors([ 'msg' => '用户不存在',]);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        if(!$id) {
            $msg = ["msg"=> ["参数错误，非法操作"],];
            return back()->withInput()->withErrors($msg);
        } else {
            $id=explode(',',$id);
            User::whereIn('id', $id)->delete();
            Teacher::whereIn('user_id',$id)->delete();;//删除老师表
            return back();
        }
    } 
	public function teacher_score($id)
    {
		$oObj = User::where(['id'=>$id,'plat'=>2])->first();
		$teacher=Teacher::where(['user_id'=>$id])->first();
		$oTeachers = new Teacher;
		$score_num = $oTeachers->statistical_score($teacher->id);//统计得分
		$oObjs = \DB::table('teacher_log_scores')->where('user_id',$id)->orderBy('addtime','desc');
		$num['a'] = $oObjs->count();
		$oObjs=$oObjs->paginate(12);
		$num['b'] = $oObjs->count();
		$type = [1=>'批改作业',2=>'创建环节',3=>'创建模块',4=>'创建单元',5=>'创建方案'];
        if (view()->exists(session('mode').'.users.teacher.score')){
				return View(session('mode').'.users.teacher.score', compact('oObj','oObjs','score_num','num','type'));
			}else{
				return View('default.users.teacher.score', compact('oObj','oObjs','score_num','num','type'));
			}
    }
	public function updata_score()
    {
		$teachers=Teacher::get();
		$oteacher = New Teacher;
		foreach($teachers as $teacher)
		{
			$oteacher->total_score($teacher->id);
		}
        return back();
    }

    public function import(){
        return View('default.users.teacher.import', compact('oObj','oObjs','score_num','num','type'));;
    }

    public function doImport(Request $request){
        if (!$request->hasFile('file')) {
            return response()->json(array('state'=>false,'msg'=>'上传失败，请联系管理员','data'=>null));
        }

        $file = $request->file("file");
        $whiteList = array("xlsx","xls");
        $ext = $file->getClientOriginalExtension();
        if(!in_array($ext, $whiteList)) {
            return array('state'=>false,'msg'=>'请上传正确excel格式','data'=>null);
        }
        $sign_name = md5($file->getClientOriginalName()).time().'.'.$ext;
        $dir = "import_template/".date("Ymd")."/";
        $dirto = public_path($dir);
        if (!is_dir($dirto)){
            mkdir($dirto, 0777 , true);
        }

        $result = $file->move($dirto, $sign_name);
        if (!$result){
            return array('state'=>false,'msg'=>'上传失败','data'=>null);
        }
        $filePath = $dir.$sign_name ;
        $res =[];
        \Excel::load($filePath, function($reader) use( &$res ) {
            $reader = $reader->getSheet(0);
            $res = $reader->toArray();
        });
        
        $school_id = $request->input('school_id');
        $plat = $request->input('plat');
        $err = array();
        for($i = 1;$i<count($res);$i++) {
            if (empty($res[$i][0]) ) {
                continue;
            }
            //新建用户
            $oUser = new User();
            $data=array(
                'name'=> $res[$i][0],
                'email'=> $res[$i][1],
                'mobile'=> intval($res[$i][2]),
                'password'=> substr(intval($res[$i][2]), -6, 6),
                'plat'=> $plat,
                'school_id'=> $school_id
            );
            $newUser=$oUser->createUser($data);
            //未通过验证或创建失败直接报错
//            dd($newUser);
            if(!$newUser['status']){
                $err[] = array('mobile'=> intval($res[$i][2]), 'email'=> $res[$i][1] , 'name'=> $res[$i][0],'msg'=>$newUser['info']);
                continue;
//                return array('state'=>false,'msg'=>'create user error','data'=>$newUser['info']);
//                return back()->withInput()->withErrors([ 'msg' => $newUser['info'],]);
            }
            $newUser=$newUser['data'];
            if($newUser){
                $data=array(
                    'user_id'=>$newUser->id,
                    'school_id'=> $school_id,
                    'name'=> $res[$i][0],
//                    'dept'=>$request->input('dept',''),
//                    'speciality'=>$request->input('speciality',''),
//                    'email'=>$request->input('contact_email',''),
//                    'qq'=>$request->input('qq',''),
//                    'desc'=>'',
                );
                Teacher::createTeacher($data);
            }

        }
        return array('state'=>true,'msg'=>'ok','data'=>$err);
    }
}
