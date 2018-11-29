 <?php

Route::get('api/sendcode','\App\Http\Controllers\Api\SmsController@sendCode');//发送验证码,无论登陆与否都可以

Route::group(['middleware' => 'web'], function () {
    //
    Route::get('/success', function() {
      return View('default.success');
    });
    Route::get('/error', function() {
        return View('default.error');
    });
    Route::get('/error2', function() {
        return View('default.error2');
    });

    // 验证码
    Route::get('captcha/login/{tmp}', ['as'=>'captcha','uses'=>'CaptchaController@login']);
    // 登录、退出
    Route::get('login', 'Auth\AuthController@login');
    Route::post('login', 'Auth\AuthController@postLogin');
    Route::post('stulogin', 'Auth\AuthController@postStuLogin');
    Route::get('logout', 'Auth\AuthController@logout'); // 账户退出
    Route::get('logoutwechat', 'Student\MyProfileController@logoutWechat'); // 微信退出登录
    //第三方登录相关
    Route::get('login/bind/{type}', ['as'=>'login_bind','uses'=>'LoginBindController@index']);
    Route::get('login/bind/{type}/callback', ['as'=>'login_bind_callback','uses'=>'LoginBindController@callback']);
    Route::get('login/bind/{type}/choose', ['as'=>'login_bind_choose','uses'=>'LoginBindController@choose']);
    Route::get('login/bind/{type}/changetoexist', ['as'=>'login_bind_change','uses'=>'LoginBindController@changeToExist']);

    Route::post('login/bind/{type}/new', ['as'=>'login_bind_new','uses'=>'LoginBindController@new']);
    Route::post('login/bind/{type}/newset', ['as'=>'login_bind_newset','uses'=>'LoginBindController@newset']);
    Route::post('login/bind/{type}/exist', ['as'=>'login_bind_exist','uses'=>'LoginBindController@exist']);
    Route::post('login/bind/weixinmob/existbind', ['as'=>'login_bind_existbind','uses'=>'LoginBindController@existBind']);
    Route::get('login/weixinmob', ['as'=>'login_bind_weixin','uses'=>'LoginBindController@weixinIndex']);

    // 发送密码重置链接路由
    Route::get('password/email', 'Auth\PasswordController@getEmail');
    Route::post('password/email', 'Auth\PasswordController@postEmail');
    Route::get('password/forget', 'Auth\AuthController@getForget');
    Route::post('password/forget', 'Auth\AuthController@postForget');
    // 密码重置路由
    Route::get('password/reset/{token}', 'Auth\PasswordController@getReset');
    Route::post('password/reset', 'Auth\PasswordController@postReset');

    // file的预览页
    Route::get('preview/file/{sign}','Administrator\Resource\FileController@previewUrl');
    Route::get('preview/info/{sign}/{squad_id?}',['as'=>'preview_info','uses'=>'Administrator\Resource\InfoController@preview']);
    // 方案的预览
    Route::get('scan/{class}/{id}', 'Administrator\Teaching\PlanController@scan')->where(['class'=>'node|cell|module|info|question']);
    // 资源的预览，防止和file的预览重复。使用scan
    // Route::get('scan/{class}/{id}', 'Administrator\Resource\InfoController@scan')->where(['class'=>'info|question']);
    // 登录判定
    Route::group(['middleware'=>'auth'], function() {
        Route::group(['namespace' => 'Usercenter', 'prefix' => 'usercenter'], function () {
			Route::group(['middleware'=>'plan'], function() {
				Route::resource('mynotify', 'MynotifyController'); //消息中心
				Route::resource('myprofile', 'MyprofileController'); //个人资料
				
				Route::post('saveinfo', 'MyprofileController@saveExtend'); //修改个人资料
				Route::resource('notice', 'NoticeController'); //个人资料
			});
			Route::post('dochangepwd', 'MyprofileController@doChangePwd'); //修改密码提交
			Route::post('cancelbind', 'MyprofileController@cancelBind'); //解除微信绑定
        });
        //
        // 全平台通用-教学方案管理
        Route::group(['namespace' => 'Administrator\Teaching', 'prefix' => 'teaching','middleware'=>'permission'], function () {
            //
           Route::group(['middleware'=>'plan'], function() {
				Route::get('plan/console/{planStruct_id}', 'PlanController@console')->where('planStruct_id','[0-9]+');//控制台
				Route::get('plan/screen/{planStruct_id}', 'PlanController@screen')->where('planStruct_id','[0-9]+');//大屏幕
				//
                Route::get('plan/applyedit/{id}', 'PlanController@applyedit');	//申请修改
                Route::get('plan/forget/{id}', 'PlanController@forget');		//退出
                Route::get('plan/delete/{id}', 'PlanController@destroy');
				Route::get('plan/struct/{id}', 'PlanController@struct'); // 方案设置
				Route::get('plan/detail/{id}', 'PlanController@detail'); // 方案详情
				// 方案设置里面的各部分的选择器
				Route::get('lists/{class}/{type?}', 'PlanController@lists')->where(['class'=>'node|cell|module']);
				//
				Route::get('plan/all', 'PlanController@getAll'); // 全网方案
				//方案备份
				Route::get('plan/plan/{id}', 'PlanController@plan');
				Route::get('plan/info/{id}', 'PlanController@info');
				
				Route::get('plan/view/{id}', 'PlanController@getView'); // 全网方案
               Route::post('plan/ajaxEdit', 'PlanController@ajaxEdit');//ajax编辑
				Route::get('plan/trash', 'PlanController@trash');//软删除
                Route::get('plan/trash/{id}', 'PlanController@doTrash');//软删除
               Route::resource('plan', 'PlanController'); // 方案
				Route::get('plan_copy/{id}/{school_id}', 'PlanController@copyToSchool')->where('id', '[0-9]+'); // 方案拷贝
				Route::get('module/delete/{id}', 'ModuleController@destroy');
                                Route::get('module/trash', 'ModuleController@trash');//软删除
				Route::resource('module', 'ModuleController'); //模块
				Route::get('module/reduction/{id}', 'ModuleController@reduction');//还原
				Route::get('cell/delete/{id}', 'CellController@destroy');
                                Route::get('cell/trash', 'CellController@trash');//软删除
                                Route::get('cell/trash/{id}', 'CellController@doTrash');//软删除
				Route::resource('cell', 'CellController'); //单元
				Route::get('cell/reduction/{id}', 'CellController@reduction');//还原
				Route::get('node/delete/{id}', 'NodeController@destroy');
				Route::get('node/reduction/{id}', 'NodeController@reduction');//还原
				Route::get('node/trash', 'NodeController@trash');//回收站
                Route::get('node/trash/{id}', 'NodeController@doTrash');//回收站还原
				Route::resource('node', 'NodeController'); //环节

			});
        });

        // 全平台通用-资源管理
        Route::group(['namespace' => 'Administrator\Resource','prefix'=>'resource','middleware'=>'permission'], function () {
			Route::group(['middleware'=>'plan'], function() {
				Route::get('info/delete/{id}', 'InfoController@destroy');
				
                                 Route::get('info/importpicwrite', 'InfoController@importpicwrite');//ceshi fangf
                                
				Route::get("info/edit/{id}","InfoController@edit");
				Route::post("info/update","InfoController@update");

                Route::post('file/editor', 'FileController@editorPost'); // 文件
				Route::post("file/doupload","FileController@doupload");
				Route::get("file/delete/{id}","FileController@destroy");
				Route::get("file/edit/{id}","FileController@edit");
				Route::post("file/update","FileController@update");
				Route::get('exampaper/edit/{id}', 'ExampaperController@edit'); // 试卷修改
				Route::post('exampaper/update', 'ExampaperController@update'); // 试卷修改
				Route::get('exampaper/delete/{id}', 'ExampaperController@destroy'); // 试卷修改
				
				
				Route::get('wj_examp/edit/{id}', 'Wj_exampController@edit'); // 问卷修改
				Route::post('wj_examp/update', 'Wj_exampController@update'); // 问卷修改
				Route::get('wj_examp/delete/{id}', 'Wj_exampController@destroy'); // 问卷删除
				
				Route::get("question/edit/{id}","QuestionController@edit");
				
				Route::post("question/ajaxadd","QuestionController@ajaxadd");
				Route::get("question/ajaxGet/{id}","QuestionController@ajaxGet");
				Route::get("question/delete/{id}","QuestionController@destroy");
				Route::post("question/update/{id}","QuestionController@update");
				
				
				// ajax html
				Route::get('lists/info/{type?}', 'InfoController@lists');
				Route::get('lists/paper/{type?}', 'ExampaperController@lists');
				Route::get('lists/wj_examp/{type?}', 'Wj_exampController@lists');
				//
			});
            Route::get("question/getquestion","QuestionController@getquestion");
            Route::get("question/choise","QuestionController@choise");
            Route::get('info/trash', 'InfoController@trash');
            Route::get('info/trash/{id}', 'InfoController@doTrash');
            Route::resource('info', 'InfoController'); // 信息
            Route::get('file/choise', 'FileController@choise'); //
            Route::resource('file', 'FileController'); // 文件
            Route::get('exampaper/trash', 'ExampaperController@trash');
            Route::get('exampaper/trash/{id}', 'ExampaperController@doTrash');
            Route::get('exampaper/view/{id}', 'ExampaperController@view'); // 试卷查看
            Route::resource('exampaper', 'ExampaperController'); // 试卷
            Route::get('question/trash', 'QuestionController@trash');
            Route::get('question/trash/{id}', 'QuestionController@doTrash');
            Route::resource('question','QuestionController'); // 题目
            Route::get('wj_examp/view/{id}', 'Wj_exampController@view'); // 问卷查看
            Route::get('wj_examp/trash', 'Wj_exampController@trash');
            Route::get('wj_examp/trash/{id}', 'Wj_exampController@doTrash');
            Route::resource('wj_examp', 'Wj_exampController'); // 问卷
		});
		Route::group(['namespace' => 'Administrator\Other', 'prefix' => 'other'], function () {
			Route::get('help/look', 'HelpController@look'); //
			Route::get('delClient/{client_id}', 'HelpController@delClient'); //
		});
        // 系统平台
        Route::group(['domain' => 'admin.'.env('APP_SITE'), 'namespace' => 'Administrator','middleware'=>'permission'], function() {
            Route::get('/', 'HomeController@index');

            // 系统设置
            Route::group(['namespace' => 'System','prefix'=>'system'], function () { //系统设置
                Route::get('w_basic', 'BasicController@index');
                Route::get('w_oss', 'OssController@index');
                Route::get('w_msg', 'MsgController@index');
                Route::get('w_email', 'EmailController@index');
                Route::get('w_bind', 'BindController@index');
                Route::get('w_ucenter', 'UcenterController@index');
                Route::get('w_notifytemlate', 'NotifyTemplateController@index');

                Route::resource('basic', 'BasicController'); //基本设置
                Route::resource('email', 'EmailController'); //邮件设置
                Route::resource('msg', 'MsgController'); //短信设置
                Route::resource('oss', 'OssController'); //阿里云OSS设置
                Route::resource('bind', 'BindController'); //三方登录设置
                Route::resource('ucenter', 'UcenterController'); //ucenter同步设置
                
               
                Route::get('sysmanadmin/delete/{id}', 'SysAdminController@destroy');
                Route::resource('sysmanadmin', 'SysAdminController');//系统管理员管理
                
                Route::get('role/delete/{id}', 'RoleController@destroy');
                Route::resource('role', 'RoleController');
                Route::resource('role/{role_id}/permission', 'PermissionController');
                
                Route::get('menu/delete/{id}', 'MenuController@destroy');//菜单
                Route::post('menu/store', 'MenuController@store');
                Route::post('menu/update/{id}', 'MenuController@update');
                Route::get('menu/create/{id}', 'MenuController@create_child');
                Route::get('menu/edit/{id}', 'MenuController@edit');
               
                
                Route::resource('menu', 'MenuController'); //
            });

            // 学校管理
            Route::group(['namespace' => 'School','prefix'=>'school'], function () {
                Route::get('qrcode/{id}', 'SchoolController@qrcode');
                Route::resource('school', 'SchoolController'); // 学校
                Route::get('squad/delete/{id}', 'SquadController@destroy');
                Route::resource('squad', 'SquadController'); // 班级
                Route::resource('group', 'GroupController'); // 分组
                Route::resource('score', 'ScoreController'); // 分数
                Route::get('point', 'PointController@index'); // 积分管理
                Route::get('specialty/edit/{school_id}-{id}', 'SpecialtyController@edit');
                Route::put('specialty/edit/{school_id}-{id}', 'SpecialtyController@update');
                Route::get('specialty/{school_id}', 'SpecialtyController@index');
                Route::get('specialty/create/{school_id}', 'SpecialtyController@create');
                Route::post('specialty/create/{school_id}', 'SpecialtyController@store');
                Route::get('specialty/delete/{school_id}-{id}', 'SpecialtyController@destroy');
                //Route::resource('specialty/{school_id}', 'SpecialtyController'); // 专业建设
                Route::get('faq', 'OnlineQaController@index'); //在线答疑
                Route::get('faq_part', 'OnlineQaController@getFaqList'); //在线答疑列表
                Route::get('faq/replylist/{id}', 'OnlineQaController@getReplyList'); //查看一个在线答疑的回复列表
                Route::post('faq/reply', 'OnlineQaController@reply'); //回复一个在线答疑
                Route::get('faq/black/{id}', 'OnlineQaController@black'); //屏蔽一个在线答疑

                Route::get('judge', 'JudgeController@index'); //评价管理
                Route::get('judge_part', 'JudgeController@getJudgeList'); //评价管理列表
            });

            // 用户管理
            Route::group(['namespace' => 'Member','prefix'=>'member'], function () { //用户管理接口组
                Route::get('logs/{plat}', 'LogController@index');

                

                Route::get('schoolmanadmin/delete/{id}', 'SchoolAdminController@destroy');
                Route::resource('schoolmanadmin', 'SchoolAdminController');//学校管理员管理

                Route::get('teacher/delete/{id}', 'TeacherController@destroy');
				Route::get('teacher/score/{id}', 'TeacherController@teacher_score');//教师积分
                Route::get('teacher/updata_score', 'TeacherController@updata_score');//教师积分
                Route::resource('teacher', 'TeacherController');//老师管理

                Route::get('student/delete/{id}', 'StudentController@destroy');
                Route::resource('student', 'StudentController');//学生管理
                Route::get('students/importtouc', 'StudentController@importStuToUcenter');//手动同步学生信息


                Route::resource('user', 'UserController');
                
                Route::get('log/{plat}', 'LogController@index')->where('plat', '[0-9]+');
            });

            Route::group(['namespace' => 'Other', 'prefix' => 'other'], function () {
                Route::resource('tag', 'TagController'); //标签管理
				
				Route::get('help/audit/{id}', 'HelpController@audit');
                Route::get('help/delete/{id}', 'HelpController@destroy');
                Route::get('help/look', 'HelpController@look'); //
                Route::get('help/trash', 'HelpController@trash');
                Route::get('help/trash/{id}', 'HelpController@doTrash');
                Route::resource('help', 'HelpController'); //
                
                
                Route::get('notice/audit/{id}', 'NoticeController@audit');
                Route::get('notice/delete/{id}', 'NoticeController@destroy');
                Route::get('notice/trash', 'NoticeController@trash');
                Route::get('notice/trash', 'NoticeController@trash');
                Route::get('notice/trash/{id}', 'NoticeController@doTrash');
                Route::resource('notice', 'NoticeController'); //平台公告

                
                 Route::get('notify_template/delete/{id}', 'NotifyTemplateController@destroy');
                Route::resource('notify_template', 'NotifyTemplateController'); // 通知模版
                Route::get('notify/delete/{id}', 'NotifyController@destroy');
                Route::resource('notify', 'NotifyController'); // 系统消息
                
                
                
            });
        });
        // 学校平台
        Route::group(['domain' => 'school.'.env('APP_SITE')], function() {
            Route::group(['namespace'=>'School'], function() {
                Route::get('/', 'HomeController@index');
                Route::get('setting/school', 'SchoolSeetingController@index'); //学校设置
                Route::post('setting/addschooltime', 'SchoolSeetingController@addSchoolTime'); //学校时间增加
                Route::post('setting/editschooltime', 'SchoolSeetingController@editSchoolTime'); //ajax返回修改数据
                Route::post('setting/doedittime', 'SchoolSeetingController@doEditTime'); //ajax执行修改数组
                Route::post('setting/deletetime', 'SchoolSeetingController@deleteTime'); //ajax执行删除
                Route::post('setting/ischoice', 'SchoolSeetingController@isChoice'); //是否选定
                Route::get('setting/trash', 'SchoolSeetingController@trash');//软删除
                Route::get('setting/trash/{id}', 'SchoolSeetingController@doTrash');//软删除
                Route::post('setting/school', 'SchoolSeetingController@update');//删除学生

                Route::get("setting/notify/delete/{id}","NotifyController@destroy");
                Route::resource('setting/notify', 'NotifyController'); // 系统消息
				
				Route::get('setting/specialty/delete/{id}', 'SpecialityController@destroy');
                Route::resource('setting/specialty', 'SpecialityController');
               

                Route::get('squad/squad/delete/{id}', 'SquadController@destroy');
                Route::resource('squad/squad', 'SquadController'); //班级管理

				Route::get('user/teacher/score/{id}', 'TeacherController@teacher_score');//教师积分
                Route::get('user/teacher/updata_score', 'TeacherController@updata_score');//教师积分
                Route::get('user/teacher/delete/{id}', 'TeacherController@destroy');
                Route::resource('user/teacher', 'TeacherController'); //老师管理
				
                Route::get('user/student/delete/{id}', 'StudentController@destroy');
                Route::resource('user/student', 'StudentController'); //学生管理

                Route::resource('squad/group', 'GroupController'); //分组管理

                Route::resource('squad/score', 'ScoreController'); //分组管理

            });
        });
        // 老师平台
        Route::group(['domain' => 'teacher.'.env('APP_SITE')], function() {
            Route::group(['namespace'=>'Teacher'], function() {
				Route::get('/', 'HomeController@index');
                Route::get('/welcome', 'HomeController@welcome');
                Route::group(['prefix'=>'teach'], function () { //教学管理分组
                    Route::resource('squad', 'SquadController'); //班级管理
                    Route::get('faq', 'OnlineQaController@index'); //在线答疑
                    Route::get('faq_part', 'OnlineQaController@getFaqList'); //在线答疑
                    Route::get('faq/replylist/{id}', 'OnlineQaController@getReplyList'); //查看一个在线答疑的回复列表
                    Route::post('faq/reply', 'OnlineQaController@reply'); //回复一个在线答疑
                    Route::get('faq/black/{id}', 'OnlineQaController@black'); //屏蔽一个在线答疑

                    Route::get('squad/plans/{id}', 'SquadController@plans');//查看一个班级可选教学方案列表
                    Route::get('squad/teachers/{id}', 'SquadController@teachers');//查看一个班级可选授课老师列表
                    Route::get('squad/assistant/{id}', 'SquadController@assistant');//查看一个班级可选助教
                    Route::post('squad/cassistant', 'SquadController@cassistant');//选择助教
                    Route::post('squad/updateassistant/{id}', 'SquadController@updateassistant');//更新助教
                    Route::post('squad/changeplan/{id}', 'SquadController@changePlan');//保存教学方案
                    Route::post('squad/changeteacher/{id}', 'SquadController@changeTeacher');//保存老师更改
					

                    Route::get('homework/reviewing/{id}', 'HomeworkController@reviewing');//查看一个班级可选教学方案列表
                    Route::get('homework/saveteachescore', 'HomeworkController@saveteachescore');//查看一个班级可选教学方案列表
                    Route::get('homework/list', 'HomeworkController@index'); //作业管理
                    Route::get('homework/{id}', 'HomeworkController@show'); //作业
                    Route::get('evaluate/list', 'HomeworkController@getEvaluateList'); //评分管理
                    Route::resource('homework', 'HomeworkController');//查看一个班级可选教学方案列表

                    Route::get("notify/delete/{id}","NotifyController@destroy");
                    Route::resource('notify', 'NotifyController'); // 系统消息
                });
                //针对一个班级的开始上课/学生管理/分组管理/作业批改/在线答疑/成绩管理/基本设置
                Route::group(['prefix'=>'teachone'], function () {
                    Route::get('squad/beginclass/{id}', 'SquadController@beginClassV2');//开始上课测试
                    // Route::get('squad/console/{squad_id}', 'SquadController@console')->where('squad_id','[0-9]+');//控制台
                    // Route::get('squad/screen/{squad_id}', 'SquadController@screen')->where('squad_id','[0-9]+');//大屏幕
                    Route::get('squad/console/{squad_id}/{planStruct_id}', 'SquadController@console_v2')->where('squad_id','[0-9]+');//控制台
                    Route::get('squad/screen/{squad_id}/{planStruct_id}', 'SquadController@screen_v2')->where('squad_id','[0-9]+');//大屏幕
                    Route::post('bind', 'SquadController@bind');//绑定uid，clientid

                    //课堂互动ajax
					Route::get('squad/screen/getCommentList/{planStruct_id}/{squad_id}/{qa_id}', 'SquadController@getCommentList');
                    Route::get('squad/screen/history/{group_id}', 'SquadController@history');

                    // 随机点名ajax
                    Route::get('squad/screen/getRollcall/{planStruct_id}/{squad_id}/{qa_id}', 'SquadController@getRollcall');
					
					Route::get('squad/wj_result/{squad_id}/{module_id}/{node_id}', 'SquadController@wj_result'); // 问卷查看
					
					//生成二维码
                    Route::get('student_ercode/{squad_id}', 'SquadController@student_ercode');
                    Route::get('squad/screen/history/{group_id}', 'SquadController@history');
					//发起投票
					Route::get('send_vote/{squad_id}', 'SquadController@send_vote');//发起投票
					Route::get('vote/{ws_id}/{wq_id}', 'SquadController@getVote');//获取某一投票内容

                    Route::get('sign_list/{squad_id}', 'SquadController@sign_list');//签到列表
					Route::get('ajax_ercode/{squad_id}', 'SquadController@ajax_ercode');

					Route::get('roll_call/{squad_id}', 'SquadController@roll_call'); //随机点名

                    Route::get('reward/{squad_id}/{id}', 'SquadController@reward'); //奖励分值
                    Route::post('save/{squad_id}/{id}', 'SquadController@save'); //奖励分值
                    Route::get('squad/screen/notify/{id}', 'SquadController@notify');//给班级发送通知
                    Route::get('squad/screen_ajax/{squad_id}/{planStruct_id}', 'SquadController@page_ajax')->where('squad_id','[0-9]+');//分页ajax
                    
                     Route::get('squad/addcalendar', 'SquadController@addCalendar'); //教学日历添加
                    Route::post('squad/doaddcalendar', 'SquadController@doAddCalendar'); //教学日历修改 
                    Route::get('squad/editcalendar/{id}', 'SquadController@editCalendar'); //教学日历添加
                    Route::post('squad/doeditcalendar', 'SquadController@doEditCalendar'); //教学日历添加
                    
                    Route::get('squad/squadsetting/{squad_id}', 'SquadController@edit'); //班级管理

                    Route::get('faq/{squad_id}', 'OnlineQaController@index'); //在线答疑
                    Route::get('online/{squad_id}', 'OnlineQaController@online'); //class online
                    Route::get('ajax_online', 'OnlineQaController@ajaxOnline'); //class online


                  Route::get('homework/reviewing/{id}', 'HomeworkController@reviewing');//查看一个班级可选教学方案列表
                    Route::get('homework/saveteachescore', 'HomeworkController@saveteachescore');//查看一个班级可选教学方案列表
                   Route::get('homework/downLoad', 'HomeworkController@downLoad');//下载文件
                    Route::get('homework/list', 'HomeworkController@index'); //作业管理
                    Route::get('evaluate/list', 'HomeworkController@getEvaluateList'); //评分管理
                    Route::resource('homework', 'HomeworkController');//查看一个班级可选教学方案列表


                    Route::get('calendar/{squad_id}', 'SquadController@calendar'); //教学日历
                    Route::get('distribution/{squad_id}', 'SquadController@distributionScore'); //开始分配积分按钮

                    Route::get('student/{squad_id}', 'StudentController@index'); //学生管理
                    Route::get('student/delete/{squad_id}/{id}', 'StudentController@destroy');//删除学生
                    Route::get('student/create/{squad_id}', 'StudentController@create');//添加学生
                    Route::post('student/create/{squad_id}', 'StudentController@store');//保存学生
                    Route::get('student/edit/{squad_id}-{id}', 'StudentController@edit');//编辑学生
                    Route::put('student/edit/{squad_id}-{id}', 'StudentController@update');//更新学生
                    Route::get('student/show/{squad_id}-{id}', 'StudentController@show');//查看学生
                    Route::get('student/import/{squad_id}', 'StudentController@importSchoolStudent');//导入学生
                    Route::post('student/import/{squad_id}', 'StudentController@imports');//导入学生
                    Route::get('student/importstudent/{squad_id}', 'StudentController@importStudent');//导入学生
                    Route::post('student/importstudent/{squad_id}', 'StudentController@import');//导入学生
                    Route::get('group/{squad_id}', 'GroupController@index'); //分组管理
                    Route::get('group/delete/{squad_id}-{id}', 'GroupController@destroy');
                    Route::get('group/show/{squad_id}-{id}', 'GroupController@show'); //分组详情



                    Route::get('score/{squad_id}', 'ScoreController@index'); //分数管理
                    Route::get('exportscore', 'ScoreController@exportScore'); //导出成绩
                    Route::post('score/staticscore','ScoreController@beginStatics'); //统计成绩
                    Route::get('score/edit/{squad_id}', 'ScoreController@edit');//设置自定义成绩
                    Route::put('score/edit/{squad_id}', 'ScoreController@update');//保存自定义成绩
                });
            });

        });
        // 学生平台
        Route::group(['domain' => 'student.'.env('APP_SITE'),'namespace' => 'Student'], function() {
            Route::get('/', 'HomeController@index');
            Route::get('indexs', 'HomeController@indexs');
            Route::group(['prefix'=>'course'], function () { //课程分组
                Route::get('index/{squad_id}','CourseController@getIndex');
                Route::get('study/{squad_id}/{planStruct_id}','CourseController@getStudy'); // 在线学习，默认跳转到正在学习的环节页
                Route::get('study/scan',['as'=>'student_scan','uses'=>"CourseController@Scan"]); // 扫一扫
                
                Route::post('study/{squad_id}/{planStruct_id}','CourseController@postStudy'); // 在线学习，默认跳转到正在学习的环节页
                Route::post('bind', 'CourseController@bind');//绑定uid，clientid

                Route::get('score/{id}/{user}/{userId}','CourseController@Del'); // 在线课堂互动，删除互动提交不满意的答案
            });
            Route::group(['prefix'=>'find'], function () { //课程分组
                Route::get('index','ExplorerController@index'); // 在线学习，默认跳转到正在学习的环节页
            });

            // 个人中心
            Route::group(['prefix'=>'my'], function () { //教学管理分组
                Route::get('faq', 'OnlineQaController@index');//某个环节的答疑列表
                Route::get('faq/replyList/{id}', 'OnlineQaController@replyList');//某个答疑的回复列表
                Route::post('faq/ask', 'OnlineQaController@ask');//提问
                Route::post('faq/reply/{id}', 'OnlineQaController@reply');//回复某个答疑

                Route::get('profile/{squad_id}', 'MyProfileController@index');//个人中心首页
                Route::get('profiles', 'MyProfileController@indexs');//个人中心首页
                Route::get('profileedit', 'MyProfileController@edit');//修改个人资料
                Route::post('profiledoedit', 'MyProfileController@editPost');//修改个人资料
                Route::get('preview/{squad_id}', 'MyProfileController@preview');// 我的预习
                Route::get('previews', 'MyProfileController@previews');

                Route::get('signs/{squad_id}', 'MyProfileController@signs');// 我的签到
                //投票
                 Route::get('votes/{squad_id}', 'VotesController@index');// 我的投票
                 Route::get("votes/view/{wj_id}/{squad_id}",['as'=>'student_votes','uses'=>"VotesController@view"]);	//问卷内容
                 Route::post("votes/handin/{id}","VotesController@handin");	//提交问卷

                Route::get('faq/{squad_id}', 'OnlineQaController@myFaq');//在线答疑
                Route::get('faqjson', 'OnlineQaController@myfaqJson');//在线答疑json列表
                Route::get('faq/show', 'OnlineQaController@faqView');//在线答疑查看
                Route::post('faq/ask', 'OnlineQaController@ask');//提问某个环节
                Route::post('faq/reply/{id}', 'OnlineQaController@reply');//回答某个答疑
                //作业
                Route::get("exam/{squad_id?}","ExamController@index");
                Route::get("exam/do/{id}","ExamController@exam");
                Route::get("exam/do/{id}/{num}",['as'=>'student_examdo','uses'=>"ExamController@exam"]);
                Route::get("exam/view/{id}","ExamController@view");
                Route::post("exam/handin/{id}","ExamController@handin");

                Route::get("score/{squad_id}", ['as'=>'student_score','uses'=>"ScoreController@index"]);

                Route::get('jduge/{squad_id}', 'JudgeController@index');//评价页面
                Route::get('jdugejson', 'JudgeController@jdugeJson');//评价json列表
                Route::get('dojduge', 'JudgeController@doJudge');//提交评价

                Route::get('changepwd', 'MyProfileController@changePwd');
                Route::post('dochangepwd', 'MyProfileController@doChangePwd');
				Route::get('changemobile', 'MyProfileController@changeMobile');
                Route::get('cancelbind', 'MyProfileController@cancelBind');

				Route::get('group/pingfen/{squad_id}', 'GroupController@pingfen');//分配积分
                Route::get('group/{squad_id}', 'GroupController@index');//分组管理
                Route::post('group/create', 'GroupController@create');//创建项目组
                Route::get('group/score', 'GroupController@score');//显示积分
                
                Route::post('group/distribute', 'GroupController@distribute');//分配积分处理
                Route::post('group/join','GroupController@join');//加入分组

                Route::get('notify', 'MynotifyController@index');//消息列表
                Route::get('notifyjson', 'MynotifyController@notifyJson');//消息json列表
                Route::get('notify/show', 'MynotifyController@notifyView');//消息查看
				
                Route::get('message', 'MessageController@index');//消息列表
                Route::get('message/reply/{id}/{type}', 'MessageController@lists');//消息回复
                Route::get('message/scan/{id}', 'MessageController@scan');//消息回复
				
				//签到
                Route::get('sign/{code}', ['as'=>'student_sign','uses'=>'MyProfileController@sign']);
            });
        });
    });
	Route::group(['namespace'=>'help'], function() {
		Route::get('help/list', 'HelpController@lists');
		Route::post('help/save', 'HelpController@save');
		Route::resource('help', 'HelpController');
		
    });

    //导师
    //企业
    Route::group(['namespace' => 'Company'], function () {

        Route::get('/company', 'IndexController@index'); //
    });

    // 官网首页
    Route::get('/', 'HomeController@index');
    //仅供测试
//    Route::get('test', ['as'=>'test','uses'=>'TestController@test']);
//    Route::get('testpage', ['as'=>'testpage','uses'=>'TestController@testpage']);
//    Route::get('testsend', ['as'=>'testsend','uses'=>'TestController@testsend']);
//    Route::post('testpost', ['as'=>'testpost','uses'=>'TestController@testpost']);
//    Route::get('testnotify', ['as'=>'testnotify','uses'=>'TestController@testnotify']);
});
Route::any('/wechatapi','WechatApi\IndexController@index');
Route::group(['middleware' => 'wechat'], function () {
    Route::get('wechat_test', ['as'=>'wechat_test','uses'=>'WechatTestController@index']);
});


Route::group(['middleware' => 'api2','namespace' => 'Api'], function () {
    Route::group(['prefix' => 'api'], function () {
        // 验证邮箱
        Route::post('check/email', '\App\Http\Controllers\Auth\AuthController@postEmailCheck');
        // 验证手机
        Route::post('check/mobile', '\App\Http\Controllers\Auth\AuthController@postMobileCheck');
    });
});
/***
  *
  * 这里的路由组按照需求来划分，目的是为了以后方便设置访问权限，和查找接口。
  * 功能在model层做区分。
  */
Route::group(['middleware' => 'api','namespace' => 'Api'], function () {
    // 获取该账户下所有的学生和所有的账户
    // Route::group(['prefix'=>'account'], function () {
    //     Route::get('students', 'AccountController@students');
    //     Route::get('users', 'AccountController@users');
    // });
    //
    Route::group(['prefix' => 'api'], function () {

        Route::any('speciallst/{school_id}', 'SpecialtyController@getListByPid'); // 学校的专业接口
        //搜索接口
        Route::get('tag/lists', 'OtherController@getTagLists');
        Route::get('{model}/lists', 'OtherController@getLists');
        //
        Route::post('cell/create','Administrator\CellController@create'); //单元设置
        Route::post('node/create','Administrator\NodeController@create'); //单元设置
        Route::post('module/create','Administrator\ModuleController@create'); //单元设置
        // 方案设置，修改结构的接口
        Route::post('plan/{id}/struct', 'PlanController@postStruct');
        Route::get('getplan/{id}', 'PlanController@getCell');
        // 环节和信息、试卷相关的接口
        Route::get('node/{node_id}/resource', 'NodePivotController@show')->where(['node_id'=>'[0-9]+']);
        Route::post('node/{node_id}/resource', 'NodePivotController@store')->where(['node_id'=>'[0-9]+']);
        Route::get('member/user', 'MemberController@index');//获取用户的接口，此接口可以通过plat来获取
        Route::get('member/teacher', 'MemberController@teacher');//获取老师的接口，这里获取的是teacher表的id不是userid
		Route::get('member/teacher_id', 'MemberController@teacher_id');//获取老师的接口，这里获取的是teacher表的id不是userid
        Route::get('member/student', 'MemberController@student');//获取学生的接口，这里获取的是student表的id不是userid

        Route::get('mynotify/delete/{id}', 'MyNotifyController@destroy');
        Route::resource('mynotify', 'MyNotifyController');//消息列表

        Route::get('notifyuser', 'MemberController@defaultUser');

        Route::post('bindmobile','SmsController@bindMobile');//绑定手机

        Route::post('faq/reply', '\App\Http\Controllers\Teacher\OnlineQaController@reply'); //回复一个在线答疑

        Route::post("file/store", "\App\Http\Controllers\Administrator\Resource\FileController@apiPostStore");
    });

    //教师平台的接口
    Route::group(['prefix' => 'teacher/api', 'namespace' => 'Teacher'], function () {
        Route::get('getall/{id}', 'SquadController@getAll');//获取某个单元的所有结构
        Route::get('getcell/{squad_id}', 'SquadController@getCellListBySquad');//获取某班级下的单元列表
        Route::get('getmodule/{plan_id}-{cell_id}', 'SquadController@getModuleListByCell');//获取某单元下的模块列表
        Route::get('getnode/{plan_id}-{module_id}', 'SquadController@getNodeListByModule');//获取某模块下的环节列表
        Route::get('grouplist/{squad_id}-{type}', 'GroupController@getGroup');//获取某个班组的项目组及专题组信息
        Route::post('savegroup/{squad_id}-{type}', 'GroupController@saveGroup');//更新学生的分组信息
        // 点击环节完成
        // Route::get('squad/node/complete/{squad_id}/{node_id}','SquadController@nodeComplete');
        //
        Route::get('squad/module/complete/{squad_id}/{planStruct_id}','SquadController@moduleComplete');
        // 点击 发送作业消息，更新作业时间
        Route::post('squad/node/setpaper/{squad_id}/{node_id}','SquadController@nodeSetPaper');
        // 点击 发送预习消息 更新预习状态
        Route::get('squad/node/setyuxi/{squad_id}/{node_id}','SquadController@nodeSetYuXi');
        // 点击 发送评分消息
        Route::get('squad/node/setpingjia/{squad_id}/{node_id}','SquadController@nodeSetEvaluate');
        // 点击 发送开始投票
	Route::post('squad/node/setvote/{squad_id}/{module_id}/{node_id}','SquadController@nodeSetVote');
    
        // 获取项目组和专题组
        Route::get('squad/group/{squad_id}','SquadController@groups');
    });

    //教师平台的接口
    Route::group(['prefix' => 'admin/api', 'namespace' => 'Administrator'], function () {
        Route::get('getcell/{plan_id}', 'PlanController@getCellListByPlan');//获取某方案下的单元列表
        Route::get('getmodule/{plan_id}-{cell_id}', 'PlanController@getModuleListByCell');//获取某单元下的模块列表
        Route::get('getnode/{plan_id}-{module_id}', 'PlanController@getNodeListByModule');//获取某模块下的环节列表
        Route::get('school/squad/{school_id}', 'SchoolController@getSquadList');
    });

    //学生平台的接口
    Route::group(['prefix' => 'student/api', 'namespace' => 'Student'], function () {
        // 答疑|评论|互动——列表|提交接口
        Route::any('qa/{planStruct_id}/{type}','OnlineQaController@run');
    });

});
