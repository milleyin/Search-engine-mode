<?php

class App extends Maxfs {

    public function Init() {

        //重新初始化错误控制
        Error::Init(PathAppLog, 'Index.runlog', false, true);

        //载入所需文件
        Import('#Mxlog');
        Import('#Pthreadx');
        Import('$Lib.Spider');
        Import('#Mcurl');

        //初始化模块
        $this->Extend('Pthreadx', 'spider_pt');
        $this->Extend('Mxlog', 'log');
        $this->Extend('Lib_Spider', 'sp');
        $this->Extend('Mcurl', 'mc');

        //初始化操作
        $this->spider_pt->Opt(PathAppSource . 'Main.php');
        $this->log->Opt(PathAppLog . 'Index.log');
        $this->log->Write('程序启动成功');
        return true;
    }

    public function Main($mem_res) {
        //执行.
        if ($this->spider_pt->Start($this->sp, 'run')) {
            Error::Loger('蜘蛛程序启动成功');
            while($this->spider_pt->Hp()){
                shm_put_var($mem_res, MemMapMsg, '程序正在运行中,现在时间是:'.date('Y-m-d H:i:s',time()));
                sleep(1);
            }
        } else {
            Error::Loger('蜘蛛程序启动失败,无法分配到内存.');
        }
        /*
          Nprint(date('H:i:s'), time());
          $urls = array(
          'http://www.maxfs.org/k.html', 'http://www.maxfs.org/k2.html',
          );
          $data = $this->mc->get($urls);
          file_put_contents(PathAppData . 'get.txt', serialize($data));
          Nprint(date('H:i:s'), time());
          //无限执行吧
          /*
          while(true){
          Error::Loger('正在运行中.');
          shm_put_var($mem_res, MemMapMsg, '程序正在运行中,现在时间是:'.date('Y-m-d H:i:s',time()));
          sleep(1);
          }
         */
        return true;
    }

}