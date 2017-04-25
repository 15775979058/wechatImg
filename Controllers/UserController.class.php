<?php
class UserController {

    function __construct() {
        
    }


    /**
     * 收集微信登录的用户信息
     */
    function collectUserInfoAction() {
        header("Content-type:text/html;charset=utf-8");
        //调用微信模块-授权登录方法
        require_once './Models/WechatModel.class.php';
        $wechat = new WechatModel();
        $user_data = $wechat->wxOAuthLogin("wx_userinfo");
    }


    /**
     * 我的作品页面
     */
    function myImgAction() {
        header("Content-type:text/html;charset=utf-8");
        //检查是否已经登录
        require './Models/WechatModel.class.php';
        $wechat = new WechatModel();
        $redirect_url = "http%3A%2F%2Fwximg.gzxd120.com%2Findex.php%3Fc%3DUser%26a%3DcollectUserInfo";        //用户信息收集页面URL
        $wechat->loginCheck($redirect_url);
        //获取我的作品
        require_once './Models/UserModel.class.php';
        $user = new UserModel();
        $ret_sqldata = $user->getMyImg();
        require_once './Views/myImg.html';
    }


    /**
     * 投票动作
     */
    function voteAction() {
        header("Content-type:text/html;charset=utf-8");
        //调用投票函数
        require './Models/UserModel.class.php';
        $user = new UserModel();
        echo $user->vote();       //返回投票结果  
    }
}