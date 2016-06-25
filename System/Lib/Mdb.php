<?php
/**
 * 通用数据库操作组件
 * 支持MYSQL和SQLITE数据库
 */
class Mdb {

    private $obj = null;
    private $type = null;
    private $link = null;
    private $init = false;
    private $data = null;
    private $host = null;
    private $user = null;
    private $pass = null;
    private $name = null;
    private $error = null;
    private $charset  = null;

    /**
     * 设置一个数据库工作连接参数
     * @param $type 链接的数据库类型，sqlite或者mysql。
     * @param $host 数据库地址，sqlite时，此处应该填数据库存放的路径。
     * @param $name 数据库名称
     * @param $user 数据库用户，sqlite时，此参数可设置为null。
     * @param $pass 数据库密码，sqlite时，此参数可设置为null。
     * @param $charset 数据库编码。默认为UTF-8。
     */
    public function Opt($type, $host, $name, $user = null, $pass = null, $charset = 'utf8') {
        $this->type = strtolower($type);
        $this->name = $name;
        $this->user = $user;
        $this->host = $host;
        $this->pass = $pass;
        $this->charset  = $charset;
    }

    /**
     * 连接数据库
     * 设定好数据库工作连接参数后，使用此方法才正式连接到数据库。
     * 在需要的地方执行连接再进行数据库操作能够降低程序整体执行时间，并提高执行效率。
     * 注意：在一个程序执行周期内长时间执行与数据库无关的操作可能会被数据库服务器断开连接，此时使用此方法进行重新连接。
     * @return boolean
     */
    public function Connect() {
        //为不同的数据库设置不同的连接方式
        switch ($this->type) {
            //Connect到MYSQL数据库
            case'mysql':
                try {
                    $this->obj = new PDO("mysql:dbname={$this->name};host={$this->host}", $this->user, $this->pass);
                    $this->Query("set names $this->charset");
                    $this->init = true;
                    return true;
                } catch (PDOException $e) {
                    $this->error = $e->getMessage();
                    return false;
                }
                break;
            //Connect到SQLITE数据库
            case'sqlite':
                try {
                    $this->obj = new PDO("sqlite:{$this->host}{$this->name}");
                    $this->Query("PRAGMA encoding = \"{$this->charset}\"");
                    $this->init = true;
                    return true;
                } catch (PDOException $e) {
                    $this->error = $e->getMessage();
                    return false;
                }
            default:
                $this->error = 'Unknow Db Type.';
                return false;
                break;
        }
    }

    /**
     * 执行查询语句
     * @param $Query SQL语句，需要注意的是，SQLITE和MYSQL支持的SQL标准是不一样的。
     * @return boolean
     */
    public function Query($Query) {
        if ($this->obj) {
            $this->data = $this->obj->prepare($Query);
            if (!$this->data) {
                $this->error = 'Check the SQL.';
                return false;
            }
            if ($this->data->execute()) {
                return $this;
            } else {
                $this->error = $this->data->errorInfo();
                return false;
            }
        } else {
            return false;
        }
    }

    /**
     * 获取执行查询语句后的一条结果，然后结果指针会自动向下移动。
     * 假如查询语句查询失败，此方法会直接返回false。
     * @param $key_name 获取结果数组中某键的值，默认为空，既完整获取结果。
     * @param $in_this  结果存放指针，当设置了此变量时，结果会写入此变量，返回值将只能是true或者false。
     * @return boolean  当设置了结果存放指针时，返回值将只能是true或者false。没有设置则返回结果。
     */
    public function GetOne($key_name = null, &$in_this = 'Unknow') {
        if ($this->data == null) {
            return false;
        }
        $res = $this->data->fetch(PDO::FETCH_ASSOC);
        if (empty($res)) {
            return false;
        } else {
            if ($key_name) {
                if ($in_this !== 'Unknow') {
                    $in_this = $res[$key_name];
                    return true;
                } else {
                    return $res[$key_name];
                }
            } else {
                if ($in_this !== 'Unknow') {
                    $in_this = $res;
                    return true;
                } else {
                    return $res;
                }
            }
        }
    }

    /**
     * 获取全部的执行结果
     * @param $key_name 获取结果数组中某键的值，默认为空，既完整获取结果。
     * @param $in_this  结果存放指针，当设置了此变量时，结果会写入此变量，返回值将只能是true或者false。
     * @return boolean  当设置了结果存放指针时，返回值将只能是true或者false。没有设置则返回结果。
     */
    public function GetAll($key_name = null, &$in_this = 'Unknow') {
        if ($this->data == false) {
            return false;
        }
        $tmp = array();
        while ($a = $this->GetOne($key_name)) {
            $tmp[] = $a;
        }
        if ($in_this !== 'Unknow') {
            $in_this = $tmp;
            return true;
        } else {
            return $tmp;
        }
    }

    /**
     * 获取最后一个插入的ID
     * @param $col 指定一个字段，当表中有多个字段使用了自增字段时，可以使用此参数指定获取哪个字段。
     * @return int
     */
    public function GetInertId($col = null) {
        return $this->link->lastInsertId($col);
    }

    /**
     * 获取错误信息
     * @return string
     */
    public function GetError() {
        return $this->error;
    }

}
