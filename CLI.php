<?php

######################################
include_once('Commom.php');
date_default_timezone_set('Asia/Shanghai');
$cgi = new Maxfs_CLI;
$cgi->Main();
######################################

class Maxfs_CLI {

    private $runtime_rund = false;
    private $mem_key      = 0;
    private $mem_res      = null;

    public function Main() {
        //初始化预处理过程
        $this->Initer();
        //获得参数
        $app_name   = $_SERVER['argv'][1];
        $app_action = $_SERVER['argv'][2];
        //加载文件
        $this->Loader($app_name);
        //运行程序
        $this->Runer($app_action);
    }

    private function Initer() {
        //基本运行常量
        define('MAXFS', TRUE);
        define('RunTime', microtime(true));
        define('PathRoot', str_replace('\\', '/', dirname(__file__) . '/'));
        define('RunModel', 'CLI');
        //系统运行常量
        define('PathSys', PathRoot . 'System/');
        define('PathApplication', PathRoot . 'Application/');
        define('PathSysKernel', PathSys . 'Kernel/');
        define('PathSysLog', PathSys . 'Log/');
        define('PathSysLib', PathSys . 'Lib/');
        define('PathSysConfig', PathSys . 'Config/');
        //管道通信常量
        define('MemMapRun', 1);
        define('MemMapPid', 2);
        define('MemMapMsg', 3);
        //导入核心文件
        include_once (PathSysKernel . 'Error.php');
        include_once (PathSysKernel . 'Maxfs.php');
        include_once (PathSysKernel . 'Mtask.php');
        include_once (PathSysConfig . 'Config.php');
        //初始化错误控制
        Error::Init(PathSysLog, 'Main_CLI.log', MF_CLI_LOG, MF_CLI_PRI);
        return true;
    }

    private function Loader($appdir) {
        //创建路径
        $app_main_path = PathApplication . $appdir . '/Source/Main.php';
        $app_conf_path = PathApplication . $appdir . '/Config.php';
        $app_tend_path = PathApplication . $appdir . '/Extend.php';
        //检查目标文件是否存在
        if (is_file($app_main_path)) {
            //载入目标文件
            include_once($app_conf_path);
            include_once($app_tend_path);
            include_once($app_main_path);

            //定义程序常量
            define('PathAppRoot', PathApplication . $appdir . '/');
            define('PathAppLib', PathAppRoot . 'Lib/');
            define('PathAppSource', PathAppRoot . 'Source/');
            define('PathAppStatic', PathAppRoot . 'Static/');
            define('PathAppData', PathAppRoot . 'Data/');
            define('PathAppLog', PathAppRoot . 'Log/');

            //设定主程序的ID,用于检查子程序是否在运行状态
            $this->mem_key = ftok($app_main_path, 't');
            //打开这个内存空间
            $this->mem_res = shm_attach($this->mem_key);
            //获取当前运行状态.
            if (shm_has_var($this->mem_res, MemMapRun)) {
                $this->runtime_rund = true;
            } else {
                $this->runtime_rund = false;
            }
            //返回运行参数
            return true;
        }
        exit('Loader Error => File Not Found');
    }

    private function Runer($app_action) {

        switch ($app_action) {
            //运行程序
            case 'start':
                if ($this->runtime_rund) {
                    Error::Loger('启动失败,程序[' . AppName . ']正在运行,只能运行一个程序.');
                } else {
                    shm_put_var($this->mem_res, MemMapRun, true);
                    $pid = pcntl_fork();

                    if ($pid == -1) {
                        shm_remove($this->mem_res);
                        Error::Loger('程序[' . AppName . ']启动失败,无法开启子进程.');
                        exit;
                    } elseif ($pid == 0) {
                        $app_action = strtolower($app_action);
                        $app        = new App;
                        $mothods    = get_class_methods($app);
                        if (in_array('Init', $mothods)) {
                            $app->Init();
                        }
                        $app->Main($this->mem_res);
                        shm_remove($this->mem_res);
                        exit;
                    } else {
                        //将子程序PID写入到内存区
                        shm_put_var($this->mem_res, MemMapPid, $pid);
                        //父程序
                        Error::Loger('程序[' . AppName . ']成功启动' . $pid);
                        exit;
                    }
                }
                break;
            case 'stop':
                if ($this->runtime_rund) {
                    $pid = shm_get_var($this->mem_res, MemMapRun);
                    posix_kill($pid, SIGTERM);
                    //清除内存数据区
                    shm_remove($this->mem_res);
                    Error::Loger('程序[' . AppName . ']的主进程停止成功。');
                } else {
                    Error::Loger('程序[' . AppName . ']的主进程已经停止，STOP操作失败。');
                }
                break;
            case 'status':
                if ($this->runtime_rund) {
                    Error::Loger('程序[' . AppName . ']正在运行.');
                    Nprint(shm_get_var($this->mem_res, MemMapMsg));
                } else {
                    Error::Loger('程序[' . AppName . ']已经停止.');
                }
                break;
            default :
                echo 'Unkown Action';
                Error::Loger('Unkown Action.');
                break;
        }
        exit;
    }

}
