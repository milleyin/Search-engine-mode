<?php
/**
 * 一个非常简单地模板解析组件
 * 支持直接的PHP标签嵌入
 */
class Mview {
    private $path,$var;

    /**
     * 设置模板文件夹路径
     * @param type $path
     * @return boolean
     */
    public function Opt($path){
        $this->path = $path;
        return true;
    }

    /**
     * 添加模板变量
     * @param $key 模板中使用的变量名
     * @param $val 模板中变量的值
     * @return boolean
     */
    public function Add($key,$val){
        $this->var[$key] = $val;
        return true;
    }

    /**
     * 解析模板或者将解析模板产生的内容获取
     * @param $file    目标模板文件路径
     * @param $return  是否获取解析内容
     * @return boolean or string
     */
    public function Display($file,$return=false){
        if(count($this->var)){extract($this->var);}
        ob_start();
        ob_implicit_flush(0);
        include($this->path.''.$file);
        $content = ob_get_clean();
        if($return){
            return $content;
        }else{
            echo $content;
            return true;
        }
    }
}
