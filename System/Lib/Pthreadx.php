<?php
/**
 * 父子进程控制器基类,使用多进程实现多路处理,模拟多线程.
 */
class PThreadx {

    private $runtime_init = false;//初始化属性,没有进行初始化则不能运行任何子进程.
    private $runtime_rund = false;//子进程运行属性,用于检查子进程是否在运行中.
    private $runtime_pids = 0;//运行中的子进程PID,主要用于杀进程.
    private $mem_key = 0;
    private $mem_res = null;
    private $mem_map = array(
        'Rund' => 1,
        'Pids' => 2,
        'Msgs' => 3
    );

    final public function Opt($obj_key = '/tmp/lib') {
        //设定主程序的ID,用于检查子程序是否在运行状态
        $this->mem_key = ftok($obj_key, 't');
        if($this->mem_key===false){
            return false;
        }
        //打开这个内存空间
        $this->mem_res = shm_attach($this->mem_key);
        //获取当前运行状态.
        if($this->Hp()){
            return false;
        }

        $this->runtime_init = true;
        return true;
    }

    final public function Start(&$obj, $obj_index, $param = null) {
        if(!$this->runtime_init){
            return false;
        }

        $pid = pcntl_fork();

        if($pid===-1){
            return false;
        }elseif ($pid === 0) {
            $this->runtime_rund = true;
            shm_put_var($this->mem_res, $this->mem_map['Rund'], true);
            //执行工作,会将当前的共享内存资源句柄发送到待执行的对象内部,第二个参数是用于消息传递的的MEM INT.第三个是执行用参数.
            $obj->$obj_index($this->mem_res,$this->mem_map['Msgs'],$param);
            shm_remove($this->mem_res);
            exit;
        } else {
            //将子程序PID写入到内存区
            $this->runtime_pids = $pid;
            shm_put_var($this->mem_res, $this->mem_map['Pids'], $this->runtime_pids);
            usleep(30000);
            return true;
        }
    }

    final public function Stop() {
        if(!$this->runtime_init){
            return false;
        }
        //停止程序
        if($this->runtime_pids===0){
            return false;
        }else{
            posix_kill($this->runtime_pids, SIGTERM);
        }

        //清除内存数据区
        shm_remove($this->mem_res);
        return true;
    }

    final public function Msg() {
        if(!$this->runtime_init){
            return false;
        }
        return shm_get_var($this->mem_res, $this->mem_map['Msgs']);
    }

    final public function Hp(){
        if($this->runtime_pids==0){
            return false;
        }
        //获取当前运行状态.
        if (pcntl_waitpid($this->runtime_pids, $status, WNOHANG)==0) {
            return true;
        } else {
            return false;
        }
    }
}