<?php
class CompetitionController {

    function __construct() {
        
    }

    //通知
    function notificationAction() {
        require_once './Models/CompetitionModel.class.php';
        include './Views/notification.html';
    }
    
    //摄影课堂
    function courseAction() {
        require_once './Models/CompetitionModel.class.php';
        include './Views/course.html';
    }
    
}