<?php

class Mxlog {
    
    private $path;
    
    final public function opt($path){
        $this->path = $path;
        return true;
    }
    
    final public function Write($msg){
        $time = (microtime(true) * 1000);
        $t1 = substr($time, 0, 10);
        $t2 = substr($time, 10, 3);
        $t3 = memory_get_usage(TRUE);
        $msg = '[' . date('Y-m-d h:i:s', $t1) . "][{$t2}][{$t3}] {$msg}";
        file_put_contents($this->path, $msg."\r\n", FILE_APPEND);
        return true;
    }
}

?>
