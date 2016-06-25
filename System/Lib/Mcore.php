<?php

class Mcore {

    private $thread_model = 1;
    private $thread_queue = 0;
    private $thread_torun = false;
    private $thread_key   = '';
    private $mem_key      = 0;
    private $mem_res      = null;

    /**
     * 初始化函数
     * 检查支持状况并将控制参数写入.
     * 模式说明:
     *
     * 1,全速模式:一次性执行所有进程,全部进程执行完毕返回执行结果.
     *
     * 2,并行模式:扩展模式,可以设置并行运行的进程组数量,进程组执行完毕后才会执行下
     *   一组进程.全部进程执行完毕返回执行结果.此模式适用于某些需要进度同步的场合.
     *
     * 3,队列模式:队列执行进程,会有一个设定长度的队列用于进程执行,执行完毕的进程会弹出
     *   队列并填入下一个进程,全部进程执行完毕返回执行结果.此模式适用于服务处理.
     *
     * @param string $path_pid  | 进程通信文件存放地址
     * @param int $thread_model | 线程运行模式(0=>全速模式|1=>并行模式|2=>队列模式)
     * @param int $thread_limit | 容量限制,设置为0的时候,无视$thread_model参数,强制$thread_model为0.
     */
    public function Opt($key = '/tmp/', $thread_model = 0, $thread_queue = 0) {
        //检查需求函数是否可用
        if (function_exists('pcntl_fork')) {
            //pcntl函数库可用.支持检测通过.
            $this->thread_torun = true;
            $this->thread_model = $thread_model;
            $this->thread_queue = $thread_queue;
            $this->thread_key   = $key;

            //设定主进程的ID,用于检查子进程是否在运行状态
            if (!$this->mem_key = ftok($key, 't')) {
                return false;
            }
            //打开这个内存空间
            $this->mem_res = msg_get_queue($this->mem_key);
            if (msg_queue_exists($this->mem_key)) {
                $this->mem_res = msg_get_queue($this->mem_key);
                msg_remove_queue($this->mem_res);
            }
            $this->mem_res = msg_get_queue($this->mem_key);

            if ($thread_queue == 0) {
                $this->thread_model = 0;
            }
        } else {
            return false;
        }
    }

    public function Run(&$obj, $mothod, $arg) {

        //检查是否opt了
        if (!$this->thread_torun) {
            return false;
        }
        //检查目标方法是否存在
        if (!method_exists($obj, $mothod)) {
            return false;
        }

        switch ($this->thread_model) {
            case 0:
                $res = $this->ModelBlock($obj, $mothod, $arg);
                break;
            case 1:
                break;
            case 2:
                break;
        }
        //移除队列并发回运行结果.
        msg_remove_queue($this->mem_res);
        return $res;
    }

    /**
     * 全速模式控制方法
     * 1、$tmpfile 判断子进程文件是否存在，存在则子进程执行完毕，并读取内容
     * 2、$data收集子进程运行结果及数据，并用于最终返回
     * 3、删除子进程文件
     * 4、轮询一次0.03秒，直到所有子进程执行完毕，清理子进程资源
     * @param  string|array $arg 用于对应每个子进程的ID
     * @return array  返回   array([子进程序列]=>[子进程执行结果]);
     */
    private function ModelBlock(&$obj, $mothod, $arg) {

        $pids = array();

        if (is_array($arg)) {
            $i = 0;
            foreach ($arg as $key => $val) {
                $spawns[$i] = $key;
                $i++;
                $pids[]     = $this->xen($obj, $mothod, $key, $val);
            }
            $total      = $i;
        } elseif ($total      = intval($arg)) {
            for ($i = 0; $i < $total; $i++) {
                $pids[] = $this->xen($obj, $mothod, $i);
            }
        }

        //等待数据
        /*
          while(true) {
          $a = msg_stat_queue($this->mem_res);
          if($a['msg_qnum'] >= $total){
          //进程组执行完毕,满足条件.
          for($i=0;$i<$total;$i++){
          //接收运行结果
          msg_receive($this->mem_res, 0, $message_type, 1024, $message, true, MSG_IPC_NOWAIT);
          $res[] = $message;
          }
          return $res;
          }else{
          //进程组仍然在运行中
          usleep(30000);
          }
          }
         */
        foreach ($pids as $pid) {
            while (true) {
                if (pcntl_waitpid($pid, $status, WNOHANG) == 0) {
                    usleep(30000);
                } else {
                    break;
                }
            }
        }
        return true;
    }

    private function ModelGroup() {
        
    }

    private function ModelQueue() {
        
    }

    /**
     * 子进程执行方法
     * 1、pcntl_fork 生成子进程
     * 2、file_put_contents 将'$obj->__fork($val)'的执行结果存入特定序列命名的文本
     * @param object $obj   待执行的对象
     * @param object $i     子进程的序列ID，以便于返回对应每个子进程数据
     * @param object $param 用于输入对象$obj方法'__fork'执行参数
     */
    private function xen($obj, $mothod, $i, $param = null) {
        $x = pcntl_fork();

        if ($x === 0) {
            $cid = getmypid();
            $msg = $obj->$mothod($i, $cid, $param);
            //msg_send($this->mem_res, 1, $msg);
            exit;
        }
        //父进程必须得到子进程的PID
        return $x;
    }

    /**
     * 切西瓜
     * 用于线程分配,把需要处理的任务切成制定数量的数组集合
     * @param type $array
     * @param type $nums
     */
    private function ThreadCuter($array, $nums) {
        //创建结果
        $res = array();
        //获取数量
        $numx = count($array);
        //需要切成多少份
        $cuts = ceil($numx / $nums);
        //组装
        for ($a = 0; $a < $cuts; $a++) {
            for ($b = 0; $b < $nums; $b++) {
                if (isset($array[($a * $nums + $b)])) {
                    $res[$a][$b] = $array[($a * $nums + $b)];
                }
            }
        }
        return $res;
    }

}