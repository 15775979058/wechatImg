<?php
class UserModel {

    function __construct() {
        
    }


    /**
     * 作品维护
     * @return resource     作品查询结果集
     */
    function getMyImg() {
        //验证是否有cookie
        $openid = isset($_COOKIE['openid']) ? $_COOKIE['openid'] : exit();
        //查询数据库
        require_once './Models/DatabaseModel.class.php';
        $db = new DatabaseModel();
        $pdo = $db->connectDatabase();
        $sql_query="SELECT * FROM wx_imginfo WHERE openid = ?";
        $sth = $pdo->prepare($sql_query);
        $sth->execute(array($openid)) or die("数据库错误: " . $sth->errorInfo()[2]);
        return $sth;
    }


    /**
     * 投票方法
     * @return string   投票结果。success：投票成功，false:投票失败
     */
    function vote() {
        //对POST变量进行验证
        $openid = isset($_COOKIE['openid']) ? $_COOKIE['openid'] : die("不合法的投票请求");
        $imgid = isset($_POST['img_id']) ? $_POST['img_id'] : die("不合法的投票请求");
        //连接数据库
        require_once './Models/DatabaseModel.class.php';
        $db = new DatabaseModel();
        $pdo = $db->connectDatabase();
        //检查openid的真实性
        $this->checkOpenid($openid, $pdo);
        //查询投票记录
        $sql_query="SELECT * FROM wx_vote WHERE openid = ?";
        $sth = $pdo->prepare($sql_query);
        $sth->execute(array($openid)) or die("数据库错误: " . $sth->errorInfo()[2]);
        $arr_res = $sth->fetchAll();
        //根据查询结果去更新票数
        if(count($arr_res) == 0){        //如果没有查询到记录
            //组装数组
            $arr_votelog = array (
                "imgid"  => $imgid,
                "date" => date("Ymd"),
            );
            $arr_two = Array();     //创建数组
            $arr_two[] = $arr_votelog;      //变成二维数组
            $json_votelog = json_encode($arr_two);      //转换成json
            //向wx_vote表中插入投票记录
            $sql_insert = "INSERT INTO wx_vote (openid, votelog) values( ?, ? )";
            $sth = $pdo->prepare($sql_insert);
            $sth->execute(array($openid, $json_votelog)) or die("数据库错误: " . $sth->errorInfo()[2]);
            //更新wx_imginfo表，把票数加1
            $sql_update = "update wx_imginfo set ticket = ticket + 1 where img_id = ?";
            $sth = $pdo->prepare($sql_update);
            $sth->execute(array($imgid)) or die("数据库错误: " . $sth->errorInfo()[2]);
            return "success";
        }  else {      //查询到记录
            $arr_log = json_decode($arr_res[0]['votelog']);            //json转换成数组，fetchAll()匹配出来的是二维数组
            //判断当天是否已经投票
            foreach($arr_log as $pos => $ticket){ 
                //必须使用$ticket->{'imgid'}这种指针方式去获取数据，因为二位数组经过json_encode和json_decode后已经不再是二维数组了。通过print_r($ticket)得知，数组内元素已变成标准对象了，即第二维变成了标准对象。
                if($ticket->{'imgid'} == $imgid && $ticket->{'date'} == date("Ymd")){       //照片id相同，并且日期相同。重复投票
                    return "false";              //返回，不在往下执行
                }elseif ($ticket->{'imgid'} == $imgid && $ticket->{'date'} != date("Ymd")) {    //日期不同，今天还没有投票
                    //更新wx_imginfo表，票数+1
                    $sql_query = "update wx_imginfo set ticket = ticket + 1 where img_id = ?";
                    $sth = $pdo->prepare($sql_query);
                    $sth->execute(array($imgid)) or die("数据库错误: " . $sth->errorInfo()[2]);
                    //更新wx_vote,更新该openid对应的投票记录，对这个编号的投票日期更新为今天
                    $ticket->{'date'} = date("Ymd");
                    $arr_log[$pos] = $ticket;     //把这个数组更新到log数组中。$arr_log[$pos]不能写成$arr_log['$pos']，如果数组下表加了引号就不是序号下表了，该数组中不存在字符串“1”为下表的元素
                    $json_log = json_encode($arr_log);      //投票记录数组转换成json
                    //更新数据库中的投票记录
                    $sql_update = "update wx_vote set votelog=? where openid =?";
                    $sth = $pdo->prepare($sql_update);
                    $sth->execute(array($json_log, $openid)) or die("数据库错误: " . $sth->errorInfo()[2]);
                    //返回数据
                    return "success";
                }
            }
            //如果执行到这个位置，该人表明没有投过该编号
            //更新wx_imginfo表，票数+1
            $sql_update = "update wx_imginfo set ticket = ticket + 1 where img_id = ?";
            $sth = $pdo->prepare($sql_update);
            $sth->execute(array($imgid)) or die("数据库错误: " . $sth->errorInfo()[2]);
            //向投票记录中加入数据
            $log = array (
                "imgid"  => $imgid,
                "date" => date("Ymd"),
            );
            $arr_log[] = $log;                      //添加到数组中
            $json_votelog = json_encode($arr_log);  //转换成json
            //更新投票记录
            $sql_insert = "update wx_vote set votelog = ? where openid = ?";
            $sth = $pdo->prepare($sql_insert);
            $sth->execute(array($json_votelog, $openid)) or die("数据库错误: " . $sth->errorInfo()[2]);
            //返回成功
            return "success";
        }
    }


    /**
     * 检查openid是否在数据库中存在，防止伪造openid刷票
     * @param $str_openid 微信openid
     * @param $var_link 数据库连接
     */
    function checkOpenid($str_openid, $pdo){
        //查询openid是否存在
        $sql_query="SELECT * FROM wx_userbase WHERE openid = ?";
        $sth = $pdo->prepare($sql_query);
        $sth->execute(array($str_openid)) or die("数据库错误: " . $sth->errorInfo()[2]);
        //计算影响行数
        $arr_res = $sth->fetchAll();
        if(count($arr_res) == 0){       //数组元素个数为0，即openid不存在
            setcookie("openid", "",time() - 3600);          //删除cookie，刷新页面时会触发重新登录，让openid存储在wx_userbase表中
            exit();                                                               //退出脚本
        }
    }
    
}