<?php

/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/3/23
 * Time: 10:34
 */
namespace App\Http\Controllers\WechatApi;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis as Redis;
use App\Models\SquadStruct;
use App\Models\Squad;
use App\Models\UserOpenid;
use App\Models\UserBind;
use App\Models\Student;

class IndexController extends Controller{

    public function __construct(){
        $this->middleware('wechatapi');
    }

    /**
     * 响应消息
     * @return [type] [description]
     */
    public function index()
    {
        $postdata = isset($GLOBALS["HTTP_RAW_POST_DATA"]) ? $GLOBALS["HTTP_RAW_POST_DATA"] : file_get_contents("php://input");
        if ($postdata){
            $postObj = simplexml_load_string($postdata, 'SimpleXMLElement', LIBXML_NOCDATA);
            $RX_TYPE = trim($postObj->MsgType);
            switch ($RX_TYPE)
            {
                case "text":
                    $resultStr = $this->receiveText($postObj);
                    break;
                case "event":
                    $resultStr = $this->receiveEvent($postObj);
                    break;
                default:
                    $resultStr = "";
                    break;
            }
            echo $resultStr;
        }
    }


    /**
     * 响应文本消息
     * @param  [type] $object [description]
     * @return [type]         [description]
     */
    private function receiveText($object)
    {
        $funcFlag = 0;
        $keyword = $object->Content;
        $info = $this->getInfo($keyword,$object->FromUserName);
        $rs = json_decode($info,true);
        if($rs['type'] == 'text'){
            $resultStr = $this->transmitText($object, $rs['info'], $funcFlag);
        }elseif($rs['type'] == 'list'){
            $resultStr = $this->transmitNews($object, $rs['info']);
        }else{
            $resultStr = $this->transmitText($object, '试试别的关键字吧！', $funcFlag);
        }
        return $resultStr;
    }

    /**
     * 响应事件
     * @param  [type] $object [description]
     * @return [type]         [description]
     */
    private function receiveEvent($object)
    {
        $contentStr = "";
        switch ($object->Event)
        {
            case "subscribe":
                // $contentStr = $this->getSubscribe($object->FromUserName);
                // if(empty($contentStr)){
                //     $contentStr = "欢迎关注双创教研";
                // }
                $contentStr = "欢迎关注双创教研公众号！
            
            进入双创课堂的同学，请<a href='https://student.sc.cnczxy.com/'>点击这里</a>绑定你的账号。

            帐号默认格式为（具体请以授课老师要求为准）：
            帐号邮箱：学号@学校邮箱后缀
            密码：学号后6位
            如果登录有问题，请直接在本公众号下直接给我们发信息，谢谢！";
                break;
            case "unsubscribe":
                break;
            case "CLICK":
                $keyword = $object->EventKey;
                $fromUser = $object->FromUserName;
                $info = $this->getInfo($keyword,$fromUser);
                $rs = json_decode($info,true);
                $contentStr = $rs['info'];
                if(!$contentStr){
                    $contentStr = "试试别的关键字吧！";
                }
                break;
            case "SCAN":
                $contentStr = $this->scanEvent($object);
                // $contentStr = "扫码成功key:".$object->EventKey."用户微信：".$object->ToUserName."发送账号：".$object->FromUserName."消息类型：".$object->MsgType."事件类型：".$object->Event."二维码ticket：".$object->Ticket;


                break;

            default:
                break;
        }
        if (is_array($contentStr)){
            $resultStr = $this->transmitNews($object, $contentStr);
        }else{
            $resultStr = $this->transmitText($object, $contentStr);
        }
        return $resultStr;
    }

    private function scanEvent($object){
        $str = explode( '-' , $object->EventKey);
        if(isset($str[1])){
            switch ($str[0]) {
                case 'user':
                    return $this->setUserData($object , $str[1]);
                    break;
                case 'qrsence_user':
                    return $this->setUserData($object , $str[1]);
                    break;
                case 'squad':
                    return $this->setSquadData($object , $str[1]);
                    break;
                default:
                    # code...
                    break;
            }
        }

    }

    private function setSquadData($object , $squad_id = null , $type = 'squad-'){
        $token = get_access_token();
        $openid = $object->FromUserName;
        $str = '获取用户信息失败';
        if ($openid){
            $user_id = UserOpenid::where('openid',$openid)->value('user_id');
            if($user_id){
                $oStudent = Student::where('user_id',$user_id)->first();
                // return $squad_id;
                $oSquadStruct = SquadStruct::where('squad_id',$squad_id)->where('struct_id',$oStudent->id)->where('type',1)->first();
                if($oSquadStruct){
                    $str = '你已经是本班级学生了，不能在申请加入。';
                }else{
                    $oSquadStruct = new SquadStruct();
                    $oSquadStruct->squad_id = $squad_id;
                    $oSquadStruct->struct_id = $oStudent->id;
                    $oSquadStruct->type = 1;
                    $oSquadStruct->save();
                    $str = '加入班级成功';
                }
            }else{
                //注册

                $qrid = rand(1, 4294967295);
                $enevtKey = 'squadUser-'.$qrid;
                $key = "wx:qrcode:{$enevtKey}";
                $url = "https://api.weixin.qq.com/cgi-bin/user/info?access_token={$token}&openid={$openid}&lang=zh_CN";
                $userData = file_get_contents($url);
                $userData = json_decode($userData ,true);
                $unionid = isset($userData['unionid'] ) ? $userData['unionid'] : '';
                $arr = array('EventKey' => $enevtKey ,'openid'=> $userData['openid'],'nickname'=>$userData['nickname'],'sex'=>$userData['sex'],'headimgurl'=>$userData['headimgurl'],'unionid'=>$unionid ,'squad_id' => $squad_id);

                Redis::set($key , json_encode($arr));
                Redis::expire($key ,3600);
                $str = "你还没有在双创教研平台注册，<a href='https://sc.cnczxy.com/user/profile/{$qrid}'>点击这里完成注册</a>";
            }
        }

        return $str;

    }

    private function setUserData($object , $kid = null , $type = 'user-'){
        $token = get_access_token();
        $openid = $object->FromUserName;
        //            $object = json_decode(json_encode($object),true);
//        $enevtKey = $object['EventKey'];
        $enevtKey = $type.$kid;
        $key = "wx:qrcode:{$enevtKey}";
        if(Redis::exists($key)){

            $url = "https://api.weixin.qq.com/cgi-bin/user/info?access_token={$token}&openid={$openid}&lang=zh_CN";
            $userData = file_get_contents($url);
            $userData = json_decode($userData ,true);
            $unionid = isset($userData['unionid'] ) ? $userData['unionid'] : '';
            $arr = array('EventKey' => $enevtKey ,'openid'=> $userData['openid'],'nickname'=>$userData['nickname'],'sex'=>$userData['sex'],'headimgurl'=>$userData['headimgurl'],'unionid'=>$unionid );

            Redis::set($key , json_encode($arr));
            Redis::expire($key ,3600);
        }
        $str = '操作成功';
        return $str;


    }

    /**
     * 文本消息翻译
     * @param  [type]  $object   [description]
     * @param  [type]  $content  [description]
     * @param  integer $funcFlag [description]
     * @return [type]            [description]
     */
    private function transmitText($object, $content, $funcFlag = 0)
    {
        $textTpl = "<xml>
<ToUserName><![CDATA[%s]]></ToUserName>
<FromUserName><![CDATA[%s]]></FromUserName>
<CreateTime>%s</CreateTime>
<MsgType><![CDATA[text]]></MsgType>
<Content><![CDATA[%s]]></Content>
<FuncFlag>%d</FuncFlag>
</xml>";
        $resultStr = sprintf($textTpl, $object->FromUserName, $object->ToUserName, time(), $content, $funcFlag);
        return $resultStr;
    }

    /**
     * 图文消息翻译
     * @param  [type]  $object   [description]
     * @param  [type]  $arr_item [description]
     * @param  integer $funcFlag [description]
     * @return [type]            [description]
     */
    private function transmitNews($object, $arr_item, $funcFlag = 0)
    {
        //首条标题28字，其他标题39字
        if(!is_array($arr_item))
            return;
        $itemTpl = "<item>
        <Title><![CDATA[%s]]></Title>
        <Description><![CDATA[%s]]></Description>
        <PicUrl><![CDATA[%s]]></PicUrl>
        <Url><![CDATA[%s]]></Url>
        </item>";
        $item_str = "";
        foreach ($arr_item as $item)
            $item_str .= sprintf($itemTpl, $item['Title'], $item['Description'], $item['PicUrl'], $item['Url']);
        $newsTpl = "<xml>
            <ToUserName><![CDATA[%s]]></ToUserName>
            <FromUserName><![CDATA[%s]]></FromUserName>
            <CreateTime>%s</CreateTime>
            <MsgType><![CDATA[news]]></MsgType>
            <Content><![CDATA[]]></Content>
            <ArticleCount>%s</ArticleCount>
            <Articles>
            $item_str</Articles>
            <FuncFlag>%s</FuncFlag>
            </xml>";
        $resultStr = sprintf($newsTpl, $object->FromUserName, $object->ToUserName, time(), count($arr_item), $funcFlag);
        return $resultStr;
    }


    private static function  curlInfo($toUrl,$urlParams = ''){
        $ch = curl_init();
        curl_setopt( $ch, CURLOPT_URL, $toUrl );//目标网址
        curl_setopt($ch, CURLOPT_POST,0);  //post方式传递
        curl_setopt($ch, CURLOPT_POSTFIELDS,$urlParams);
        // curl_setopt( $ch, CURLOPT_COOKIEJAR, $cookie );
        // curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, true );
        // curl_setopt( $ch, CURLOPT_ENCODING, "" );
        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
        curl_setopt( $ch, CURLOPT_AUTOREFERER, true );
        curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false );    # required for https urls
        // curl_setopt( $ch, CURLOPT_CONNECTTIMEOUT, $timeout );
        // curl_setopt( $ch, CURLOPT_TIMEOUT, $timeout );
        // curl_setopt( $ch, CURLOPT_MAXREDIRS, 10 );
        $content = curl_exec( $ch );
        $response = curl_getinfo( $ch );
        curl_close ( $ch );
        return $content;
    }


    /**
     * 关键字回复
     * @param  [type] $keyword  [description]
     * @param  string $fromUser [description]
     * @return [type]           [description]
     */
    private function getInfo($keyword,$fromUser=''){
        $datas=array();
        if($keyword == '客服'){
            $datas['type'] = 'text';
            $datas['info'] = '如有问题请在公众号留言，我们会尽快为您解决。';
        }else{
            $datas['type'] = 'text';
            $datas['info'] = "请<a href='https://sc.cnczxy.com/help'>点击这里获取帮助</a>或联系客服。
            如在产品使用中遇到问题，可在页面点击【问题反馈】或拨打客服电话。";
        }
        // if(empty($datas)){
        //     $datas['type'] = 'text';
        //     $datas['info']	= '试试别的关键字吧！';
        // }
        return json_encode($datas);
    }

    /**
     * 获取关注事件
     * @return [type] [description]
     */
    private function getSubscribe($openid){
        //加入关注数据表
        //$content = $this->txtModel->where(array('b_typeid'=>5))->getField('content');
        //$content = trim($content);
        $content='欢迎关注';
        return $content;
    }
}