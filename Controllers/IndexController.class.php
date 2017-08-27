<?php
//header("Content-type:text/html;charset=utf-8");

class IndexController {

    function __construct() {
        
    }


    /**
     * 首页
     */
    function indexAction() {
        //检查是否已经登录
        $wechat = new WechatModel();
        $redirect_url = "http%3A%2F%2Fwximg.gzxd120.com%2Findex.php%3Fc%3DUser%26a%3DcollectUserInfo";        //用户信息收集页面URL
        $wechat->loginCheck($redirect_url);
        //调用JS-SDK
        $jssdk = new JsSdkModel("wx011cddc56212c6ed", "b2aa40a0d1f71b9ba4103ca05c22453e");
        $signPackage = $jssdk->GetSignPackage();
        //载入首页视图
        require './Views/index.html';
    }
}
