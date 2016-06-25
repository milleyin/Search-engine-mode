<?php

/**
 * 任务派发管理器
 * 几个要点;
 * 为了保证子任务派发的孙任务能够正确结束,需要自行设计停止子任务的停止方法.子任务中的停止方法再去通知孙任务结束.
 * 为了让信号能正确处理,在子任务的方法中,需要调用pcntl_signal_dispatch()来监听信号.
 * 如果子任务中有循环操作,请把pcntl_signal_dispatch()放到循环中,确保在CPU处理时间中有分到监听操作.
 * 通过Hp方法去监听子任务的运行状态,运行结束会获得返回值false
 * 所有任务都是异步运行的.需要自己使用Hp方法获得任务状态,并及时使用Stop清除任务.
 * Add仅仅是添加任务,需要使用Start方法触发任务.
 * 不管在何种执行方法中,如果任务不存在都会返回-1.添加任务除外,如果任务存在则返回-2.
 * 如果没有使用Opt方法初始化任务派发管理器,执行其他方法都会直接得到-1.
 */
class Mtask {

    private $task, $memory = 1, $kill   = 0, $init   = 1,$file='';
    
    /**
     * 初始化设定
     * @param $obj_key
     * @return boolean
     */
    public function Opt($task_file = '/tmp/lib') {
        //检查文件是否存在
        if(!file_exists($task_file)){
            //尝试创建文件
            $task_array = array();
            if(!file_put_contents($task_file,  serialize($task_array),LOCK_EX)){
                //创建失败
                return false;
            }
        }else{
           //读取内容
           $task_array = unserialize(file_get_contents($task_file));
        }
        
        $this->task = $task_array;
        $this->file = $task_file;
        
        //设定主程序的ID,用于检查子程序是否在运行状态
        $this->memory = ftok($task_file, 't');
        if ($this->memory === false) {
            return false;
        }
        
        //打开这个内存空间
        $this->memory = shm_attach($this->memory);
        $this->init = 0;
        return true;
    }

    /**
     * 添加任务,用于执行.
     * @param type $task_obj  |执行的对象
     * @param type $task_name |任务名
     * @param type $task_start|启动方法
     * @param type $task_stop |停止方法
     * @return boolean        |添加结果
     */
    public function Add(&$task_obj, $task_name, $task_start, $task_stop) {
        if ($this->init) {
            return -1;
        }
        if (isset($this->task[$task_name])) {
            return -2;
        }
        //检查对象
        //任务名可用
        $this->task[$task_name]['obj']   = &$task_obj;
        $this->task[$task_name]['pid']   = 0;
        $this->task[$task_name]['start'] = $task_start;
        $this->task[$task_name]['stop']  = $task_stop;
        $this->task[$task_name]['time']  = 0;

        //注册任务停止方法到信号处理
        pcntl_signal(SIGUSR1, array(&$this, 'TaskStop'));
        
        //将变化写入到文件中
        file_put_contents($this->file,  serialize($this->task),LOCK_EX);

        return true;
    }

    /**
     * 执行任务
     * @param $task_name |要运行的任务名
     * @param $task_params | 输入参数,将会传入目标任务对象start方法的第三个参数.
     * @return boolean
     */
    public function Start($task_name,$task_params=null) {
        if ($this->init) {
            return -1;
        }
        if (!isset($this->task[$task_name])) {
            return -2;
        }
        if ($this->task[$task_name]['time'] == 0) {
            $pid = pcntl_fork();

            if ($pid == -1) {
                //进程启动失败
                return false;
            } elseif ($pid == 0) {
                //标记运行状态
                $cid        = getmypid();
                $this->kill = $task_name;
                $task_obj   = &$this->task[$task_name]['obj'];
                $task_start = $this->task[$task_name]['start'];
                shm_put_var($this->memory, $cid, 'None');
                $task_obj->$task_start($this->memory, $cid,$task_params);
                $this->Stop($task_name);
                exit;
            } else {
                //进程启动成功
                $this->task[$task_name]['pid'] = $pid;
                //将变化写入到文件中
                file_put_contents($this->file,  serialize($this->task),LOCK_EX);
                return true;
            }
        }
    }
    
    /**
     * 停止任务,
     * @param type $task_name
     * @return boolean
     */
    public function Stop($task_name) {
        if ($this->init) {
            return -1;
        }
        if (!isset($this->task[$task_name])) {
            return -2;
        }
        //发送停止命令
        posix_kill($this->task[$task_name]['pid'], SIGUSR1);
        //清除内存MSG
        shm_remove_var($this->memory, $this->task[$task_name]['pid']);
        //清除任务数组
        unset($this->task[$task_name]);
        //将变化写入到文件中
        file_put_contents($this->file,  serialize($this->task),LOCK_EX);
        return true;
    }

    /**
     * 获取任务运行状态
     * 此方法会立即返回结果.
     * 任务运行结束时返回 0;
     * 任务仍在运行时返回 1;
     * @param type $task_name
     * @return int
     */
    public function Hp($task_name) {
        if ($this->init) {
            return -1;
        }
        if (!isset($this->task[$task_name])) {
            return -2;
        }
        if (pcntl_waitpid($this->task[$task_name]['pid'], $status, WNOHANG) == 0) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 获取正在运行中的任务传递回来的消息
     * 默认值是任务初始运行前写入的 None
     * 任务不存在时返回 -1
     * @param  $task_name | 运行的任务名字
     * @return mixed
     */
    public function Msg($task_name) {
        if ($this->init) {
            return -1;
        }
        if (!isset($this->task[$task_name])) {
            return -2;
        }
        $msg = shm_get_var($this->memory, $this->task[$task_name]['pid']);
        return $msg;
    }
    
    /**
     * 获得任务是否已经存在于任务系统
     * @param $task_name
     * @return 
     */
    public function TaskExists($task_name){
        if ($this->init) {
            return -1;
        }
        if (isset($this->task[$task_name])) {
            return true;
        }else{
            return false;
        }
    }

    /**
     * 任务停止功能
     * @return boolean
     */
    public function TaskStop() {
        if ($this->kill !== 0) {
            if (isset($this->task[$this->kill])) {
                $task_obj  = &$this->task[$this->kill]['obj'];
                $task_stop = $this->task[$this->kill]['stop'];
                $task_obj->$task_stop();
                exit;
            }
        }
        return false;
    }

}