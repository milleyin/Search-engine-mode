<?php

/**
 * 基本文件
 */
abstract class Maxfs {

    private $define_obj = array(); //已经被定义的对象名字

    /**
     * 实例化一个对象,并扩展到自身以便使用.
     * @param string $obj_name|对象名字
     * @param string $re_name |重命名
     * @param array $param    |构造参数
     * @return boolean
     */
    final public function Extend($obj_name, $re_name = null, $param = array()) {
        //判断要实例化的对象是否存在
        if (class_exists($obj_name)) {
            //如果没有设置参数二,则使用参数一作为参数二.
            if ($re_name === null) {
                $re_name = $obj_name;
            }
            //检查对象是否已经被实例化了
            if (!$this->IsExtended($re_name)) {
                //如果有参数,进行处理.
                if ($i = count($param)) {
                    $run = '';
                    while ($i > 0) {
                        --$i;
                        $run = ",\$param[$i]" . $run;
                    }
                    $run = substr($run, 1);
                    //带构造参数的实例化,使用eavl动态生成.
                    eval('$class = new ' . $re_name . '(' . $run . ');');
                } else {
                    //不带构造参数的实例化
                    $class = new $obj_name;
                }
                //扩展到当前对象
                $this->$re_name = $class;
                $this->ToExtended($re_name);
                //销毁对象
                unset($class);
                return true;
            } else {
                Error::Loger('错误:这个对象名字已经被使用了.');
                return false;
            }
        } else {
            Error::Loger('错误:初始化对象所需的类库并不存在,请检查类库是否载入.');
            return false;
        }
    }

    /**
     * 清除已经实例化的对象
     * @param string $obj_name | 实例化对象的名字
     * @return boolean
     */
    final public function Unload($obj_name) {
        if ($this->IsExtended($obj_name)) {
            $this->UnExtended($obj_name);
            return true;
        } else {
            Error::Loger('错误:清除对象失败,目标对象不存在.');
            return false;
        }
        return true;
    }

    /**
     * 检查目标对象是否已经被实例化
     * @param string $obj_name
     * @return boolean
     */
    private function IsExtended($obj_name) {
        if (in_array($obj_name, $this->define_obj)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 把实例化的名字加入到内部属性数组中,用于下次检查.
     * @param string $obj_name
     * @return boolean
     */
    private function ToExtended($obj_name) {
        $this->define_obj[] = $obj_name;
        return true;
    }

    /**
     * 清除掉一个扩展
     * @param string $obj_name
     * @return boolean
     */
    private function UnExtended($obj_name) {
        $i = 0;
        foreach ($this->define_obj as $obj) {
            if ($obj == $obj_name) {
                unset($this->define_obj[$i]);
                unset($this->$obj_name);
                return true;
            } else {
                $i++;
            }
        }
        return false;
    }

}
