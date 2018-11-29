<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/3/21
 * Time: 13:46
 */

namespace App\Http\Services;


interface  SyncAccountInterface
{
    public function syncLogin($userId);

    public function syncLogout($userId);

    public function checkUsername($username, $randomName = '');

    public function checkEmail($email);

    public function checkMobile($mobile);

    public function checkEmailOrMobile($emailOrMobile);
}