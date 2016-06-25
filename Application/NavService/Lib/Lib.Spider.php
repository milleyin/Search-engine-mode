<?php

class Lib_Spider extends Maxfs {

    public function Run() {

        //载入所需文件
        Import('#Mxlog');
        Import('#Mcore');
        //Import('#Mcurl');
        Import('$Lib.Spider.Db');

        //初始化模块
        $this->Extend('Mxlog', 'log');
        $this->Extend('Mcore', 'mcc');
        //$this->Extend('Mcurl', 'mcu');
        $this->Extend('Lib_Spider_Db', 'lsd');

        //初始化操作
        $this->log->opt(PathAppLog . 'Spider.log');
        $this->mcc->opt(PathAppLib . 'Lib.Spider.php');
        $this->lsd->opt();

        //运行采集
        $this->CollectMain();
        return true;
    }

    public function CollectMain() {
        //获取所有配置
        $site_cfgs = $this->lsd->ReadAllWebSite();
        if (count($site_cfgs)) {
            $site_cfgs_cut = $this->CollectCut($site_cfgs, SpiderThreadLimit);
            foreach ($site_cfgs_cut as $site_cfg) {
                $this->mcc->Run($this, 'CollectSite', $site_cfg);
            }
        } else {
            $this->log->Write('没有获得站点列表。');
        }
        $this->log->Write('运行结束。');
        return true;
    }

    public function CollectSite($i, $cid, $param) {
        $this->log->Write("进程号:{$cid}启动,开始抓取:[{$param['wid']}],根位置:[{$param['base']}].");
        $this->CollectProgress($param['wid'], $param['base'], $param['url'], $param['deep'], 0);
        $this->lsd->SiteFresh($param['wid']);
        $this->log->Write("进程号:{$cid}采集结束.");
        return true;
    }

    private function CollectCut($array, $nums) {
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

    private function CollectProgress($wid, $base, $url, $deep, $now) {
        //判断是否终止采集
        if ($deep >= $now) {
            $this->log->Write("地址[{$url}]开始采集.");
            //检查该连接是否需要采集
            $check = $this->lsd->CheckURL($url);
            if ($check) {
                //$this->log->Write("连接需要采集[{$url}]");
                //没有超过最大采集深度,采集开始,下载页面，.
                $html = $this->CollectDown($url, $base, 10, 5);
                //内容检测
                if ($lenx = strlen($html) < 100) {
                    $this->log->Write($url . '数据长度不符合标准[' . $lenx . '].');
                    return false;
                }
                //修正页面编码
                $html = $this->CollectConver($html, 'UTF-8');
                //分析页面并写入数据库
                $r1   = $this->AnalysisTitle($html);
                $r2   = $this->AnalysisKeywords($html);
                $r3   = $this->AnalysisDescription($html);
                $r4   = $this->AnalysisContent($html);
                //内容完整性检查
                if (empty($r1) || empty($r4)) {
                    $this->log->Write($url . '内容不完整.');
                    return false;
                }
                if (empty($r2)) {
                    $r2 = CutStr($r4, 30, '');
                }
                if (empty($r3)) {
                    $r3       = CutStr($r4, 30, '');
                }
                $this->lsd->AddPage($wid, $now, $url, $r1, $r2, $r3, $r4);
                $this->log->Write("链接已采集[{$url}]。");
                //分析当前页面可用子连接
                $sub_link = $this->AnalysisLink($base, $url, $html);
                //优化，清除数据
                unset($html);
                unset($r1);
                unset($r2);
                unset($r3);
                unset($r4);
                //采集下面的数据
                if ($sub_link) {
                    //说明有子连接可用
                    foreach ($sub_link as $urlx) {
                        $next = $now + 1;
                        if ($deep >= $next) {
                            $this->CollectProgress($wid, $base, $urlx, $deep, $next);
                        }
                    }
                }
            } else {
                $this->log->Write("连接不需要采集[{$url}]");
            }

            return true;
        } else {
            //超过最大采集深度,采集终止.
            //$this->log->Write("地址[{$url}]超过最大采集深度,停止采集.");
            return false;
        }
    }

    /**
     * 分析页面 -- 获取连接
     */
    private function AnalysisLink($base, $urlt, $content) {
        //link int
        $links_i = 0;
        $links   = array();
        $link_res = array();
        //获取内容中的所有连接
        preg_match_all("'<\s*a\s.*?href\s*=\s*([\"\'])?(?(1)(.*?)\\1|([^\s\>]+))[^>]*>?(.*?)</a>'isx", $content, $links);
        $link_array = $links[2];
        //消除重复链接
        $link_array = array_flip(array_flip($link_array));
        unset($links);
        $link_count = count($link_array);
        //$this->log->Write("基地址[{$urlt}],捕获连接:[{$link_count}]个.");
        if ($link_count) {
            foreach ($link_array as $link) {
                $link = str_replace("\n", "", $link);
                $link = str_replace("\r", "", $link);
                $link = str_replace(" ", "", $link);
                if ($urlt != $link) {
                    if (stripos($link, $base) === 0) {
                        //通过前缀匹配,证明是子连接.
                        //$link_res[] = $this->AnalysisLinkEx($base, $link);
                        $link_res[] = $link;
                        $links_i++;
                    }
                }
            }
        } else {
            //$this->log->Write("基地址[{$urlt}],没有捕获可用链接.");
        }
        //$this->log->Write("基地址[{$urlt}],捕获连接:[{$links_i}]");
        return $link_res;
    }

    private function AnalysisTitle($body) {
        preg_match("/<title>(.*)<\/title>/isU", $body, $inarr);
        if(isset($inarr[1])){
            return $inarr[1];
        }else{
            return null;
        }
        
    }

    private function AnalysisKeywords($body) {
        preg_match('|name="keywords" content="(.*)"|isU', $body, $inarr);
        if(isset($inarr[1])){
            $str = $inarr[1];
        }else{
            return null;
        }
        $str = str_replace(',', ' ', $str);
        $str = str_replace('\\', ' ', $str);
        $str = str_replace("\n", ' ', $str);
        $str = str_replace("\r", ' ', $str);

        return $str;
    }

    private function AnalysisDescription($body) {
        preg_match('|name="description" content="(.*)"|isU', $body, $inarr);
        if (isset($inarr[1])) {
            $str = str_replace("\n", ' ', $inarr[1]);
            $str = str_replace("\r", ' ', $str);
            return $str;
        } else {
            return null;
        }
    }

    /**
     * 内容分析器 -- 获得纯文本内容
     */
    private function AnalysisContent($document) {
        $search = array("'<script[^>]*?>.*?</script>'si", // strip out javascript
            "'<[\/\!]*?[^<>]*?>'si", // strip out html tags
            "'([\r\n])[\s]+'", // strip out white space
            "'&(quot|#34|#034|#x22);'i", // replace html entities
            "'&(amp|#38|#038|#x26);'i", // added hexadecimal values
            "'&(lt|#60|#060|#x3c);'i",
            "'&(gt|#62|#062|#x3e);'i",
            "'&(nbsp|#160|#xa0);'i",
            "'&(iexcl|#161);'i",
            "'&(cent|#162);'i",
            "'&(pound|#163);'i",
            "'&(copy|#169);'i",
            "'&(reg|#174);'i",
            "'&(deg|#176);'i",
            "'&(#39|#039|#x27);'",
            "'&(euro|#8364);'i", // europe
            "'&a(uml|UML);'", // german
            "'&o(uml|UML);'",
            "'&u(uml|UML);'",
            "'&A(uml|UML);'",
            "'&O(uml|UML);'",
            "'&U(uml|UML);'",
            "'&szlig;'i",
        );

        $text = preg_replace($search, ' ', $document);
        $text = str_replace("\r", " ", $text);
        $text = str_replace("\n", " ", $text);
        return $text;
    }

    private function CollectDown($url, $refer = '', $timeout = 3, $connecttimeout = 3) {
        $false = 0;
        $sc    = true;
        while ($sc) {
            // 初始化
            $curl = curl_init();
            //设置
            curl_setopt($curl, CURLOPT_URL, $url);
            curl_setopt($curl, CURLOPT_REFERER, $refer);
            curl_setopt($curl, CURLOPT_TIMEOUT, $timeout);
            curl_setopt($curl, CURLOPT_COOKIEJAR, '/tmp/http.cookies.tmp');
            curl_setopt($curl, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.4 (KHTML, like Gecko) Chrome/22.0.1229.94 Safari/537.4');
            curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, $connecttimeout);
            curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
            curl_setopt($curl, CURLOPT_AUTOREFERER, 1);
            curl_setopt($curl, CURLOPT_MAXREDIRS, 3); //最多跳转3次
            //curl_setopt($curl, CURLOPT_LOW_SPEED_TIME, 1);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
            //curl_setopt($curl, CURLOPT_LOW_SPEED_LIMIT, 512000); //每秒最多128KB的下载速度
            $res  = curl_exec($curl);
            curl_close($curl);
            //运行统计
            if ($res !== false) {
                //正确得到数据,立即返回.
                $sc = false;
            } else {
                if ($false > 2) {
                    //超过重试次数
                    $sc = false;
                } else {
                    //继续重试.
                    $false++;
                }
            }
            sleep(1);
        }
        return $res;
    }

    private function CollectConver($data, $to) {
        if (is_array($data)) {
            foreach ($data as $key => $val) {
                $data[$key] = $this->CollectConver($val, $to);
            }
        } else {
            $encode_array = array('ASCII', 'UTF-8', 'GBK', 'GB2312', 'BIG5');
            $encoded = mb_detect_encoding($data, $encode_array);
            $to      = strtoupper($to);
            if ($encoded != $to) {
                $data = mb_convert_encoding($data, $to, $encoded);
            }
        }
        return $data;
    }

}