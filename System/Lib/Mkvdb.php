<?php

/**
 * Key/Value 键值数据库
 * 简单实用的键值数据库，基于磁盘。
 */
class Mkvdb {

    private $path; //存储读写位置
    private $deep; //存储分级深度
    private $stat = 1; //初始化状态

    /**
     * 数据库设置
     * @param string $path|设置读写位置
     * @param string $deep|设置分级深度
     * @return bool|会检查一下目标路径是否存在,不存在则返回FALSE.
     */

    public function Opt($path, $deep = 2) {
        if (is_dir($path)) {
            $this->path = $path;
            $this->deep = $deep;
            $this->stat = 0;
        } else {
            $this->stat = 1;
            return false;
        }
    }

    /**
     * 写入数据
     * @param $content
     * @return boolean
     */
    public function Write($content, $key = null) {
        //初始化检查
        if ($this->stat) {
            return false;
        }

        if ($key == null) {
            //创建KEY
            $key = md5(time() . md5(substr($content, 0, 10)));
            //创建路径
            $path = $this->AutoPath($key, 1);
        } else {
            //创建路径
            $path = $this->AutoPath($key, 0);
        }

        //写入文件
        $fp = fopen($path, 'wb');
        flock($fp, LOCK_EX);
        fwrite($fp, $content);
        flock($fp, LOCK_UN);
        fclose($fp);

        //反馈
        return $key;
    }

    /**
     * 读取记录
     * @param $key
     */
    public function Read($key) {
        //初始化检查
        if ($this->stat) {
            return false;
        }

        //创建路径
        $path = $this->AutoPath($key, 0);

        //读取文件
        $fp = fopen($path, 'rb');
        $res = fread($fp, filesize($path));
        fclose($fp);
        return $res;
    }

    /**
     * 自动路径
     * @param $key  |用于创建路径的KEY.
     * @param $model|创建模式,是否检查路径是否存在,是否自动创建目标路径.
     */
    private function AutoPath($key, $model = 1) {
        $extd = substr($key, 0, $this->deep);
        $path = "{$this->path}{$extd}/";
        if ($model) {
            if (!is_dir($path)) {
                mkdir($path, 0777);
                chmod($path, 0777);
            }
        }
        return $path . $key . '.kvdb';
    }

}
