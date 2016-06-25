<?php

class Mysql {

    private $db_conn    = null;
    private $db_status  = false;
    private $db_qcache  = null;
    private $db_host    = null;
    private $db_user    = null;
    private $db_pass    = null;
    private $db_name    = null;
    private $db_charset = null;

    public function Opt($db_host, $db_user, $db_pass, $db_name, $db_charset = 'utf8') {
        $this->db_name    = $db_name;
        $this->db_user    = $db_user;
        $this->db_host    = $db_host;
        $this->db_pass    = $db_pass;
        $this->db_charset = $db_charset;
    }

    public function Connect() {
        $this->db_conn = mysql_connect($this->db_host, $this->db_user, $this->db_pass);
        if ($this->db_conn === false) {
            return mysql_error();
        } else {
            mysql_query("set names $this->db_charset", $this->db_conn);
            if (mysql_select_db($this->db_name, $this->db_conn) !== false) {
                $this->db_status = true;
                return true;
            } else {
                return mysql_error();
            }
        }
    }

    public function Reconnect() {
        if (!mysql_ping($this->db_conn)) {
            mysql_close($this->db_conn); //注意：一定要先执行数据库关闭，这是关键 
            $this->Connect();
        }
    }

    public function Byebye() {
        if ($this->db_status) {
            mysql_close($this->db_conn);
        } else {
            return false;
        }
    }

    public function Query($SQL) {
        $this->db_qcache = mysql_query($SQL, $this->db_conn);
        return $this;
    }

    public function Insert($table_name, $datax) {
        //获得keys
        $key_name = array_keys($datax);
        //创建临时语句
        $write0   = "$table_name";
        $write1   = '';
        $write2   = '';
        //首先把数组转换成语句
        foreach ($key_name as $keys) {
            $write1 .= "$keys,";
            if (is_numeric($datax[$keys])) {
                $write2 .= $datax[$keys] . ',';
            } else {
                $write2 .= '\'' . $datax[$keys] . '\',';
            }
        }
        //规范数据
        $write1 = substr($write1, 0, -1);
        $write2 = substr($write2, 0, -1);
        //组建查询语句
        $Query  = "INSERT INTO {$write0}({$write1}) VALUES({$write2})";
        //执行查询
        return $this->Query($Query);
    }

    public function Select($table_name, $cols, $limit = '') {
        //创建临时语句
        $sql_page1 = '';
        $sql_page2 = '';
        //获取表名
        if (strpos($table_name, ',') !== false) {
            $table_name = explode(',', $table_name);
            foreach ($table_name as $table_name_x) {
                //下面这个处理将支持别名模式
                if (strpos($table_name_x, ' ' !== false)) {
                    $table_name_x = explode(' ', $table_name_x);
                    $sql_page1 .= $table_name_x[0] . '.' . $table_name_x[1] . ',';
                } else {
                    $sql_page1 .= $table_name_x . ',';
                }
            }
        } else {
            $sql_page1 .= $table_name . ',';
        }

        //获取cols
        if (strpos($cols, ',') !== false) {
            $cols = explode(',', $table_name);
        }
        foreach ($cols as $col_key_name) {
            //下面这个处理将支持别名模式
            if (strpos($col_key_name, '.' !== false)) {
                $col_key_name = explode(' ', $col_key_name);
                $sql_page2 .= $col_key_name[0] . '.' . $col_key_name[1] . ',';
            } else {
                $sql_page2 .= $col_key_name . ',';
            }
        }
        //预处理
        $sql_page1 = substr($sql_page1, 0, -1);
        $sql_page2 = substr($sql_page2, 0, -1);
        $Query     = "SELECT {$sql_page2} FROM {$sql_page1} {$limit}";
        return $this->Query($Query);
    }

    public function Update($table_name, $update_data, $limit = '') {
        //创建临时语句
        $sql_page   = '';
        //获取全部cols
        $update_key = array_keys($update_data);
        foreach ($update_key as $keys) {
            $sql_page .= "$keys";
            if (is_numeric($update_data[$keys])) {
                $sql_page .= '=' . $update_data[$keys] . ',';
            } else {
                $sql_page .= '=\'' . $update_data[$keys] . '\',';
            }
        }
        //预处理
        $sql_page = substr($sql_page, 0, -1);
        //Query
        $Query    = "UPDATE $table_name SET $sql_page $limit";
        return $this->Query($Query);
    }

    public function Delete($table_name, $limit) {
        //创建临时语句
        $Query = "DELETE FROM $table_name $limit";
        return $this->Query($Query);
    }

    public function Get($key_name = null) {
        if ($this->db_qcache == false) {
            return false;
        }
        $res = mysql_fetch_array($this->db_qcache, MYSQL_ASSOC);
        if (empty($res)) {
            return false;
        } else {
            if ($key_name) {
                return $res[$key_name];
            } else {
                return $res;
            }
        }
    }

    public function GetInsertKey() {
        return mysql_insert_id($this->db_conn);
    }

    public function GetAll() {
        if ($this->db_qcache == false) {
            return false;
        }
        $tmp = array();
        while ($a = $this->Get()) {
            $tmp[] = $a;
        }
        return $tmp;
    }

    public function GetError() {
        return mysql_error();
    }

}

?>