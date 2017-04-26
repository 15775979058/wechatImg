<?php
class ImageModel {

    function __construct() {
        
    }
    

    /**
     * 接收文件
     * @return string   json数据，状态以及照片文件名
     */
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
                    $randname=date("Y").date("m").date("d").date("H").date("i").date("s").rand(100, 999).".jpg";       //照片名字，时间戳+随机数字，防止名字重复
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
    


    /**
     * 存储照片(作品)信息
     * @return string   http标签，meta定时刷新
     */
    function storeInfo() {
        //连接数据库
        require_once './Models/DatabaseModel.class.php';
        $db = new DatabaseModel();
        $pdo = $db->connectDatabase();
        //对POST提交的变量进行非空验证
        $post_name = !empty($_POST['name']) ? $_POST['name'] : die("POST参数不正确");
        $post_openid = !empty($_POST['openid']) ? $_POST['openid'] : die("POST参数不正确");
        $post_title = !empty($_POST['title']) ? $_POST['title'] : die("POST参数不正确");
        $post_brief = !empty($_POST['brief']) ? $_POST['brief'] : die("POST参数不正确");
        $post_filename = !empty($_POST['filename']) ? $_POST['filename'] : die("POST参数不正确");
        //对于提交的表单数据进行特殊字符检查，防止提交HTML标签
        $name = htmlspecialchars($post_name,ENT_QUOTES);        //转义所有HTML字符，包括英文的单引号、双引号
        $openid = htmlspecialchars($post_openid,ENT_QUOTES);
        $title = htmlspecialchars($post_title,ENT_QUOTES);
        $filename = htmlspecialchars($post_filename,ENT_QUOTES);
        //对简介进行特殊字符处理，包括回车换行符
        $no_specialchars_brief = htmlspecialchars($post_brief,ENT_QUOTES);         
        $brief = str_replace("\r\n", "<br>", $no_specialchars_brief);              //把回车换行符\r\n替换成<br/>
        //向wx_imginfo表插入数据
        $sql_query = "INSERT INTO wx_imginfo ( name, openid, title, brief, img_file_name) VALUES (?, ?, ?, ?, ?)";
        $sth = $pdo->prepare($sql_query);
        $sth->execute(array($name, $openid, $title, $brief, $filename)) or die("数据库错误: " . $sth->errorInfo()[2]);
        //上传成功，跳转到排行榜
        return "<meta http-equiv='refresh' content='3; url=./index.php?c=Image&a=ranklist' /><h2 align='center'>作品上传成功 3秒后自动跳转到排行榜</h2>";
    }
    

    /**
     * 获取排行榜数据
     * @return resource|string  排行榜数据
     */
    function getRanklist() {
        //连接数据库
        require_once './Models/DatabaseModel.class.php';
        $db = new DatabaseModel();
        $pdo = $db->connectDatabase();
        //获取请求页码
        $page = !empty($_POST['page']) ? $_POST['page'] : 0;         //获取页码，默认第0页
        $startPage = $page * 10;
        //查询wx_imginfo表
        $sql_select = "select * from wx_imginfo order by ticket desc limit ".$startPage." , 10";
        $sth = $pdo->prepare($sql_select);
        $sth->execute() or die("数据库错误: " . $sth->errorInfo()[2]);
        if($page == 0){
            return $sth;        //如果不是ajax请求，直接返回查询结果集
        }else{
            //查询结果转存到数组中
            $arr_result =array();     //创建数组
            while ($arr_photoinfo = $sth->fetch(PDO::FETCH_ASSOC)) {
                $arr_result[]=$arr_photoinfo;
            }
            //把数组编码成json并返回
            return json_encode($arr_result);
        }
    }


    /**
     * 获取单个作品详细信息
     * @return array    数组-单个作品
     */
    function getDetail() {
        //连接数据库
        require_once './Models/DatabaseModel.class.php';
        $db = new DatabaseModel();
        $pdo = $db->connectDatabase();
        //对POST提交的变量进行验证
        $id = !empty($_GET['id']) ? $_GET['id'] : exit();
        //查询photoinfo表
        $sql_select = "select * from wx_imginfo where img_id = ?";
        $sth = $pdo->prepare($sql_select);
        $sth->execute(array($id)) or die("数据库错误: " . $sth->errorInfo()[2]);
        //检查是否有该记录，没有就是id错误，直接跳转到排行榜
        $arr_detail = $sth->fetch(PDO::FETCH_ASSOC);
        $rows = count($arr_detail);
         if($rows==0){      //如果没有
             header("Location: ./index.php?c=Image&a=ranklist");        //记录不存在就跳转到排行榜
             exit();
        }
        //阅读量+1
        $sql_update = "UPDATE wx_imginfo SET pageview = pageview + 1 WHERE img_id = ?";
        $sth = $pdo->prepare($sql_update);
        $sth->execute(array($id)) or die("数据库错误: " . $sth->errorInfo()[2]);
        //返回数组数组
        return $arr_detail;    //转换成数组
    }
    

    /**
     * 搜索作品
     * @return resource     搜索结果，数据库查询结果集
     */
    function searchImg() {
        //连接数据库
        require_once './Models/DatabaseModel.class.php';
        $db = new DatabaseModel();
        $pdo = $db->connectDatabase();
        //对GET提交的变量进行验证
        $key = !empty($_GET['key']) ? $_GET['key'] : exit();
        $value = !empty($_GET['value']) ? $_GET['value'] : exit();
        //分类组装查询语句
        $sql_search = "";
        switch ($key) {
            case "1":
                $sql_search = "select * from wx_imginfo where img_id = ?";
                break;
            case "2":
                $sql_search = "select * from wx_imginfo where title = ?";
                break;
            case "3":
                $sql_search = "select * from wx_imginfo where name = ?";
                break;
            default:
                die('不支持的搜索类型');
                break;
        }
        //查询数据库
        $sth = $pdo->prepare($sql_search);
        $sth->execute(array($value)) or die("数据库错误: " . $sth->errorInfo()[2]);
        return $sth;
    }
    

    /**
     * 获取照片信息，用于填充修改表单
     * @param $img_id   照片id
     * @return array    照片信息查询结果集
     */
    function getMyImgById() {
        //连接数据库
        require_once './Models/DatabaseModel.class.php';
        $db = new DatabaseModel();
        $pdo = $db->connectDatabase();
        //验证Get变量
        $id = !empty($_GET['id']) ? $_GET['id'] : exit();
        //检查是否有cookie
        $cookie_openid = !empty($_COOKIE["openid"]) ? $_COOKIE["openid"] : exit();
        //查询数据库
        $sql_select = "select * from wx_imginfo where img_id = ?";
        $sth = $pdo->prepare($sql_select);
        $sth->execute(array($id)) or die("数据库错误: " . $sth->errorInfo()[2]);
        //验证openid，防止非法访问修改页面
        $arr_imginfo = $sth->fetch(PDO::FETCH_ASSOC);             //查询结果匹配成数组
        if($arr_imginfo['openid'] != $cookie_openid){             //判断是否本人。此处直接读取cookie，因为登录检查方法已经验证了该cookie已经存在
            die("<meta http-equiv='refresh' content='3; url=./index.php' /><h2 align='center'>你不是该作品的主人</h2>");
        }
        return $arr_imginfo;        //返回照片信息数组
    }
    

    /**
     * 更新照片信息
     */
    function updateImg() {
        //连接数据库
        require_once './Models/DatabaseModel.class.php';
        $db = new DatabaseModel();
        $pdo = $db->connectDatabase();
        //验证cookie中是否有openid
        $cookie_openid = !empty($_COOKIE["openid"]) ? $_COOKIE["openid"] : exit();
        //对POST提交的变量进行验证
        $post_name = !empty($_POST['name']) ? $_POST['name'] : exit();
        $post_title = !empty($_POST['title']) ? $_POST['title'] : exit();
        $post_brief = !empty($_POST['brief']) ? $_POST['brief'] : exit();
        $img_id = !empty($_POST['img_id']) ? $_POST['img_id'] : exit();
        $filename = !empty($_POST['filename']) ? $_POST['filename'] : exit();
        //对于提交的表单数据进行特殊字符检查，防止提交HTML标签
        $name = htmlspecialchars($post_name,ENT_QUOTES);                 //转义所有HTML字符，包括英文的单引号、双引号
        $title = htmlspecialchars($post_title,ENT_QUOTES);
        $no_sc_brief = htmlspecialchars($post_brief,ENT_QUOTES);         
        $brief = str_replace("\r\n", "<br>", $no_sc_brief);              //把回车换行符\r\n替换成<br/>
        //进行拥有者验证
        $sql_select = "select * from wx_imginfo where img_id = ?";
        $sth = $pdo->prepare($sql_select);
        $sth->execute(array($img_id)) or die("数据库错误: " . $sth->errorInfo()[2]);
        $arr_imginfo = $sth->fetch(PDO::FETCH_ASSOC);            //查询结果匹配成数组
        if($arr_imginfo['openid'] != $cookie_openid){            //cookie中openid与数据库该照片openid不符，即不是本人请求
            die("<meta http-equiv='refresh' content='3; url=./index.php' /><h2 align='center'>你不是该作品的主人</h2>");
        }
        //更新数据库
        $sql_update= "UPDATE wx_imginfo SET name = ?, title = ?, brief = ?, img_file_name = ? WHERE img_id = ?";
        $sth = $pdo->prepare($sql_update);
        $sth->execute(array($name, $title, $brief, $filename, $img_id)) or die("数据库错误: " . $sth->errorInfo()[2]);
        //跳转到作品详情页
        header("Location: index.php?c=Image&a=detail&id=".$img_id);
    }
}