<?php
class CompetitionController {

    function __construct() {
        
    }


    /**
     * 通知
     */
    function notificationAction() {
        require './Views/notification.html';
    }


    /**
     * 摄影课堂
     */
    function courseAction() {
        require './Views/course.html';
    }
    
}