<?php

namespace App\Http\Middleware;
use App\Models\Permission as Per;


use Illuminate\Routing\UrlGenerator;
use Closure;

class Permission
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {

        // 用户判定
        $oUser = \Auth::user();
        // 获取用户的权限
        if($oUser->plat === 0) {
            $query = \Request::getQueryString();
            $uri = \Request::path();

            //
            if(!$oUser->role) {
                // 没有设置角色，出错
            }
            if($oUser->role->name != '超级管理员') {
                $arrPermissions = $oUser->role->permissions()->pluck('id')->toArray();
                $arrUnPermissions = [];
                Per::where('permission_id','!=',0)->get()->each(function($objPermission, $item) use (&$arrUnPermissions, $arrPermissions) {
                    if(!$arrPermissions) {
                        $arrUnPermissions[] = $objPermission->toArray();
                    } elseif(!in_array($objPermission->id, $arrPermissions)) {
                        $arrUnPermissions[] = $objPermission->toArray();
                    }
                });

                if($arrUnPermissions) {
                    foreach($arrUnPermissions as $arrUnPermission) {
                        $url = $arrUnPermission['alias'];
                        if($url) {
                            $tmpQuery = $tmpUri = '';
                            $tmp = explode('?', $url);
                            $tmpUri = trim($tmp[0],'/');
                            if($uri == $tmpUri || starts_with($uri,$tmpUri)) {
                                if($tmpQuery) {
                                    if($query && str_contains($query,$tmpQuery)) {
                                        // 非法操作
                                        return redirect('error')->with(['msg'=>'你没有权限操作！', 'href'=>app(UrlGenerator::class)->previous()]);

                                    }
                                } else {
                                    return redirect('error')->with(['msg'=>'你没有权限操作！', 'href'=>app(UrlGenerator::class)->previous()]);
                                }

                            }
                        }
                    }
                }
            }
        }
        return $next($request);
    }
}
