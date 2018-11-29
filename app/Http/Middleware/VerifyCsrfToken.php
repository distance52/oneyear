<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as BaseVerifier;

class VerifyCsrfToken extends BaseVerifier
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array
     */
    protected $except = [
        '/resource/file/doupload',
        'teacher/api/savegroup/*',//改接口由于token无法验证，暂时解决方案移除token，@20170615
        'teacher/api/squad/node/setpaper/*',//改接口由于token无法验证，暂时解决方案移除token，@20170615
        'wechat/login',
        'wechat/msg',
    ];
}
