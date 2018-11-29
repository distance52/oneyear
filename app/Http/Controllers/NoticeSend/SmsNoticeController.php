<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/3/25
 * Time: 15:42
 */

namespace App\Http\Controllers\NoticeSend;


class SmsNoticeController extends NoticeHttp
{
    private $key='23325702';
    private $secret='57ae2296384276d09ee1a5709baa1629';
    private $apiUrl='https://ca.aliyuncs.com/gw/alidayu/sendSms';

    public function setConfig($config){
        if($config['key'] && $config['secret']){
            $this->key=$config['key'];
            $this->secret=$config['secret'];
        }
    }

    /**
     * @param $touser 单个为手机号，多个为用英文逗号隔开的手机号，一次性不超过200个
     * @param string $template_id  短信模板ID
     * @param $data
     * @return array content,extend
     * {"model":"100980620579^1101448319175","code":"0","success":true}返回格式为json格式的字符串
     */
    public function send($touser, $template_id = 'SMS_5905293', $data) {
        $header[] = "X-Ca-Key:{$this->key}";
        $header[] = "X-Ca-Secret:{$this->secret}";
        //$data = json_encode($data);
        //直接传送json格式的会报参数不存在，先拼装json
        $str='{';
        $i=1;
        foreach($data as $key=>$val){
            $dot=',';
            if($i==count($data)){
                $dot='';
            }
            $str.="'".$key."':'".$val."'".$dot;
            $i++;
        }
        $str.='}';
        $param=array(
            'rec_num'=>$touser,
            'sms_template_code'=>$template_id,
            'sms_type'=>'normal',
            'sms_free_sign_name'=>'乐道',//签名必须要是建立的签名，否则会报错
            'extend'=>1234,
            'sms_param'=>$str
            //'sms_param'=>"{'code':'1234','product':'alidayu'}"
        );
        //print_r($param);
        $tmpInfo=$this->httpGetHeader($this->apiUrl,$param,$header);
        \Log::error('手机号'.$touser.'发送情况：'.$tmpInfo);
        return $tmpInfo;
    }
}