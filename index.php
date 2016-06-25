<?php
if(isset($_SERVER['HTTP_HOST'])){
    //CGI模式执行CGI专用处理模块。
    include('CGI.php');
}else{
    //CLI模式执行CLI专用处理模块。
    include('CLI.php');
}
