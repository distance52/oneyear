<?php

namespace App\Http\Controllers\Teacher;

use Illuminate\Routing\UrlGenerator;
use App\Models\Group;
use App\Models\SquadStruct;
use App\Models\Teaching\PlanStruct;
use Illuminate\Http\Request;
use App\Http\Requests;
use Storage;
use Excel;
use App\Models\School;
use App\Models\User;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\Squad;
use App\Models\GroupStudent;
class StudentController extends BaseController
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index($squad_id)
    {
//
        // 获取教学方案数据

        $oSquad = Squad::where(['id'=>$squad_id,'teacher_id'=>$this->teacher_id])->with('teacher')->first();
        if(!$oSquad){
            return redirect('error')->with(['msg'=>'班级不存在或班级不属于当前老师', 'href'=>app(UrlGenerator::class)->previous()]);
        }
        $aSearch = [];
        $email=$name= $sno=$where='';
        \Request::has('name') &&  $aSearch['name']=$name = \Request::input('name');
        \Request::has('sno') &&  $aSearch['sno']=$sno = \Request::input('sno');
        \Request::has('email') &&  $aSearch['email']=$email = \Request::input('email');
        //查询关联学生的id
        $relation = \DB::table('squad_structs')
            ->where(['squad_id'=>$squad_id,'type'=>1])
            ->get();
        foreach ($relation as $value) {
            $stu_id[] = $value->struct_id;
        }
        //查询关联学生的id
        if(!$relation->isEmpty()){
            // $user_ids=User::where('email','like','%'.$email.'%')->pluck('id');
            // $oObjs->whereIn("user_id",$user_ids);
            $oObjs=Student::whereIn('id', $stu_id);
            if($name!=''){
                if(!$relation->isEmpty()){
                    $oObjs=Student::whereIn('id', $stu_id)->where("name","like",'%'.$name.'%');
                }else{
                    $oObjs->where("name","like",'%'.$name.'%');
                }
            }
            if($sno!=''){
                if(!$relation->isEmpty()){
                    $oObjs=Student::whereIn('id', $stu_id)->where("sno",$sno);
                }else{
                    $oObjs->where("sno",$sno);
                }
            }
            if($email!=''){
                $user_ids=User::where('email','like','%'.$email.'%')->pluck('id');
                if(!$relation->isEmpty()){
                    $oObjs=Student::whereIn('id', $stu_id)->whereIn("user_id",$user_ids);
                }else{
                    $oObjs->whereIn("user_id",$user_ids);
                }
            }
            $oObjs=$oObjs->orderBy('id','desc')->with('user');
            $num['a'] = $oObjs->count();
            $oObjs=$oObjs->paginate(20);
            $num['b'] = $oObjs->count();


            //比较复杂的获取用户专题组名称以及项目组名称的方式
            $xmz_group = Group::where('squad_id',$squad_id)->pluck('id')->toArray();
            $stu_id_arr=array_column($oObjs->toArray()['data'], 'id');

            $xmz_arr=GroupStudent::whereIn('student_id',$stu_id_arr)->where(['type'=>0])->whereIn('group_id',$xmz_group)->with('group')
//                ->with(['group' => function ($query) use ($squad_id){
//                $query->where('squad_id', $squad_id);
//            }])
                ->get();

            $ztz_arr=GroupStudent::whereIn('student_id',$stu_id_arr)->where(['type'=>1])->whereIn('group_id',$xmz_group)->with('group')
//                ->with(['group' => function ($query) use ($squad_id){
//                $query->where('squad_id', $squad_id);
//            }])
                ->get();
            $xmzData=array();
            $ztzData=array();

            if(!$xmz_arr->isEmpty()){
                foreach($xmz_arr as $val){
//                    isset($xmzData[$val->student_id])?$xmzData[$val->student_id]=$val->group->name:'';
                    $xmzData[$val->student_id] =  isset($val->group) ? $val->group->name : '';
                }
            }

            if(!$ztz_arr->isEmpty()) {
                foreach ($ztz_arr as $val) {
                    $ztzData[$val->student_id] = isset($val->group->name)? $val->group->name : '';
                }
            }

            if(!$oObjs->isEmpty()) {
                foreach ($oObjs as &$val) {
                    $xmz = isset($xmzData[$val->id]) ? $xmzData[$val->id] : '';
                    $ztz = isset($ztzData[$val->id]) ? $ztzData[$val->id] : '';
                    $val->xmz = $xmz;
                    $val->ztz = $ztz;
                }
            }
        }else{
            $oObjs ='have';
        }
        if (view()->exists(session('mode').'.teacherplat.student.list')){
            return View(session('mode').'.teacherplat.student.list', compact('oObjs','squad_id','aSearch','num'));
        }else{
            return View('default.teacherplat.student.list', compact('oObjs','squad_id','aSearch','num'));
        }
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create($squad_id)
    {
        $squad_name=Squad::where('id',$squad_id)->value('name');
        if (view()->exists(session('mode').'.teacherplat.student.create')){
            return View(session('mode').'.teacherplat.student.create', compact('squad_id','squad_name'));
        }else{
            return View('default.teacherplat.student.create', compact('squad_id','squad_name'));
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request,$squad_id)
    {
        $oUser = new User();
        $sno=$request->input('sno');
        $sno_regix='/^[0-9]{6,}$/';//学号只能纯数字并大于6位
        if($sno=='' || !preg_match($sno_regix,$sno)){
            return back()->withInput()->withErrors([ 'msg' => '学号不小于6位不能为空',]);
        }
        $suffix=School::where('id',$this->school_id)->first(['email_suffix','host_suffix']);
        if($suffix->email_suffix==''){
            return back()->withInput()->withErrors([ 'msg' => '该学校的登录邮箱后缀还未设置',]);
        }
        if($suffix->host_suffix==''){
            return back()->withInput()->withErrors([ 'msg' => '该学校的二级域名前缀还未设置',]);
        }

        //判断user表有没有这个
        $login_email=$sno.'@'.$suffix->email_suffix;
        $userhave  = User::where('email',$login_email)->first();
        if (!empty($userhave)) {
            //判断不用写进user表，直接关联的情况下
            //查询students表id
            $stuhave  = Student::where('user_id',$userhave->id)->first();
            $studentid = Student::where('user_id',$userhave->id)->first();
            $relation = SquadStruct::where(['squad_id'=>$squad_id,'struct_id'=>$studentid->id,'type'=>1])->first();
            if(!empty($relation)){
                return redirect('/teachone/student/'.$squad_id)->withErrors([ 'msg' => "该学生已经在本班级",]);
            }
            $data = ['squad_id'=>$squad_id,'struct_id'=>$studentid->id,'type'=>1];
            $m = \DB::table("squad_structs")->insertGetId($data);//返回自增id
            if($m){
                return redirect('/teachone/student/'.$squad_id)->withErrors([ 'msg' => "该学号已经存在！把学号($userhave->email),同步加到了本班。",]);
            }else{
                return back()->withInput()->withErrors([ 'msg' => '创建用户失败',]);
            }

        }else{
            $stu=Student::where(['sno'=>$sno,'school_id'=>$this->school_id])->first();
            //判断用写进user表
            if(!empty($stu)){
                return back()->withInput()->withErrors([ 'msg' => '同一所学校的学号不能重复',]);
            }
            $login_email=$sno.'@'.$suffix->email_suffix;
            $data=array(
                'name'=>$request->input('name'),
                'username'=>$suffix->host_suffix.$sno,//用户名为学校前缀+学号
                'mobile'=>'',
                'email'=>$login_email,
                'password'=>$request->input('password', '123456'),
                'plat'=>3,
                'school_id'=>$this->school_id
            );

            $oUserMobile=$oUser->where('mobile',$request->input('mobile'))->pluck('mobile')->toArray();
            if(!empty($oUserMobile)){
                return back()->withInput()->withErrors([ 'msg' => '手机号已存在']);
            }

            $newUser=$oUser->createUser($data);
            //未通过验证或创建失败直接报错
            if(!$newUser['status']){
                return back()->withInput()->withErrors([ 'msg' => $newUser['info'],]);
            }
            $newUser=$newUser['data'];
            $time = date("Y-m-d H:i:s",time());
            if($newUser){
                $data=array(
                    'school_id'=>$this->school_id,
                    'squad_id'=>$squad_id, //班级id
                    'sno'=>$sno,
                    'user_id'=>$newUser->id,
                    'name'=>$request->input('name',''),
                    'academy'=>$request->input('academy',0),
                    'dept'=>$request->input('dept',0),
                    'major'=>$request->input('major',0),
                    'year'=>$request->input('year',0),
                    'qq'=>$request->input('qq',''),
                    'phone'=>$request->input('phone',''),
                    'desc'=>'',
                    'created_at'=>$time,
                    'updated_at'=>$time,
                );
                $id = Student::createStudent($data);
//                $stuid = \DB::table("students")->insertGetId($data);//写进学生表
                if($id){
                    $data1 = ['squad_id'=>$squad_id,'struct_id'=>$id,'type'=>1,'created_at'=>$time, 'updated_at'=>$time];
                    $structid = \DB::table("squad_structs")->insertGetId($data1);//返回自增id
                    if($structid){
                        return redirect('/teachone/student/'.$squad_id)->withErrors([ 'msg' => '添加成功',]);
                    }else{
                        return back()->withInput()->withErrors([ 'msg' => '抱歉，创建用户失败，请联系网站管理员',]);
                    }
                }else{
                    return back()->withInput()->withErrors([ 'msg' => '抱歉，创建用户失败，请联系网站管理员',]);
                }
            }else{
                return back()->withInput()->withErrors([ 'msg' => '创建用户失败，请稍后再试',]);
            }
        }

    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($squad_id,$id)
    {
        if($id){
            $oObj = User::where(['id'=>$id,'plat'=>3])->first();
            if($oObj){
                $student=Student::where(['user_id'=>$id])->first();
                $squad_name=Squad::where('id',$squad_id)->value('name');
                if (view()->exists(session('mode').'.teacherplat.student.show')){
                    return View(session('mode').'.teacherplat.student.show', compact('oObj','squad_id','squad_name','student'));
                }else{
                    return View('default.teacherplat.student.show', compact('oObj','squad_id','squad_name','student'));
                }
            }else {
                return back()->withInput()->withErrors(['msg' => '学生不存在',]);
            }
        }
        else{
            return back()->withInput()->withErrors(['msg' => '参数缺失',]);
        }
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($squad_id,$id)
    {
        if($id){
            $oObj = User::where(['id'=>$id,'plat'=>3])->first();
            if($oObj){
                $student=Student::where(['user_id'=>$id])->first();
                $squad_name=Squad::where('id',$squad_id)->value('name');
                if ($squad_id != $student->squad_id){
                    $msg  = '该学生不是本班级添加的，不能修改资料';
                }else{
                    $msg = '';
                }
                if (view()->exists(session('mode').'.teacherplat.student.edit')){
                    return View(session('mode').'.teacherplat.student.edit', compact('oObj','squad_id','squad_name','student','msg'));
                }else{
                    return View('default.teacherplat.student.edit', compact('oObj','squad_id','squad_name','student','msg'));
                }
            }
            else {
                return back()->withInput()->withErrors(['msg' => '学生不存在',]);
            }
        }
        else{
            return back()->withInput()->withErrors(['msg' => '参数缺失',]);
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request,$squad_id,$id)
    {
        if(!$id){
            return back()->withErrors([ 'msg' => '参数缺失',]);
        }
        $oUser = User::where(['id'=>$id,'plat'=>3])->first();
        if($oUser) {
            $oUser->name = $request->input('name', '');
            if($request->input('password')!=''){
                // $oUser->password = $request->input('password', '123456');
                $oUser->password = bcrypt($request->input('password', '123456'));
            }
            $oUser->save();
            if($oUser->id){
                $oStudent=Student::where(['user_id'=>$oUser->id])->first();
                $data=array(
                    'school_id'=>$this->school_id,
                    'user_id'=>$oUser->id,
                    'squad_id'=>$squad_id,
                    'name'=>$request->input('name',''),
                    'academy'=>$request->input('academy',0),
                    'dept'=>$request->input('dept',0),
                    'major'=>$request->input('major',0),
                    'year'=>$request->input('year',0),
                    'qq'=>$request->input('qq',''),
                    'phone'=>$request->input('phone',''),
                    'desc'=>'',
                );
                empty($oStudent) || $data['id']=$oStudent->id;//如果找到则更新
                $result=Student::createStudent($data);
                if(!$result){
                    return back()->withErrors([ 'msg' => '更新失败',]);
                }
                return redirect('/teachone/student/'.$squad_id)->withErrors([
                    'msg' => '修改成功',
                ]);
            }
            else{
                return back()->withErrors([ 'msg' => '更新失败',]);
            }
        } else {
            return back()->withErrors([ 'msg' => '用户不存在',]);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($squad_id,$id)
    {
        //由于增加导入学生功能
        //对于导入学生只做移除本班级操作
        //对于添加学生，做删除学生+移除所有班级操作
        if(!$id) {
            $msg = ["msg"=> ["参数错误，非法操作"],];
            return back()->withInput()->withErrors($msg);
        } else {
            $id=explode(',',$id);
            $user_id=Student::whereIn('user_id', $id)->pluck('user_id');//过滤一次避免用户传参删除
            if ($id != $user_id) {
                $user_id = $id;
            }
            // User::whereIn('id', $user_id)->delete();
            //查询学生id
            Student::whereIn('user_id',$user_id)->get()->each(function ($student,$i)use($squad_id,$user_id){
                //判断学生所在的班级
                if($student->squad_id == $squad_id){
                    \DB::table('students')
                        ->where(['user_id'=>$user_id[$i]])
                        ->update(array('squad_id' => 0));
                    //解除关系表
                    \DB::table('squad_structs')
                        ->where(['squad_id'=>$squad_id,'struct_id'=>$student->id,'type'=>1])
                        ->delete();
                }else{
                    \DB::table('squad_structs')
                        ->where(['squad_id'=>$squad_id,'struct_id'=>$student->id,'type'=>1])
                        ->delete();
                }
            });
//            // Student::whereIn('user_id',$user_id)->delete();//删除学生表
            return back();
        }
    }

    public function importSchoolStudent($squad_id){
        $teacher_id =  Squad::whereId($squad_id)->value('teacher_id');
        if($teacher_id != $this->teacher_id){
            exit('你不是当前班级教室');
        }
        $aSearch = [];
        \Request::has('name') && \Request::input('name') && $aSearch['name'] = \Request::input('name');
        $oStudent = Student::where('school_id',$this->school_id)->with('user');
        if($aSearch) {
            if(isset($aSearch['name'])) {
                $oStudent = $oStudent->where(function ($query) use ($aSearch){
                    $query->orwhere("sno", 'like', '%'.$aSearch['name'].'%')
                        ->orwhere("name", 'like', '%'.$aSearch['name'].'%')
                        ->orWhereHas('user',function ($subquery)use($aSearch){
                            $subquery->where("email", 'like', '%'.$aSearch['name'].'%');
                        })
                        ->orWhereHas('user',function ($subquery)use($aSearch){
                            $subquery->where("mobile", 'like', '%'.$aSearch['name'].'%');
                        });
                });
            }
        }
        $oStudent = $oStudent->paginate(10);
        return View('default.teacherplat.student.import_one', compact('squad_id','oStudent','aSearch'));
    }

    public function imports(Request $request){

        $ids = $request->input('ids');
        $squad_id = $request->input('squad_id');
        foreach ($ids as $v){
            //导入学生时为了防止重复导入，先验证班级是否有这个学生
            $id =  SquadStruct::where('struct_id',$v)->where('squad_id',$squad_id)->where('type',1)->value('id');
            if (!$id){
                $squadStruct = new SquadStruct;
                $squadStruct->squad_id = $squad_id;
                $squadStruct->struct_id = $v;
                $squadStruct->type = 1;
                $squadStruct->save();
            }
        }
        return response()->json('ok');
    }

    public function importStudent($squad_id){
        $filePath='/other/import_template/student_import_example.xls';//模板文件地址
        if (view()->exists(session('mode').'.teacherplat.student.import')){
            return View(session('mode').'.teacherplat.student.import', compact('squad_id','filePath'));
        }else{
            return View('default.teacherplat.student.import', compact('squad_id','filePath'));
        }
    }

    public function import(Request $request,$squad_id){
        header("Content-type: text/html; charset=utf-8");
        if ($request->hasFile('excel')) {
            if ($request->file('excel')->isValid()){
                $file = $request->file('excel');
                //$mime = $request->file('excel')->getMimeType();
                //var_dump($mime);
                $extension=$file->getClientOriginalExtension();
                if(!in_array($extension,array('xls','xlsx'))){
                    return back()->withInput()->withErrors(['msg' => '只能上传xls及xlx文件格式的文件',]);
                }
                $file_name = time().str_random(6).'.'.$extension;
                Storage::disk('local')->put($file_name, file_get_contents($file->getRealPath()));
                if(Storage::disk('local')->exists($file_name)==false) {
                    return back()->withInput()->withErrors(['msg' => '文件上传失败',]);
                }
            } else {
                return back()->withInput()->withErrors(['msg' => '文件上传失败',]);
            }
        }
        else{
            return back()->withInput()->withErrors(['msg' => '请先选择excel文件',]);
        }
        //$file_name='1460026524GOUqFP.xlsx';
        $filePath=storage_path('app/'.$file_name);
        $suffix=School::where('id',$this->school_id)->first(['email_suffix','host_suffix']);
        if($suffix->email_suffix==''){
            return back()->withErrors([ 'msg' => '该学校的登录邮箱后缀还未设置',]);
        }
        if($suffix->host_suffix==''){
            return back()->withErrors([ 'msg' => '该学校的二级域名前缀还未设置',]);
        }
        //让用户导入会有个问题，会产生很多垃圾数据到用户表
        $my_error='';
        $resultMsg = [] ;
        Excel::load($filePath, function($reader) use($squad_id , $suffix , &$resultMsg){
            //$reader->dd();
            //获取excel的第几张表
            $reader = $reader->getSheet(0); //获取excel的第几张表
            $data = $reader->toArray();
            unset($data[0],$data[1]);//去掉第一行第二行
            if (empty($data)) {
                $my_error='未解析到有效数据，请仔细校对表格文件!';
                die('<script type="text/javascript">alert("'.$my_error.'");history.back();</script>');
            }
            $userModel = new User();

            foreach($data as $k => $val){
                $i = $k+1;
                $stu_id=(string) $val[1];
                $name = $val[0];
                $mobile = (string) $val[6];
                if($stu_id=='' || strlen($stu_id)<6){

                    $my_error='第'.$i.'行学号为空或小于6位，请修改正确后重试。';
                    $resultMsg[] = array('id'=>$i , 'name' => $name , 'sno' => $stu_id , 'mobile' => $mobile,'msg' => $my_error);
                    continue;
//                    die('<script type="text/javascript">alert("'.$my_error.'");history.back();</script>');
//                    break;
                }
                $password=substr($stu_id, -6, 6);  //取学号后六位
                $year=(string)$val[2];
                $phone=(string) $val[6];
                $login_email=$stu_id.'@'.$suffix->email_suffix;
                $user_name=$suffix->host_suffix.$stu_id;//用户名为学校前缀+学号
                $academy=(string) $val[3];
                $dept=(string) $val[4];
                $major=(string) $val[5];


                $user=User::where('email',$login_email)->first();

                //判断用不用写进user表,首先判断数据是否存在用户表
                if(!empty($user)){
                    //学生表已经存在，写进关系表，判断学生表是否存在
                    //修改的--------------------------------------------
                    $studentid = Student::where('user_id',$user->id)->first();
                    if(empty($studentid)){
                        $user_id = $user->id;
                        $time = date("Y-m-d H:i:s",time());
                        $data_student = array(
                            'school_id'=>$this->school_id,
                            'sno'=>$stu_id,//学号
                            'user_id'=>$user_id,
                            'name'=>$name,
                            'academy'=>$academy,
                            'dept'=>$dept,
                            'major'=>$major,
                            'year'=>$year,
                            'phone'=>$phone,
                            'created_at'=>$time,
                            'updated_at'=>$time,
                        );

                        $stuid = \DB::table("students")->insertGetId($data_student);//写进学生表
                        $datas = array('squad_id'=>$squad_id,'struct_id'=>$stuid,'type'=>1);
                        $m = \DB::table("squad_structs")->insertGetId($datas);//返回自增id

                        if($m<0){
                            $my_error='第'.$i.'行保存学生关系失败，请稍后重试。';
                            $resultMsg[] = array('id'=>$i , 'name' => $name , 'sno' => $stu_id , 'mobile' => $mobile,'msg' => $my_error);
                            continue;
//                            die('<script type="text/javascript">alert("'.$my_error.'");history.back();</script>');
//                            break;
                        }
                    }else{
                        $relation = \DB::table('squad_structs')
                            ->where(['squad_id'=>$squad_id,'struct_id'=>$studentid->id,'type'=>1]) //服务器这行报的错误
                            ->first();
                        if (empty($relation)){
                            $datas = array('squad_id'=>$squad_id,'struct_id'=>$studentid->id,'type'=>1);
                            \DB::table("squad_structs")->insertGetId($datas);//返回自增id
                        }
                    }
                    //------------------------修改--------------------------

                    if(isset($relation) && !empty($relation)){
                        $my_error='第'.$i.'行该学生存在于本班级。';
                        $resultMsg[] = array('id'=>$i , 'name' => $name , 'sno' => $stu_id , 'mobile' => $mobile,'msg' => $my_error);
                        continue;
//                        die('<script type="text/javascript">alert("'.$my_error.'");history.back();</script>');
//                        break;
                    }


                }else{
                    //在学生表创建学生信息
                    $stu=Student::where(['sno'=>$stu_id,'school_id'=>$this->school_id])->first();
                    if(!empty($stu)){
                        $my_error= '第'.$i.'行学号：'.$stu->sno.'重复,同一个学校的学号必须唯一';
                        $resultMsg[] = array('id'=>$i , 'name' => $name , 'sno' => $stu_id , 'mobile' => $mobile,'msg' => $my_error);
                        continue;
//                        die('<script type="text/javascript">alert("'.$my_error.'");history.back();</script>');
//                        break;
                    }
                    //--------------------修改--------------------
//                    $data_user = array('name'=>$name,'username'=>$user_name,'email'=>$login_email,'password'=>$password,'mobile'=>$mobile,'school_id'=>$this->school_id,'plat'=>3,);
//                    $uid = $userModel->insertGetId($data_user);
                    //--------------------修改--------------------
                    //创建用户
                    $result=$userModel->createUser(array(
                        'name'=>$name,
                        'username'=>$user_name,
                        'email'=>$login_email,
                        'password'=>$password,
                        'mobile'=>$mobile,
                        'school_id'=>$this->school_id,
                        'plat'=>3,
                    ));

                    if(!$result['status']){
                        $my_error = "第{$i}行数据导入出现错误，可能原因：{$result['info']}";
                        $resultMsg[] = array('id'=>$i , 'name' => $name , 'sno' => $stu_id , 'mobile' => $mobile,'msg' => $my_error);
                        continue;
//                        die("<script type=\"text/javascript\">alert(\"数据导入出现错误，可能原因：{$result['info']}\");history.back();</script>");
                    }
                    $uid = $result['data']->id;
                    if(empty($uid)){
                        $my_error='第'.$i.'行学生插入成为添加用户失败，请稍后重试。';
                        $resultMsg[] = array('id'=>$i , 'name' => $name , 'sno' => $stu_id , 'mobile' => $mobile,'msg' => $my_error);
                        continue;
//                        die('<script type="text/javascript">alert("'.$my_error.'");history.back();</script>');
                    }
                    //创建学生
                    $user_id=$uid;
                    $time = date("Y-m-d H:i:s",time());
                    $data=array(
                        'school_id'=>$this->school_id,
                        'squad_id'=>$squad_id,
                        'sno'=>$stu_id,//学号
                        'user_id'=>$user_id,
                        'name'=>$name,
                        'academy'=>$academy,
                        'dept'=>$dept,
                        'major'=>$major,
                        'year'=>$year,
                        'phone'=>$phone,
                        'created_at'=>$time,
                        'updated_at'=>$time,
                    );
                    $stuid = \DB::table("students")->insertGetId($data);//写进学生表
                    if($stuid>0){
                        $data1 = ['squad_id'=>$squad_id,'struct_id'=>$stuid,'type'=>1,'created_at'=>$time, 'updated_at'=>$time];
                        $structid = \DB::table("squad_structs")->insertGetId($data1);//返回自增id
                        if($structid<0){
                            $my_error='第'.$i.'行'.'写进关系表失败。';
                            $resultMsg[] = array('id'=>$i , 'name' => $name , 'sno' => $stu_id , 'mobile' => $mobile,'msg' => $my_error);
                            continue;
//                            die('<script type="text/javascript">alert("'.$my_error.'");history.back();</script>');
                        }
                    }else{
                        $my_error='第'.$i.'行'.'写进学生表失败。';
                        $resultMsg[] = array('id'=>$i , 'name' => $name , 'sno' => $stu_id , 'mobile' => $mobile,'msg' => $my_error);
                        continue;
//                            die('<script type="text/javascript">alert("'.$my_error.'");history.back();</script>');
                    }
                }
//                $i++;
                /*
                $userarr[]=array(
                    'name'=>$name,
                    'email'=>$stu_id.'@'.$email_suffix,
                    'password'=>bcrypt($password),
                    'school_id'=>$this->school_id,
                    'plat'=>3,
                    );
                $stuarr[]=array(
                    'school_id'=>$this->school_id,
                    'squad_id'=>$squad_id,
                    'user_id'=>0,
                    'name'=>$name,
                    'year'=>$year,
                    'phone'=>$phone,
                );
                */
            }

            /*
            //批量入库
            for($i=0;$i<count($userarr);$i++){
                $user=$userarr[$i];
                $user_id=User::insertGetId($user);
                //要删掉
                $login_email=$user['email'];
                $user_id=User::where('email',$login_email)->take(1)->value('id');
                $stuarr[$i]['user_id']=$user_id;
                //学生表
                Student::insert($stuarr[$i]);
            }
            */
        });
        Storage::disk('local')->delete($file_name);
        if($my_error==''){
            \Session::flash('resultMsg', $resultMsg);
            return back()->withInput()->withErrors(['msg' => '批量导入操作成功']);
        }
        else{
            return back()->withInput()->withErrors(['msg' => 'err']);
        }
    }

    public function squadQrCode($id){
        $teacher_id = Squad::where('id',$id)->value('teacher_id');
        if($teacher_id != $this->teacher_id){
            return response()->json(array('status' => false ,'msg' => 'not found' ,'data' =>null));
        }

        $http = new NoticeHttp;
        $token = get_access_token();
        $url = "https://api.weixin.qq.com/cgi-bin/qrcode/create?access_token=$token";

        $qrid = 'squad-'.$id;
        $parmas = array("action_name"=>"QR_LIMIT_STR_SCENE" ,"action_info"=> array('scene'=> array('scene_str'=>$qrid) ));
        $s = $http->postRequest($url,json_encode( $parmas));
        $result = json_decode($s,true);
        // \Session::put('qr_id',$qrid);
        // \Session::save();
        if(isset($result['ticket'])){
            return response()->json(array('status'=>true , 'msg'=> 'ok' ,'data'=>array('userId'=>$qrid , 'ticket'=> $result['ticket'] ) ));
        }else{
            return response()->json(array('status'=>false , 'msg'=> 'err' ,'data'=>null ));
        }
    }


}
