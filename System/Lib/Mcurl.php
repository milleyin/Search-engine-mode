<?php

/**
 * 这个库可以让CURL的多线程下载变得更加简单.
 */
class Mcurl {

    private $curl_opt = array(
        CURLOPT_REFERER => '',
        CURLOPT_TIMEOUT => 30,
        CURLOPT_COOKIEJAR => '/tmp/http.cookies.tmp',
        CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.4 (KHTML, like Gecko) Chrome/22.0.1229.94 Safari/537.4',
        CURLOPT_CONNECTTIMEOUT => 3,
        CURLOPT_FOLLOWLOCATION => 1,
        CURLOPT_AUTOREFERER => 1,
        CURLOPT_MAXREDIRS => 3,
        CURLOPT_RETURNTRANSFER => 1,
    );

    /**
     *
     * @param type $key
     * @param type $val
     */
    public function Opt($key, $val) {
        $this->curl_opt[$key] = $val;
        return true;
    }

    public function Get($urls,$encode='UTF-8') {
        $mh = curl_multi_init();
        $ac = true;
        $ch = array();
        $da = array();
        //创建执行参数
        foreach ($urls as $i => $url) {
            $ch[$i] = curl_init($url);
            foreach ($this->curl_opt as $row => $key) {
                curl_setopt($ch[$i], $row, $key);
            }
            curl_multi_add_handle($mh, $ch[$i]);
        }
        //执行批量抓取
        while ($ac) {
            curl_multi_exec($mh, $ac);
            usleep(3000);
        }
        //抓取接收,回收结果.
        foreach ($urls as $i => $url) {
            $da[$url] = $this->converstr(curl_multi_getcontent($ch[$i]),$encode);
            curl_multi_remove_handle($mh, $ch[$i]);
            curl_close($ch[$i]);
        }
        //关闭资源
        curl_multi_close($mh);
        return $da;
    }

    private function converstr($string,$encode) {
        $encoding = mb_detect_encoding($string, array('ASCII', 'UTF-8', 'GB2312', 'GBK', 'BIG5'));
        //return iconv($encoding,'utf-8//IGNORE',$string);
        return mb_convert_encoding($string, $encode, $encoding);
    }

}