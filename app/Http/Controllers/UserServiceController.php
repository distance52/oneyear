<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Http\Requests;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserBind;
use App\Models\UserOpenid;
use App\Services\OAuthClient\OAuthClientFactory;

class UserServiceController extends Controller
{

    /**
     * 获取一个用户的全部绑定
     * @param $userId
     * @return mixed
     */
    public function findBindsByUserId($userId)
    {
        $user = User::whereid($userId)->first();
        if (empty($user)) {
            throw $this->createServiceException('获取用户绑定信息失败，当前用户不存在');
        }
        return UserBind::wheretoId($userId)->get();
    }

    protected function typeInOAuthClient($type)
    {
        $types = array_keys(OAuthClientFactory::clients());
        $types = array_merge($types, array('discuz', 'phpwind'));
        return in_array($type, $types);
    }

    /**
     * 解除绑定
     * @param $type
     * @param $toId
     * @return int|mixed
     */
    public function unBindUserByTypeAndToId($type, $toId)
    {
        $user = User::whereid($toId)->first();
        if (empty($user)) {
            throw $this->createServiceException('解除第三方绑定失败，该用户不存在');
        }

        if (!$this->typeInOAuthClient($type)) {
            throw $this->createServiceException("{$type}类型不正确，解除第三方绑定失败。");
        }
        $bind = UserBind::where(['type'=>$type,'toId'=>$toId])->first();
        if ($bind) {
            $bind   = UserBind::where(['id'=>$bind->id])->delete();
        }
        return $bind;
    }

    /**
     * 根据来源获取绑定情况
     * @param $type weixin,weibo,Qq
     * @param $fromId
     * @return mixed
     */
    public function getUserBindByTypeAndFromId($type, $fromId)
    {
        if ($type == 'weixinweb' || $type == 'weixinmob') {
            $type = 'weixin';
        }
        return UserBind::where(['type'=>$type,'fromId'=>$fromId])->first();
    }

    public function getUserBindByToken($token)
    {
        return UserBind::where(['token'=>$token])->first();
    }

    /**
     * 根据来源获取绑定情况V2
     * @param $type weixin,weibo,Qq
     * @param $fromId
     * @return mixed
     */
    public function getUserBindByTypeAndFromIdV2($type, $fromId)
    {
        if ($type == 'weixinweb' || $type == 'weixinmob') {
            $type = 'weixin';
        }
        return UserBind::where(['type'=>$type,'fromId'=>$fromId])->get();
    }

    /**
     * 查询已绑定账号数量
     * @param $type weixin,weibo,Qq
     * @param $fromId
     * @return int
     */
    public function getUserBindCount($type, $fromId)
    {
        if ($type == 'weixinweb' || $type == 'weixinmob') {
            $type = 'weixin';
        }
        return UserBind::where(['type'=>$type,'fromId'=>$fromId])->count();
    }

    /**
     * 查询已绑定账号学生数量
     * @param $type weixin,weibo,Qq
     * @param $fromId
     * @return int
     */
    public function getStudentBindCount($type, $fromId)
    {
        if ($type == 'weixinweb' || $type == 'weixinmob') {
            $type = 'weixin';
        }
        $count = UserBind::where(['type'=>$type,'fromId'=>$fromId])->whereHas('user',function ($query){
            $query->where('plat',3);
        })->count();
        return $count;
    }

    /**
     * 查询该帐号是否绑定过
     * @param $type
     * @param $toId
     * @return mixed
     */
    public function getUserBindByTypeAndUserId($type, $toId)
    {
        $user = User::whereid($toId)->first();
        if (empty($user)) {
            throw $this->createServiceException('获取用户绑定信息失败，该用户不存在');
        }
        if (!$this->typeInOAuthClient($type)) {
            throw $this->createServiceException("{$type}类型不正确，获取第三方登录信息失败。");
        }
        if ($type == 'weixinweb' || $type == 'weixinmob') {
            $type = 'weixin';
        }
        return UserBind::where(['type'=>$type,'toId'=>$toId])->first();
    }

    /**
     * 绑定帐号
     * @param $type
     * @param $fromId
     * @param $toId
     * @param $token
     * @return mixed
     */
    public function bindUser($type, $fromId, $toId, $token)
    {
        $user = User::whereid($toId)->first();
        if (empty($user)) {
            throw $this->createServiceException('用户不存在，第三方绑定失败');
        }
        if (!$this->typeInOAuthClient($type)) {
            throw $this->createServiceException("{$type}类型不正确，第三方绑定失败。");
        }
        if ($type == 'weixinweb' || $type == 'weixinmob') {
            $type = 'weixin';
        }
        $bind = UserBind::where(['type'=>$type,'toId'=>$toId])->first();
        if ($bind) {
            UserBind::where(['id'=>$bind->id])->delete();
        }
        $bind_id=UserBind::insert(array(
            'type'        => $type,
            'fromId'      => $fromId,
            'toId'        => $toId,
            'token'       => empty($token['token']) ? '' : $token['token'],
            'createdTime' => time(),
            'expiredTime' => empty($token['expiredTime']) ? 0 : $token['expiredTime']
        ));
        if(isset($token['avatar']) && $token['avatar']!=''){
            //更新用户的头像
            $user=User::where('id',$toId)->first();
            $user->avatar=$token['avatar'];
            $user->save();
        }
        return $bind_id;
    }

    /**
     * 将用户加入微信推送表中
     * @param $toId
     * @param $openid
     */
    public function bindUidOpenid($toId,$openid){
        if($openid!=''){
            $oUser=UserOpenid::where(['user_id'=>$toId])->first();
            $oUserOpen=UserOpenid::where(['openid'=>$openid])->first();
            //只有该用户没有绑定过微信并且该微信号没有被其他用户绑定才插入，否则会bug
            if(empty($oUser) && empty($oUserOpen)){
                UserOpenid::insert(array(
                    'user_id'=>$toId,
                    'openid'=>$openid
                ));
            }
        }
    }
}
