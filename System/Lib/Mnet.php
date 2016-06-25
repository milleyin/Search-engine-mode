<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Mnet是一个支持数据加密验证的网络通信库。
 * 可同时充当客户端和服务端。
 *
 */
class Mnet {

    private $rsp;
    private $key;
    private $content;

    public function Opt($key = 123) {
        $this->key = $key;
    }

    public function Data($content = null) {
        if ($content) {
            //有内容的情况下，将内容压入预发射文本。
            $this->content = $this->xcrypt(serialize($content),  $this->key,'E');
            return true;
        } else {
            //没有内容则是读取内容。
            $this->content = unserialize($this->xcrypt($_POST['MNET_TRANSP'], $this->key,'D'));
            return $this->content;
        }
    }

    public function Send($url) {
        $opt = array(
		'http' => array(
		'method'  => 'POST',
		'header'  => "Content-type:application/x-www-form-urlencoded",
		'content' => http_build_query(array('MNET_TRANSP'=>$this->content)),
	));
        $return = file_get_contents($url, false, stream_context_create($opt));
        if($return){
            $this->rsp = $return;
            return true;
        }else{
            return false;
        }
    }

    public function GetRsp(){
        return unserialize($this->xcrypt($this->rsp,$this->key,'D'));
    }

    public function Recv($data=null) {
        if($_POST['MNET_TRANSP']){
            $post = $this->Data();
            if($post&&$data!==null){
                echo $this->xcrypt(serialize($data),  $this->key,'E');
            }
        }
        return false;
    }

    public function xcrypt($string, $key = '',$operation) {
        $key = md5($key);
        $key_length = strlen($key);
        $string = $operation == 'D' ? base64_decode($string) : substr(md5($string . $key), 0, 8) . $string;
        $string_length = strlen($string);
        $rndkey = $box = array();
        $result = '';
        for ($i = 0; $i <= 255; $i++) {
            $rndkey[$i] = ord($key[$i % $key_length]);
            $box[$i] = $i;
        }
        for ($j = $i = 0; $i < 256; $i++) {
            $j = ($j + $box[$i] + $rndkey[$i]) % 256;
            $tmp = $box[$i];
            $box[$i] = $box[$j];
            $box[$j] = $tmp;
        }
        for ($a = $j = $i = 0; $i < $string_length; $i++) {
            $a = ($a + 1) % 256;
            $j = ($j + $box[$a]) % 256;
            $tmp = $box[$a];
            $box[$a] = $box[$j];
            $box[$j] = $tmp;
            $result.=chr(ord($string[$i]) ^ ($box[($box[$a] + $box[$j]) % 256]));
        }
        if ($operation == 'D') {
            if (substr($result, 0, 8) == substr(md5(substr($result, 8) . $key), 0, 8)) {
                return substr($result, 8);
            } else {
                return'';
            }
        } else {
            return str_replace('=', '', base64_encode($result));
        }
    }

}

?>
