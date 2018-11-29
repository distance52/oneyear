<?php

namespace App\Http;

use Illuminate\Foundation\Http\Kernel as HttpKernel;

class Kernel extends HttpKernel
{
    /**
     * The application's global HTTP middleware stack.
     *
     * These middleware are run during every request to your application.
     *
     * @var array
     */
    protected $middleware = [
        \Illuminate\Foundation\Http\Middleware\CheckForMaintenanceMode::class,
        \App\Http\Middleware\ResetConfig::class,
        \App\Http\Middleware\Cors::class, //解决跨域
        \Illuminate\Session\Middleware\StartSession::class,//开启session
        \Illuminate\View\Middleware\ShareErrorsFromSession::class,//错误总是有用
    ];

    /**
     * The application's route middleware groups.
     *
     * @var array
     */
    protected $middlewareGroups = [
        'web' => [
            \App\Http\Middleware\EncryptCookies::class,
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \App\Http\Middleware\VerifyCsrfToken::class,
        ],

        'wechat' => [
            \App\Http\Middleware\EncryptCookies::class,
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \App\Http\Middleware\VerifyCsrfToken::class,
            \App\Http\Middleware\WechatAutoLogin::class,
        ],

        'api' => [
            // 'throttle:60,1',

            \App\Http\Middleware\EncryptCookies::class,
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \App\Http\Middleware\IsLogin::class,
        ],
        'api2' => [
            // 'throttle:60,1',

            \App\Http\Middleware\EncryptCookies::class,
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
        ],
    ];

    /**
     * The application's route middleware.
     *
     * These middleware may be assigned to groups or used individually.
     *
     * @var array
     */
    protected $routeMiddleware = [
        'auth' => \App\Http\Middleware\Authenticate::class,
        'auth.basic' => \Illuminate\Auth\Middleware\AuthenticateWithBasicAuth::class,
        'guest' => \App\Http\Middleware\RedirectIfAuthenticated::class,
        'throttle' => \Illuminate\Routing\Middleware\ThrottleRequests::class,
        'wechatapi'=>\App\Http\Middleware\WechatApi::class,
        'permission'=>\App\Http\Middleware\Permission::class,
		'plan'=>\App\Http\Middleware\Plan::class,
        'plugin'=>\App\Http\Middleware\PluginPermission::class
    ];
}
