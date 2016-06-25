<?php

/**
 * 错误控制器
 * 用于处理运行中发生的各种错误
 */
class Error {

    static $cfg_path, $cfg_name, $cfg_display, $cfg_log2file, $define_err;

    final static public function Init($path, $name, $display = true, $log2file = true) {
        //error_reporting(E_ALL);
        error_reporting(0);
        register_shutdown_function(array('Error', 'Fatal'));
        set_error_handler(array('Error', 'Corer'));
        self::$cfg_path = $path;
        self::$cfg_name = $name;
        self::$cfg_display = $display;
        self::$cfg_log2file = $log2file;
        self::$define_err = array();
        return true;
    }

    final static public function Loger($msg) {
        $x = debug_backtrace();
        self::Write("[{$x[0]['file']}][{$x[0]['line']}]{$msg}");
        return true;
    }

    final static public function Fatal() {
        if ($e = error_get_last()) {
            self::Corer($e['type'], '[Maxfs::Shut Down!]'.$e['message'], $e['file'], $e['line']);
        }
        self::end();
        exit;
    }

    final static public function Write($msg) {
        $time = (microtime(true) * 1000);
        $t1 = substr($time, 0, 10);
        $t2 = substr($time, 10, 3);
        $t3 = memory_get_usage(TRUE);
        $msg = '[' . date('Y-m-d h:i:s', $t1) . "][{$t2}][{$t3}] {$msg}";
        self::$define_err[] = $msg;
        return true;
    }

    final static public function Corer($errno, $errstr, $errfile, $errline) {
        switch ($errno) {
            case E_ERROR:
            case E_PARSE:
            case E_CORE_ERROR:
            case E_COMPILE_ERROR:
            case E_USER_ERROR:
                $errorStr = "[{$errfile}][{$errline}]{$errstr}";
                self::Write($errorStr);
                break;
            case E_STRICT:
            case E_USER_WARNING:
            case E_USER_NOTICE:
            default:
                $errorStr = "[{$errfile}][{$errline}]{$errstr}";
                self::Write($errorStr);
                break;
        }
        return true;
    }

    final static public function End() {
        if (self::$define_err) {
            foreach (self::$define_err as $e) {
                if (RunModel == 'CLI') {
                    if (self::$cfg_display) {
                        Nprint($e);
                    }
                    if (self::$cfg_log2file) {
                        file_put_contents(self::$cfg_path . self::$cfg_name, $e."\r\n", FILE_APPEND);
                    }
                } elseif (RunModel == 'CGI') {
                    if (self::$cfg_display) {
                        Bprint($e);
                    }
                    if (self::$cfg_log2file) {
                        file_put_contents(self::$cfg_path . self::$cfg_name, $e."\r\n", FILE_APPEND);
                    }
                }
            }
        }
    }

}

?>
