<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/3/22
 * Time: 9:54
 */

namespace App\Http\Controllers\NoticeSend;


class WechatNoticeController extends NoticeHttp{
    /**
     * 新增模版
     * @param $template_id
     * @return array|mixed
     */
    function wx_get_add_template($template_id) {
        $access_token = get_access_token();
        $data['template_id_short'] = $template_id;
        $tmpInfo = hxCurlPost("https://api.weixin.qq.com/cgi-bin/template/api_add_template?access_token={$access_token}", $data);
        $jsoninfo_template = json_decode($tmpInfo, true);
        if (!$jsoninfo_template['access_token']) {
            return array('status' => -2, 'msg' => '错误编号：' . $jsoninfo_template['errcode'] . ',详细：' . $tmpInfo);
            exit();
        }
        return $jsoninfo_template;
    }

    /**
     * 发送模版消息
     * @param $touser 微信用户id
     * @param string $template_id
     * @param $data
     * @param string $url
     * @return array
     */
    public function send($touser, $template_id = 'B6t2pY2-O089r4kLAGq5pQSNnYlk1OA_VtCWaq6Dp6w', $data, $url = 'http://weixin.qq.com/download') {
        $access_token = get_access_token();
        if(!$access_token){
            \Log::error('微信发送情况：access_token获取失败');
            return array('status' => -2, 'msg' => 'access_token获取失败');
            exit();
        }
        /**
        $data_err='';
        foreach($data as $val){
            $data_err.=$val;
        }
        \Log::error('微信号'.$touser.'发送情况：'.$data_err);
         * */
        $data_err=array();
        foreach($data as $key=>$val){
            $data_err[$key]=array('value'=>$val);
        }
        $get_array = json_encode(array('touser' => $touser, 'template_id' => $template_id, 'url' => $url, 'topcolor' => '#FF0000', 'data' => $data_err), true);
        $template = urldecode($get_array);
        $posturl="https://api.weixin.qq.com/cgi-bin/message/template/send?access_token={$access_token}";
        $tmpInfo=$this->postRequest($posturl,$template);
        $jsoninfo = json_decode($tmpInfo, true);
        \Log::error('微信号'.$touser.'发送情况：'.$tmpInfo);
        if($jsoninfo['errcode']){
            return array('status' => -3, 'msg' => '错误编号：' . $jsoninfo['errcode'] . ',详细：' .  $jsoninfo['errmsg']);
            exit();
        }
        if (!$jsoninfo['msgid']) {
            return array('status' => -4, 'msg' => '错误编号：' . $jsoninfo['errcode'] . ',详细：' . $tmpInfo);
            exit();
        }else{

            return array('status' => 1, 'msg' => '推送成功','data'=>$jsoninfo);
        }
    }
}