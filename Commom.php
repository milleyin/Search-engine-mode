<?php

/**
 * 导入文件,等同于include_once.
 * 特殊:可以自动识别特有路径标识符,并转换成对应路径.当使用了路径标识符的时候,文件名后面不需要加.php,会自动加上.
 * 需求:只有当Maxfs正常初始化后才能使用.
 * 格式:
 * [$]=>导入程序目录内Lib的对应文件,比如"$mytest.php"等同于 PathAppLib+mytest.php
 * [#]=>导入系统目录内Lib的对应文件,比如"#mytest.php"等同于 PathSysLib+mytest.php
 * @param string $path
 * @return bool 成功导入否
 */
function Import($path) {
    return include_once(LibPath($path));
}

/**
 * 路径解析器
 * 需求:只有当Maxfs正常初始化后才能使用.
 * 格式:
 * [$]=>导入程序目录内Lib的对应文件,比如"$mytest.php"等同于 PathAppLib+mytest.php
 * [#]=>导入系统目录内Lib的对应文件,比如"#mytest.php"等同于 PathSysLib+mytest.php
 * @param string $path
 * @return bool 成功导入否
 */
function LibPath($path) {
    if (MAXFS) {
        if (strpos($path, '$') !== false) {
            $path = str_replace('$', PathAppLib, $path) . '.php';
        } elseif (strpos($path, '#') !== false) {
            $path = str_replace('#', PathSysLib, $path) . '.php';
        }
        return $path;
    } else {
        return $path;
    }
}

/**
 * 生成操作用URL
 * 此函数仅能在CGI模式下运行,输入创建URL的参数即可获得动态生成的URL地址.
 * 动态生成的URL地址根据其项目配置的URL模式自动生成.
 * @param string $quest |请求的程序文件
 * @param string $action|请求的操作
 * @param string $param |操作参数,参数请使用 - 链接，不支持数组，仅支持字符串。
 * @return string
 */
function UrlOpration($quest = 'index', $opration = 'main', $param = null, $return = false) {

    if (RunModel === 'CGI') {
        switch (RouterModel) {
            case 1:
                $return = '/?/' . $quest . '.' . $opration . '-' . $param;
                break;
            case 2:
                $return = '/index.php/' . $quest . '.' . $opration . '-' . $param;
                break;
            case 3:
                $return = '/' . $quest . '.' . $opration . '-' . $param;
                break;
            default :
                $return = "?R={$quest}&O={$opration}&P={$param}";
                break;
        }
        if ($return) {
            return $return;
        } else {
            echo $return;
        }
    } else {
        return null;
    }
}

/**
 * 静态资源路径生成
 * @param string $file_name|请求的资源文件路径，相对与资源目录[static]，不要填写后缀名
 * @param string $file_type|请求的资源文件后缀名。
 * @return null
 */
function UrlStatic($file_path,$returnx=true) {
    if (RunModel === 'CGI') {
        if (AppStaticRewrite) {
            switch (RouterModel) {
                case 1:
                    $return = '/?/Static/' . $file_path;
                    break;
                case 2:
                    $return = '/index.php/Static/' . $file_path;
                    break;
                case 3:
                    $return = '/Static/' . $file_path;
                    break;
                default :
                    $return = "?R=/Static/" . $file_path;
                    break;
            }
        }else{
            $return = "/Application/".AppDir. $file_path;
        }
        if($returnx){
            return $return;
        }else{
            echo $return;
        }   
    } else {
        return null;
    }
}

/**
 * 行打印字符串，多用于CLI模式。
 * 此函数将在输出字符串的时候自动给末尾加上换行符
 * @return boolean
 */
function Nprint($str) {
    echo $str . "\n";
    return true;
}

/**
 * 行打印字符串，多用于CGI模式。
 * 此函数将在输出字符串的时候自动给末尾加上HTML换行符
 * @return boolean
 */
function Bprint($str) {
    echo $str . "</br>\n";
    return true;
}

/**
 * 
 * @param type $name
 * @return type
 */
function GetFileExtn($name) {
    $a = array_reverse(explode('.', $name));
    return $a[0];
}

function GetFileSize($i) {
    $s = sprintf("%u", $i);
    if ($s == 0) {
        return ("0 Bytes");
    }
    $sizename = array(" Byte", " KB", " MB", " GB", " TB", " PB", " EB", " ZB", " YB");
    return round($s / pow(1024, ($i = floor(log($s, 1024)))), 2) . $sizename[$i];
}

/**
 * 字符截取 支持UTF8/GBK
 * @param $string
 * @param $length
 * @param $dot
 * @param $charset
 */
function CutStr($string, $length, $dot = '...', $charset = 'utf-8') {
    $strlen = strlen($string);
    if ($strlen <= $length)
        return $string;
    $string = str_replace(array(' ', '&nbsp;', '&amp;', '&quot;', '&#039;', '&ldquo;', '&rdquo;', '&mdash;', '&lt;', '&gt;', '&middot;', '&hellip;'), array('∵', ' ', '&', '"', "'", '“', '”', '—', '<', '>', '·', '…'), $string);
    $strcut = '';
    if (strtolower($charset) == 'utf-8') {
        $length = intval($length - strlen($dot) - $length / 3);
        $n      = $tn     = $noc    = 0;
        while ($n < strlen($string)) {
            $t = ord($string[$n]);
            if ($t == 9 || $t == 10 || (32 <= $t && $t <= 126)) {
                $tn = 1;
                $n++;
                $noc++;
            } elseif (194 <= $t && $t <= 223) {
                $tn = 2;
                $n += 2;
                $noc += 2;
            } elseif (224 <= $t && $t <= 239) {
                $tn = 3;
                $n += 3;
                $noc += 2;
            } elseif (240 <= $t && $t <= 247) {
                $tn = 4;
                $n += 4;
                $noc += 2;
            } elseif (248 <= $t && $t <= 251) {
                $tn = 5;
                $n += 5;
                $noc += 2;
            } elseif ($t == 252 || $t == 253) {
                $tn = 6;
                $n += 6;
                $noc += 2;
            } else {
                $n++;
            }
            if ($noc >= $length) {
                break;
            }
        }
        if ($noc > $length) {
            $n -= $tn;
        }
        $strcut = substr($string, 0, $n);
        $strcut = str_replace(array('∵', '&', '"', "'", '“', '”', '—', '<', '>', '·', '…'), array(' ', '&amp;', '&quot;', '&#039;', '&ldquo;', '&rdquo;', '&mdash;', '&lt;', '&gt;', '&middot;', '&hellip;'), $strcut);
    } else {
        $dotlen      = strlen($dot);
        $maxi        = $length - $dotlen - 1;
        $current_str = '';
        $search_arr  = array('&', ' ', '"', "'", '“', '”', '—', '<', '>', '·', '…', '∵');
        $replace_arr = array('&amp;', '&nbsp;', '&quot;', '&#039;', '&ldquo;', '&rdquo;', '&mdash;', '&lt;', '&gt;', '&middot;', '&hellip;', ' ');
        $search_flip = array_flip($search_arr);
        for ($i = 0; $i < $maxi; $i++) {
            $current_str = ord($string[$i]) > 127 ? $string[$i] . $string[++$i] : $string[$i];
            if (in_array($current_str, $search_arr)) {
                $key         = $search_flip[$current_str];
                $current_str = str_replace($search_arr[$key], $replace_arr[$key], $current_str);
            }
            $strcut .= $current_str;
        }
    }
    return $strcut . $dot;
}