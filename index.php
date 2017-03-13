<?php
//前端请求分发器

$c = !empty($_GET['c']) ? $_GET['c'] : "Index";         //默认载入IndexController这个控制器

require_once "./Controllers/".$c."Controller.class.php";

$controller_name = $c."Controller";				//构建控制器的类名
$ctrl = new $controller_name();                 //可变类

$act = !empty($_GET['a']) ? $_GET['a'] : "index";   //默认调用控制器的index动作
$action = $act."Action";                            //组装动作名
$ctrl->$action();                                   //可变函数