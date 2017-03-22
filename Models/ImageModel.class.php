<?php
class ImageModel {

    function __construct() {
        
    }
    
    
    //接收上传的图片文件方法
    function receiveImg() {
        $status = "";      //上传状态，用于控制表单提交按钮的隐藏/显示
        if(($_FILES["file"]["type"] == "image/gif") || ($_FILES["file"]["type"] == "image/jpeg")
        ||($_FILES["file"]["type"] == "image/pjpeg") || ($_FILES["file"]["type"] == "image/png")){ 

          if($_FILES["file"]["error"] > 0){     //文件错误
            $status = "upload failure";
           }
            else{
                if(file_exists("./upload/" . $_FILES["file"]["name"])){     //文件已经存在
                  $status = "file existed";
                }
                else{       //上传成功并保存
                    $randname=date("Y").date("m").date("d").date("H").date("i").date("s").rand(100, 999).".jpg";       //时间戳+随机数字，防止名字重复
                    move_uploaded_file($_FILES["file"]["tmp_name"], "./upload/" . $randname);
                    $status = "upload completed";
                }
            }
        }
        else{       //格式错误
            $status = "format error";
        }

        //组装用于转换成JSON的数组
        $upload_result = array (
            "status"  => $status,
            "filename" => $randname,
        );
        //返回json数据
        return json_encode($upload_result);
    }
    
    
    //存储照片(作品)信息
    function storeInfo() {
        require_once './Models/DatabaseModel.class.php';
        $db = new DatabaseModel();
        $link = $db->connectDatabase();
        //对POST提交的变量进行非空验证
        $name = !empty($_POST['name']) ? $_POST['name'] : die("POST参数不正确");
        $openid = !empty($_POST['openid']) ? $_POST['openid'] : die("POST参数不正确");
        $title = !empty($_POST['title']) ? $_POST['title'] : die("POST参数不正确");
        $brief = !empty($_POST['brief']) ? $_POST['brief'] : die("POST参数不正确");
        $filename = !empty($_POST['filename']) ? $_POST['filename'] : die("POST参数不正确");
        //向photoinfo表插入数据
        $sql_picinfo="INSERT INTO wx_imginfo ( name, openid, title, brief, img_file_name) VALUES ('$name','$openid','$title','$brief','$filename')";
        $ret_inslog = mysql_query($sql_picinfo, $link);
        if ($ret_inslog === false) {
            die("插入数据失败: " . mysql_error($link));
        }
        else{
            return "<meta http-equiv='refresh' content='3; url=./index.php?c=Image&a=ranklist' /><h2 align='center'>作品上传成功 3秒后自动跳转到排行榜</h2>";
        }
    }
    
    
    //获取排行榜数据
    function getRanklist() {
        require_once './Models/DatabaseModel.class.php';
        $db = new DatabaseModel();
        $link = $db->connectDatabase();     //连接数据库
        $page = !empty($_POST['page']) ? $_POST['page'] : 0;         //获取页码，默认第0页
        $startPage = $page * 10;
        //查询photoinfo表
        $sql_picinfo="select * from wx_imginfo order by ticket desc limit $startPage,10";
        $ret_sqldata = mysql_query($sql_picinfo, $link);
        if ($ret_sqldata === false) {
            die("查询数据失败: " . mysql_error($link));
        }
        if($page == 0){
            return $ret_sqldata;        //如果不是ajax请求，直接返回查询结果集
        }else{
            //查询结果转存到数组中
            $arr_result =array();     //创建数组
            while ($arr_photoinfo=mysql_fetch_array($ret_sqldata)) {
                $arr_result[]=$arr_photoinfo;
            }
            //把数组编码成json并返回
            return json_encode($arr_result);
        }
    }
    
    
    //获取单个作品详细信息
    function getDetail() {
        require_once './Models/DatabaseModel.class.php';
        $db = new DatabaseModel();
        $link = $db->connectDatabase();     //连接数据库
        //对POST提交的变量进行验证
        $id = !empty($_GET['id']) ? $_GET['id'] : exit();
        //查询photoinfo表
        $sql_picinfo="select * from wx_imginfo where img_id='$id'";
        $ret_sqldata = mysql_query($sql_picinfo, $link);
        if ($ret_sqldata === false) {
            die("查询数据失败: " . mysql_error($link));
        }
        //检查是否有该记录，没有就是id错误，直接跳转到排行榜
        $rows = mysql_num_rows($ret_sqldata);
         if($rows==0){
             header("Location: ./index.php?c=Image&a=ranklist");        //记录不存在就跳转到排行榜
             exit();
        }
        //阅读量+1
        $sql_upd="UPDATE wx_imginfo SET pageview=pageview+1 WHERE img_id = '$id'";
        $result = mysql_query($sql_upd, $link);
        if ($result === false) {
            die("更新阅读量失败:" . mysql_error($link));
        }
        //返回数组数组
        return mysql_fetch_array($ret_sqldata);    //转换成数组
    }
    
    
    //搜索作品
    function searchImg() {
        require_once './Models/DatabaseModel.class.php';
        $db = new DatabaseModel();
        $link = $db->connectDatabase();     //连接数据库
        //对GET提交的变量进行验证
        $key = !empty($_GET['key']) ? $_GET['key'] : exit();
        $value = !empty($_GET['value']) ? $_GET['value'] : exit();
        //分类组装查询语句
        $sql_search = "";
        switch ($key) {
            case "1":
                $sql_search = "select * from wx_imginfo where img_id='$value'";
                break;
            case "2":
                $sql_search = "select * from wx_imginfo where title='$value'";
                break;
            case "3":
                $sql_search = "select * from wx_imginfo where name='$value'";
                break;
            default:
                die('不支持的搜索类型');
                break;
        }
        //查询photoinfo表
        $ret_sqldata = mysql_query($sql_search, $link);
        if ($ret_sqldata === false) {
            die("查询数据失败: " . mysql_error($link));
        }
        return $ret_sqldata;    //返回查询结果集
    }
    
    
    //获取照片信息，用于填充修改表单
    function getMyImgById($img_id) {
        //连接数据库
        require_once './Models/DatabaseModel.class.php';
        $db = new DatabaseModel();
        $link = $db->connectDatabase();
        //查询数据库
        $id = !empty($_GET['id']) ? $_GET['id'] : exit();       //验证Get变量
        $sql_picinfo="select * from wx_imginfo where img_id=".$id;
        $ret_sqldata = mysql_query($sql_picinfo, $link);
        if ($ret_sqldata === false) {
            die("查询数据失败: " . mysql_error($link));
        }
        //验证openid，防止非法访问修改页面
        $arr_imginfo = mysql_fetch_array($ret_sqldata);          //记录转换成数组
        if($arr_imginfo['openid'] != $_COOKIE["openid"]){        //判断是否本人。此处直接读取cookie，因为登录检查方法已经验证了该cookie已经存在
            die('你不是该作品的拥有者');
        }
        return $arr_imginfo;        //返回照片信息数组
    }
    
    
    //更新照片信息
    function updateImg() {
        //连接数据库
        require_once './Models/DatabaseModel.class.php';
        $db = new DatabaseModel();
        $link = $db->connectDatabase();
        //对POST提交的变量进行验证
        $name = !empty($_POST['name']) ? $_POST['name'] : exit();
        $title = !empty($_POST['title']) ? $_POST['title'] : exit();
        $brief = !empty($_POST['brief']) ? $_POST['brief'] : exit();
        $img_id = !empty($_POST['img_id']) ? $_POST['img_id'] : exit();
        //更新数据库
        $sql_picinfo="UPDATE wx_imginfo SET name='$name',title='$title',brief='$brief' WHERE img_id='$img_id'";
        $ret_inslog = mysql_query($sql_picinfo, $link);
        if ($ret_inslog === false) {
            die("插入数据失败: " . mysql_error($link));
        }
        else{
            header("Location: https://open.weixin.qq.com/connect/oauth2/authorize?appid=wx011cddc56212c6ed&redirect_uri=http%3A%2F%2Fwximg.gzxd120.com%2F%3Fc%3DImage%26a%3Ddetail%26id%3D".$img_id."&response_type=code&scope=snsapi_base&state=STATE#wechat_redirect");     //跳转到作品详情页
        }   
    }
}