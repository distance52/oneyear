<?php

namespace App\Http\Controllers\Administrator\Other;

use Illuminate\Http\Request;

use Illuminate\Routing\UrlGenerator;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use App\Models\Tag;

class TagController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $aSearch = [];
        \Request::has('name') && \Request::input('name') && $aSearch['name'] = \Request::input('name');
        //
        $oObjs = new Tag;
        if($aSearch) {
            $oObjs = $oObjs->where('name', 'like', '%'.$aSearch['name'].'%');
        }
        $oObjs = $oObjs->orderBy('id','desc');
		$num['a'] = $oObjs->count();
		$oObjs=$oObjs->paginate(20);
		$num['b'] = $oObjs->count();
        //
		if (view()->exists(session('mode').'.other.tag.list')){
			return View(session('mode').'.other.tag.list', compact('oObjs','aSearch','num'));
		}else{
			return View('default.other.tag.list', compact('oObjs','aSearch','num'));
		}
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $oObj = Tag::find($id);
		if (view()->exists(session('mode').'.other.tag.show')){
			return View(session('mode').'.other.tag.show', compact('oObj'));
		}else{
			return View('default.other.tag.show', compact('oObj'));
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
        //未完成，不仅仅如此
        $oObj = Tag::find($id);
        if($oObj) {
            $oObj->name = $request->input('name', '');
            $oObj->save();
            return back()
                ->withInput()
                ->withErrors([
                    'msg' => '修改成功',
                ]);
        } else {
            //跳转错误页面
            return redirect('error')->with(['msg'=>'参数错误，不存在此教学单元', 'href'=>app(UrlGenerator::class)->previous()]);
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

    }
}
