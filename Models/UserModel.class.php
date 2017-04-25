<?php
class UserModel {

    function __construct() {
        
    }

    
    //作品维护
    function getMyImg() {
        //连接数据库
        require_once './Models/DatabaseModel.class.php';
        $db = new DatabaseModel();
        $link = $db->connectDatabase();
        //查询wx_imginfo表
        $sql_picinfo="SELECT * FROM wx_imginfo WHERE openid = '$_COOKIE[openid]'";
        $ret_sqldata = mysql_query($sql_picinfo, $link) or die("数据库错误: " . mysql_error($link));
        return $ret_sqldata;
    }
    
    
    //投票方法
    function vote() {
        //对POST变量进行验证
        $openid = isset($_POST['openid']) ? $_POST['openid'] : die("不合法的投票请求");
        $imgid = isset($_POST['img_id']) ? $_POST['img_id'] : die("不合法的投票请求");
        //连接数据库
        require_once './Models/DatabaseModel.class.php';
        $db = new DatabaseModel();
        $link = $db->connectDatabase();
        $sql_select="SELECT * FROM wx_vote WHERE openid = '".$openid."'";
        $ret_sqldata = mysql_query($sql_select, $link) or die("数据库错误: " . mysql_error($link));
        //根据查询结果去更新票数
        if(mysql_num_rows($ret_sqldata)==0){        //如果没有查询到记录
            //组装数组
            $arr_votelog = array (
                "imgid"  => $imgid,
                "date" => date("Ymd"),
            );
            $arr_two = Array();     //创建数组
            $arr_two[] = $arr_votelog;      //变成二维数组
            //转换成json
            $json_votelog = json_encode($arr_two);
            //向wx_vote表中插入投票记录
            $sql_ins_vl = "INSERT INTO wx_vote (openid, votelog) values('".$openid."','".$json_votelog."')";
            $ret_vl_sqldata = mysql_query($sql_ins_vl, $link) or die("数据库错误: " . mysql_error($link));
            //更新wx_imginfo表，把票数加1
            $sql_upd_ii = "update wx_imginfo set ticket=ticket+1 where img_id='".$imgid."'";
            $ret_ii_sqldata = mysql_query($sql_upd_ii, $link) or die("数据库错误: " . mysql_error($link));
            return "success";
        }  else {      //查询到记录
            $arr_votelog = mysql_fetch_array($ret_sqldata);             //转换成数组
            $arr_log = json_decode($arr_votelog['votelog']);            //json转换成数组
            //遍历数组，查询是否今天已经投过这个照片
            foreach($arr_log as $pos => $ticket){ 
                //必须使用$ticket->{'imgid'}这种指针方式去获取数据，因为二位数组经过json_encode和json_decode后已经不再是二维数组了。通过print_r($ticket)得知，数组内元素已变成标准对象了，即第二维变成了标准对象。
                if($ticket->{'imgid'} == $imgid && $ticket->{'date'} == date("Ymd")){       //照片id相同，并且日期相同。重复投票
                    return "false";              //返回，不在往下执行
                }elseif ($ticket->{'imgid'} == $imgid && $ticket->{'date'} != date("Ymd")) {    //日期不同，今天还没有投票
                    //更新wx_imginfo表，票数+1
                    $sql_upd_ii = "update wx_imginfo set ticket=ticket+1 where img_id='".$imgid."'";
                    $ret_ii_sqldata = mysql_query($sql_upd_ii, $link) or die("数据库错误: " . mysql_error($link));
                    //更新wx_vote,更新该openid对应的投票记录，对这个编号的投票日期更新为今天
                    $ticket->{'date'} = date("Ymd");
                    $arr_log[$pos] = $ticket;     //把这个数组更新到log数组中。$arr_log[$pos]不能写成$arr_log['$pos']，如果数组下表加了引号就不是序号下表了，该数组中不存在字符串“1”为下表的元素
                    //把投票记录会写到wx_votelog表中
                    $json_log = json_encode($arr_log);
                    //更新wx_vote表中的投票记录
                    $sql_ins_vl = "update wx_vote set votelog='".$json_log."' where openid ='".$openid."'";
                    $ret_data = mysql_query($sql_ins_vl, $link) or die("数据库错误: " . mysql_error($link));
                    //返回数据
                    return "success";
                }
            }
            //如果执行到这个位置，表明没有投过该编号
            //更新wx_imginfo表，票数+1
            $sql_upd_ii = "update wx_imginfo set ticket=ticket+1 where img_id='".$imgid."'";
            $ret_ii_sqldata = mysql_query($sql_upd_ii, $link) or die("数据库错误: " . mysql_error($link));
            //向投票记录中加入数据
            $log = array (
                "imgid"  => $imgid,
                "date" => date("Ymd"),
            );
            $arr_log[] = $log;                      //添加到数组中
            $json_votelog = json_encode($arr_log);  //转换成json
            //更新投票记录
            $sql_vote = "update wx_vote set votelog='".$json_votelog."' where openid='".$openid."'";
            $sqldata = mysql_query($sql_vote, $link) or die("数据库错误: " . mysql_error($link));
            //返回成功
            return "success";
        }
    }
    
}