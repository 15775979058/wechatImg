<?php
class ImageController {

    function __construct() {
        
    }
    
    //上传表单
    function uploadAction() {
        //检查是否已经登录
        require './Models/UserModel.class.php';
        $user = new UserModel();
        $user->loginCheck(true,"snsapi_userinfo","userinfo");
        require_once './Views/uploadImg.html';
    }
    
    //接收ajax提交的图片文件
    function receiveImgAction() {
        header('Content-type: text/json');      //json格式
        require_once './Models/ImageModel.class.php';
        $img = new ImageModel();
        echo $img->receiveImg();         //直接向客户端返回json数据
    }
    
    //存储作品(图片)信息
    function storeInfoAction() {
        header("Content-type:text/html;charset=utf-8");
        require_once './Models/ImageModel.class.php';
        $img = new ImageModel();
        echo $img->storeInfo();         //直接向客户端打印结果
    }
    
    
    //获取排行榜数据
    function ranklistAction() {
        //检查是否已经登录
        require './Models/UserModel.class.php';
        $user = new UserModel();
        $user->loginCheck(false);
        //获取获取排行榜数据
        require_once './Models/ImageModel.class.php';
        $img = new ImageModel();
        $rankData = $img->getRanklist();
        if(isset($_POST[page]) && !empty($_POST[page])){
            echo $rankData;     //回复排行榜json数据
            return;             //ajax提交无需加载排行榜视图，所以直接返回
        }
        $ret_sqldata = $rankData;       //如果不是ajax请求，函数调用后的结果是sql查询结果集，方便视图读取
        require_once './Views/ranklist.html';
    }
    
    //作品展示页面
    function detailAction() {
        //检查是否已经登录
        require './Models/UserModel.class.php';
        $user = new UserModel();
        $user->loginCheck(false);
        //调用JS-SDK
        require_once './Models/JsSdkModel.class.php';
        $jssdk = new JSSDK("wx011cddc56212c6ed", "b2aa40a0d1f71b9ba4103ca05c22453e");
        $signPackage = $jssdk->GetSignPackage();
        //获取详细信息
        require_once './Models/ImageModel.class.php';
        $img = new ImageModel();
        $arr_singleinfo = $rankData = $img->getDetail();
        require_once './Views/detail.html';
    }
    
    //搜索动作
    function searchAction() {
        //检查是否已经登录
        require './Models/UserModel.class.php';
        $user = new UserModel();
        $user->loginCheck(false);
        //执行搜索操作
        require_once './Models/ImageModel.class.php';
        $img = new ImageModel();
        $ret_sqldata = $img->searchImg();
        require_once './Views/search.html';
    }
    
    //修改照片信息
    function modifyAction() {
        //检查是否已经登录
        require './Models/UserModel.class.php';
        $user = new UserModel();
        $user->loginCheck(false);
        //获取照片信息，填充表单
        require_once './Models/ImageModel.class.php';
        $img = new ImageModel();
        $arr_singleinfo = $img->getMyImgById();
        require_once './Views/modify.html';
    }
    
    //更新照片信息
    function updateAction() {
        header("Content-type:text/html;charset=utf-8");
        require_once './Models/ImageModel.class.php';
        $img = new ImageModel();
        $img->updateImg();
    }
}












