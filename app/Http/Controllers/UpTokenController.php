<?php
/**
 * Created by PhpStorm.
 * User: xiangli
 * Date: 2016/10/6
 * Time: 14:53
 */

namespace App\Http\Controllers;
use Qiniu\Auth;

class UpTokenController extends Controller
{
    public function getToken(){
        $bucket = 'mudsky-1';
        $accessKey = 'adNgFyEmDn8ESiqmgjyQ7_0sSerDt5tAwG0vF9s1';
        $secretKey = 'bkHnHral1RAMHoYttmwqHeg-3OdqeKPp0mqWnNf9';
        $auth = new Auth($accessKey, $secretKey);
        $upToken = $auth->uploadToken($bucket);
		$json = array('uptoken' => $upToken);
        echo json_encode($json);
    }
}