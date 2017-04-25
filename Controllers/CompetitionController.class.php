<?php
class CompetitionController {

    function __construct() {
        
    }


    /**
     * 通知
     */
    function notificationAction() {
        include './Views/notification.html';
    }


    /**
     * 摄影课堂
     */
    function courseAction() {
        include './Views/course.html';
    }
    
}