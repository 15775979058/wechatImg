<?php
class DatabaseModel {

    function __construct() {
        
    }
    
    //连接数据库方法
    function connectDatabase() {
        $dbname = 'wximg_gzxd120_com';    //数据库名称
        $host = 'rds10mh1rv5l0jb7wnxa.mysql.rds.aliyuncs.com';         //主机名称
        $port = 3306;                //数据库端口号
        $user = 'xdwximg';              //用户名AK
        $pwd = 'XDsql%*0308';             //密码SK
        //打开数据库连接
        $link = @mysql_connect("{$host}:{$port}",$user,$pwd,true);
        if(!$link) {
            die("数据库连接失败: " . mysql_error());
        }
        //连接成功后立即调用mysql_select_db()选中需要连接的数据库
        if(!mysql_select_db($dbname,$link)) {
            die("选择数据库失败: " . mysql_error($link));
        }
        return $link;
    }
}




