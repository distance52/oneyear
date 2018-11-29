<?php

/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/3/22
 * Time: 9:52
 */
namespace App\Http\Controllers\NoticeSend;
class NoticeHttp
{
    protected $userAgent = 'Mozilla/5.0 (compatible; MSIE 5.01; Windows NT 5.0)';
    protected $connectTimeout = 30;
    protected $timeout = 30;
    /**
     * HTTP POST
     * @param  string 	$url    要请求的url地址
     * @param  array 	$params 请求的参数
     * @return string
     */
    public function postRequest($url, $params)
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($curl, CURLOPT_USERAGENT, $this->userAgent);
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, $this->connectTimeout);
        curl_setopt($curl, CURLOPT_TIMEOUT, $this->timeout);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_HEADER, 0);
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $params);
        curl_setopt($curl, CURLOPT_URL, $url );
        // curl_setopt($curl, CURLINFO_HEADER_OUT, TRUE );
        $response = curl_exec($curl);
        curl_close($curl);
        return $response;
    }

    public function getRequest($url, $params)
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($curl, CURLOPT_USERAGENT, $this->userAgent);
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, $this->connectTimeout);
        curl_setopt($curl, CURLOPT_TIMEOUT, $this->timeout);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_HEADER, 0);
        $url = $url . '?' . http_build_query($params);
        curl_setopt($curl, CURLOPT_URL, $url );
        $response = curl_exec($curl);
        curl_close($curl);
        return $response;
    }

    public function httpGetHeader($url, $params,$header)
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false); // 跳过证书检查
        curl_setopt($curl,CURLOPT_HEADER,false);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_TIMEOUT, $this->timeout);
        $url = $url . '?' . http_build_query($params);
        curl_setopt($curl, CURLOPT_URL, $url );
        $response = curl_exec($curl);
        curl_close($curl);
        return $response;
    }
}