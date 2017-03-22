<?php
class WechatModel {
    
    function __construct() {
        
    }
    
    //跳转到微信网页授权登录URL
    function jumpWechatLogin($redirect_url, $scope,$state) {
        $open_url = "https://open.weixin.qq.com/connect/oauth2/authorize?appid=wx011cddc56212c6ed&redirect_uri=".$redirect_url."&response_type=code&scope=".$scope."&state=".$state."#wechat_redirect";
        header("Location: ".$open_url);
    }
    
    //授权登录信息获取
    function wxOAuthLogin() {
        //用户同意授权，获取code
        $code = !empty($_GET['code']) ? $_GET['code'] : exit();
        $state = !empty($_GET['state']) ? $_GET['state'] : exit();
        //判断授权类型
        if($state == "base"){
            //通过code换取网页授权openid
            $url_openid="https://api.weixin.qq.com/sns/oauth2/access_token?appid=wx011cddc56212c6ed&secret=b2aa40a0d1f71b9ba4103ca05c22453e&code=$code&grant_type=authorization_code";
            $json_token = file_get_contents($url_openid);
            $arr_token = json_decode($json_token);
            return $arr_token;         //返回openid
        }elseif($state == "userinfo"){
            //通过code换取网页授权access_token
            $url_openid="https://api.weixin.qq.com/sns/oauth2/access_token?appid=wx011cddc56212c6ed&secret=b2aa40a0d1f71b9ba4103ca05c22453e&code=$code&grant_type=authorization_code";
            $json_token = file_get_contents($url_openid);
            $arr_token = json_decode($json_token);
            $access_token = $arr_token->{'access_token'};         //获取access_token
            $openid = $arr_token->{'openid'};                     //获取openid
            //拉取用户信息(需scope为 snsapi_userinfo)
            $url_info="https://api.weixin.qq.com/sns/userinfo?access_token=".$access_token."&openid=".$openid."&lang=zh_CN";
            $json_info = file_get_contents($url_info);
            return json_decode($json_info);         //返回用户信息数组
        }
    }
    
    //获取当前网页URL方法
    function getPageURL() {
        $pageURL = 'http';
        if ($_SERVER["HTTPS"] == "on")
        {
          $pageURL .= "s";
        }
        $pageURL .= "://";

        if ($_SERVER["SERVER_PORT"] != "80")
        {
          $pageURL .= $_SERVER["SERVER_NAME"] . ":" . $_SERVER["SERVER_PORT"] . $_SERVER["REQUEST_URI"];
        } 
        else
        {
          $pageURL .= $_SERVER["SERVER_NAME"] . $_SERVER["REQUEST_URI"];
        }
        return $pageURL;
    }

}

