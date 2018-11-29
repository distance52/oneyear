<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/3/21
 * Time: 10:58
 */
namespace App\Http\Services;

use Illuminate\Http\Request;
use App\Http\Requests;
use App\Services\AuthProvider;


class SyncAccountService implements SyncAccountInterface
{
    private $partner = null;

    public function syncLogin($userId)
    {
        $providerName = $this->getAuthProvider()->getProviderName();
        //$bind         = $this->getUserService()->getUserBindByTypeAndUserId($providerName, $userId);
        $bind=array(
            'fromId'=>1,
            'uid'=>2
        );
        if (empty($bind)) {
            return '';
        }
        return $this->getAuthProvider()->syncLogin($bind['fromId']);
    }

    public function syncLogout($userId)
    {
        $providerName = $this->getAuthProvider()->getProviderName();
        $bind         = $this->getUserService()->getUserBindByTypeAndUserId($providerName, $userId);

        if (empty($bind)) {
            return '';
        }

        return $this->getAuthProvider()->syncLogout($bind['fromId']);
    }

    public function checkUsername($username, $randomName = '')
    {
        //如果一步注册则$randomName为空，正常校验discus和系统校验，如果两步注册，则判断是否使用默认生成的，如果是，跳过discus和系统校验

        if (empty($randomName) || $username != $randomName) {
            try {
                $result = $this->getAuthProvider()->checkUsername($username);
            } catch (\Exception $e) {
                return array('error_db', '暂时无法注册，管理员正在努力修复中。（Ucenter配置或连接问题）');
            }

            if ($result[0] != 'success') {
                return $result;
            }

            $avaliable = $this->getUserService()->isNicknameAvaliable($username);

            if (!$avaliable) {
                return array('error_duplicate', '名称已存在!');
            }
        }

        return array('success', '');
    }

    public function checkEmail($email)
    {
        try {
            $result = $this->getAuthProvider()->checkEmail($email);
        } catch (\Exception $e) {
            return array('error_db', '暂时无法注册，管理员正在努力修复中。（Ucenter配置或连接问题）');
        }

        if ($result[0] != 'success') {
            return $result;
        }

        $avaliable = $this->getUserService()->isEmailAvaliable($email);

        if (!$avaliable) {
            return array('error_duplicate', 'Email已存在!');
        }

        return array('success', '');
    }

    public function checkMobile($mobile)
    {
        try {
            $result = $this->getAuthProvider()->checkMobile($mobile);
        } catch (\Exception $e) {
            return array('error_db', '暂时无法注册，管理员正在努力修复中。（Ucenter配置或连接问题）');
        }

        if ($result[0] != 'success') {
            return $result;
        }

        $avaliable = $this->getUserService()->isMobileAvaliable($mobile);

        if (!$avaliable) {
            return array('error_duplicate', '手机号码已存在!');
        }

        return array('success', '');
    }

    public function checkEmailOrMobile($emailOrMobile)
    {
        if (SimpleValidator::email($emailOrMobile)) {
            return $this->checkEmail($emailOrMobile);
        } else

            if (SimpleValidator::mobile($emailOrMobile)) {
                return $this->checkMobile($emailOrMobile);
            } else {
                return array('error_dateInput', '电子邮箱或者手机号码格式不正确!');
            }
    }

    protected function getAuthProvider()
    {
        if (!$this->partner) {
            $partner='discuz';
            //App\Services\AuthProvider\DiscuzAuthProvider
            $class = "App\\Services\\AuthProvider\\".ucfirst($partner)."AuthProvider";
            $this->partner = new $class();
        }
        return $this->partner;
    }
}