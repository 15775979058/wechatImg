<?php
//header("Content-type:text/html;charset=utf-8");

class IndexController {

    function __construct() {
        
    }
    
    //上传表单
    function indexAction() {
        //载入首页视图
        require_once './Views/index.html';
    }
}
