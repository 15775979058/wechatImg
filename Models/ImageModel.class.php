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
        //对POST提交的变量进行验证
        $name = "";
        $openid = "";
        $title = "";
        $brief = "";
        $filename = "";
        if(isset($_POST['name']) && is_string($_POST['name']) && !empty($_POST['name'])){             //判断变量已设置、非空并且是字符串
            $name = $_POST['name'];
        }else { exit(); }   //不符合任何一个条件，直接退出
        if(isset($_POST['openid']) && is_string($_POST['openid']) && !empty($_POST['openid'])){          //判断变量已设置、非空并且是字符串
            $openid = $_POST['openid'];
        }else { exit(); }
        if(isset($_POST['title']) && is_string($_POST['title']) && !empty($_POST['title'])){          //判断变量已设置、非空并且是字符串
            $title = $_POST['title'];
        }else { exit(); }
        if(isset($_POST['brief']) && is_string($_POST['brief']) && !empty($_POST['brief'])){          //判断变量已设置、非空并且是字符串
            $brief = $_POST['brief'];
        }else { exit(); }
        if(isset($_POST['filename']) && is_string($_POST['filename']) && !empty($_POST['filename'])){          //判断变量已设置、非空并且是字符串
            $filename = $_POST['filename'];
        }else { exit(); }
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
        $id = "";
        if(isset($_GET['id']) && is_string($_GET['id']) && !empty($_GET['id'])){       //判断变量已设置、非空并且是字符串
            $id = $_GET['id'];
        }else { exit(); }
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
        $key = "";
        $value = "";
        if(isset($_GET['key']) && is_string($_GET['key']) && !empty($_GET['key'])){             //判断变量已设置、非空并且是字符串
            $key = $_GET['key'];
        }else { exit(); }
        if(isset($_GET['value']) && is_string($_GET['value']) && !empty($_GET['value'])){          //判断变量已设置、非空并且是字符串
            $value = $_GET['value'];
        }else { exit(); }

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
            case "4":
                $sql_search = "select * from wx_imginfo where nickname='$value'";
                break;
            default:
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
        $sql_picinfo="select * from wx_imginfo where img_id=".$_GET['id'];
        $ret_sqldata = mysql_query($sql_picinfo, $link);
        if ($ret_sqldata === false) {
            die("查询数据失败: " . mysql_error($link));
        }
        return mysql_fetch_array($ret_sqldata);       //记录转换成数组
    }
    
    
    //更新照片信息
    function updateImg() {
        //连接数据库
        require_once './Models/DatabaseModel.class.php';
        $db = new DatabaseModel();
        $link = $db->connectDatabase();
        //对POST提交的变量进行验证
        $name = "";
        $title = "";
        $brief = "";
        $number = "";
        if(isset($_POST['name']) && is_string($_POST['name']) && !empty($_POST['name'])){             //判断变量已设置、非空并且是字符串
            $name = $_POST['name'];
        }else { exit(); }   //不符合任何一个条件，直接退出
        if(isset($_POST['title']) && is_string($_POST['title']) && !empty($_POST['title'])){          //判断变量已设置、非空并且是字符串
            $title = $_POST['title'];
        }else { exit(); }
        if(isset($_POST['brief']) && is_string($_POST['brief']) && !empty($_POST['brief'])){          //判断变量已设置、非空并且是字符串
            $brief = $_POST['brief'];
        }else { exit(); }
        if(isset($_POST['img_id']) && is_string($_POST['img_id']) && !empty($_POST['img_id'])){       //判断变量已设置、非空并且是字符串
            $img_id = $_POST['img_id'];
        }else { exit(); }
        //更新数据
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