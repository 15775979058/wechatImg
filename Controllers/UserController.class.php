<?php
class UserController {

    function __construct() {
        
    }
    
    //微信登录
    function wxLoginAction() {
        header("Content-type:text/html;charset=utf-8");
        //调用微信模块获取授权用户信息
        require_once './Models/WechatModel.class.php';
        $wechat = new WechatModel();
        $user_data = $wechat->wxOAuthLogin();
        //存储微信授权信息到数据库
        require_once './Models/UserModel.class.php';
        $user = new UserModel();
        $user->storeUserInfo($user_data);
    }
    
    //我的作品页面
    function myImgAction() {
        header("Content-type:text/html;charset=utf-8");
        //检查是否已经登录
        require './Models/UserModel.class.php';
        $user = new UserModel();
        $user->loginCheck(false);
        //获取我的作品
        require_once './Models/UserModel.class.php';
        $user = new UserModel();
        $ret_sqldata = $user->getMyImg();
        require_once './Views/myImg.html';
    }
    
    //投票动作
    function voteAction() {
        header("Content-type:text/html;charset=utf-8");
        //调用投票函数
        require './Models/UserModel.class.php';
        $user = new UserModel();
        echo $user->vote();       //返回投票结果  
    }
}