<?php
//header("Content-type:text/html;charset=utf-8");

class IndexController {

    function __construct() {
        
    }
    
    //首页
    function indexAction() {
        //检查是否已经登录
        require './Models/UserModel.class.php';
        $user = new UserModel();
        $user->loginCheck(false);
        //调用JS-SDK
        require_once './Models/JsSdkModel.class.php';
        $jssdk = new JSSDK("wx011cddc56212c6ed", "b2aa40a0d1f71b9ba4103ca05c22453e");
        $signPackage = $jssdk->GetSignPackage();
        //载入首页视图
        require_once './Views/index.html';
    }
}