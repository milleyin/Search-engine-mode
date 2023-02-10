<?php

######################################
include_once('Commom.php');
date_default_timezone_set('Asia/Shanghai');
$cgi = new Maxfs_CGI;
$cgi->Main();
######################################

class Maxfs_CGI {

    /**
     * 所以说我喜欢这种风格。:-)
     */
    public function Main() {
        //初始化预处理过程
        $this->Initer();
        //获得host位置
        $host = $this->Hoster();
        $rmod = $host[1];
        $ldir = $host[0];
        $this->Runner($this->Loader($ldir, $this->Router($rmod)));
        exit();
    }

    /**
     * 初始化
     * @return boolean
     */
    private function Initer() {
        //基本运行常量
        define('MAXFS', TRUE);
        define('RunTime', microtime(true));
        define('PathRoot', str_replace('\\', '/', dirname(__file__) . '/'));
        define('RunModel', 'CGI');
        //系统运行常量
        define('PathSys', PathRoot . 'System/');
        define('PathApplication', PathRoot . 'Application/');
        define('PathSysKernel', PathSys . 'Kernel/');
        define('PathSysPage', PathSys . 'Page/');
        define('PathSysLog', PathSys . 'Log/');
        define('PathSysLib', PathSys . 'Lib/');
        define('PathSysConfig', PathSys . 'Config/');
        //导入核心文件
        include_once (PathSysKernel . 'Error.php');
        include_once (PathSysKernel . 'Maxfs.php');
        include_once (PathSysConfig . 'Config.php');
        //初始化错误控制
        Error::Init(PathSysLog . 'Main_CGI.log', MF_CGI_LOG, MF_CGI_PRI);
        return true;
    }

    /**
     * 匹配目标
     * @return boolean|string
     */
    private function Hoster() {
        $host_conf = include(PathSysConfig . 'Host.php');
        $host_name = $_SERVER['HTTP_HOST'];

        if (isset($host_conf[$host_name])) {
            return $host_conf[$host_name];
        }
        $this->ErrorPage(400,'无效请求','此域名没有绑定任何应用程序.');
    }

    /**
     * 路由选择器
     * @param type $mod
     * @return array
     */
    private function Router($mod) {
        switch ($mod) {
            case 1:
                define('RouterModel', 1);
                $res = $this->RouterModelRewrite();
                break;
            case 2:
                define('RouterModel', 2);
                $res = $this->RouterModelRewrite();
                break;
            case 3:
                define('RouterModel', 3);
                $res = $this->RouterModelRewrite();
                break;
            default :
                define('RouterModel', 0);
                $res = $this->RouterModelDefault();
                break;
        }
        return $res;
    }

    /**
     * 重写模式路由
     */
    private function RouterModelRewrite() {
        if (isset($_SERVER['REQUEST_URI'])) {
                $RMR_URI= urldecode($_SERVER['REQUEST_URI']);
        } else {
            if (isset($_SERVER['argv'])) {
                $RMR_URI = urldecode($_SERVER['PHP_SELF'] . '?' . $_SERVER['argv'][0]);
            } else {
                $RMR_URI = urldecode($_SERVER['PHP_SELF'] . '?' . $_SERVER['QUERY_STRING']);
            }
        }
        //针对/index.php和/?进行特别过滤处理
        if($tmpx = strpos($RMR_URI, '/index.php') !== false){
            define('RMR_URI', substr($RMR_URI,$tmpx+9));
        }elseif($tmpx = strpos($RMR_URI, '/?') !== false){
            define('RMR_URI', substr($RMR_URI,$tmpx+1));
        }else{
            define('RMR_URI',$RMR_URI);
        }

        $a = strpos(RMR_URI, '.');
        $a = ($a !== false) ? $a : 0;
        $b = strpos(RMR_URI, '-');
        $b = ($b !== false) ? $b : 0;
        //////////////////////警告，这是一段很变态的三元，连我自己也忘记当初怎么写出来了。//////////////////////////////
        $c = ($a !== 0 || $b !== 0) ?
                (($a > $b) ? (($b === 0) ?
                                substr(RMR_URI, 0, $a) :
                                substr(RMR_URI, 0, $b)) :
                        (($a === 0) ?
                                substr(RMR_URI, 0, $b) :
                                substr(RMR_URI, 0, $a))) :
                RMR_URI;
        $e = ($a !== 0 || $b !== 0) ?
                (($a > $b) ? (($b === 0) ?
                                substr(RMR_URI, $a) :
                                substr(RMR_URI, $b)) :
                        (($a === 0) ?
                                substr(RMR_URI, $b) :
                                substr(RMR_URI, $a))) :
                RMR_URI;
        //////////////////////警告，这是一段很变态的三元，连我自己也忘记当初怎么写出来了。//////////////////////////////
        if ($e == RMR_URI) {
            $e = '';
        }
        $d = explode('/', $c);
        array_shift($d);
        $d = array_reverse($d);
        if (empty($d[0])) {
            $c .= 'Index';
        }
        $res['Request'] = $c;
        $cut_str        = substr($e, 1);
        $cut_int        = strpos($cut_str, '-');
        if ($cut_int !== FALSE) {
            $res['Opration'] = substr($cut_str, 0, $cut_int);
            $res['Params']   = substr($cut_str, $cut_int + 1);
        } else {
            $res['Opration'] = $cut_str;
            $res['Params']   = null;
        }
        return $res;
    }

    /**
     * 完全兼容模式路由
     * @return mixed $res
     */
    private function RouterModelDefault() {
        $res = array(
            'Request'  => empty($_GET['R']) ? 'Index' : $_GET['R'],
            'Opration' => empty($_GET['O']) ? 'Main' : $_GET['O'],
            'Params'   => empty($_GET['P']) ? null : $_GET['P'],
        );
        return $res;
    }

    /**
     * 加载器,加载文件.
     */
    private function Loader($appdir, $appx) {
        //创建路径
        $app_conf_path = PathApplication . $appdir . '/Config.php';
        $app_tend_path = PathApplication . $appdir . '/Extend.php';
        //检查目标程序目录是否存在
        if (file_exists(PathApplication . $appdir)) {
            //载入目标文件
            include_once($app_conf_path);
            include_once($app_tend_path);

            //定义程序常量
            define('PathAppRoot', PathApplication . $appdir . '/');
            define('PathAppLib', PathAppRoot . 'Lib/');
            define('PathAppTpl', PathAppRoot . 'Tpl/');
            define('PathAppStatic', PathAppRoot . 'Static/');
            define('PathAppData', PathAppRoot . 'Data/');
            define('PathAppLog', PathAppRoot . 'Log/');
            define('PathAppSource',PathAppRoot . 'Source/'. $appx['Request'] . '.php');
            define('AppDir',$appdir);

            //重写检查
            if(!defined('AppStaticRewrite')){
                define('AppStaticRewrite', false);
            }

            if(is_file(PathAppSource)){
                //返回运行参数
                include_once(PathAppSource);
                return $appx;
            }elseif(AppStaticRewrite){
                //根据程序中的AppStaticRewrite常量判断是否重写资源文件
                $len = strpos(RMR_URI, '/Static');
                if($len===0){
                    $appx['Request'] = substr($appx['Request'], 7);
                    //中断正常处理，切换到资源输出方法
                    $this->ReWriter($appx);
                }
            }
        }else{
             $this->ErrorPage(400,'运行时错误','未能加载应用程序.');
        }
    }

    /**
     * 资源输出处理器
     */
    private function ReWriter(){
        //初始化
        $exp_time = 3600;
        $now_time = time();
        if(!defined('AppStaticRewriteOfMed')){define('AppStaticRewriteOfMed',3600);}
        if(!defined('AppStaticRewriteOfPic')){define('AppStaticRewriteOfPic',3600);}
        if(!defined('AppStaticRewriteOfCss')){define('AppStaticRewriteOfCss',3600);}
        if(!defined('AppStaticRewriteOfJs')){define('AppStaticRewriteOfJs',3600);}
        if(!defined('AppStaticRewriteGzip')){define('AppStaticRewriteGzip',5);}

        $file_extn = strtolower(GetFileExtn(RMR_URI));

        //开始匹配
        switch($file_extn){
            //图片匹配组
            case 'jpg':
                $exp_time=AppStaticRewriteOfPic;
                $static_mime = 'image/jpeg';
                break;
            case 'jpeg':
                $exp_time=AppStaticRewriteOfPic;
                $static_mime = 'image/jpeg';
                break;
            case 'png':
                $exp_time=AppStaticRewriteOfPic;
                $static_mime = 'image/png';
                break;
            case 'gif':
                $exp_time=AppStaticRewriteOfPic;
                $static_mime = 'image/gif';
                break;
            case 'bmp':
                $exp_time=AppStaticRewriteOfPic;
                $static_mime = 'image/bmp';
                break;
            //其他资源
            case 'js':
                $exp_time=AppStaticRewriteOfJs;
                $static_mime = 'text/javascript';
                break;
            case 'css':
                $exp_time=AppStaticRewriteOfCss;
                $static_mime = 'text/css';
                break;
            //媒体类型
            case 'swf':
                $exp_time=AppStaticRewriteOfMed;
                $static_mime = 'application/x-shockwave-flash';
                break;
            default :
                $this->ErrorPage(405,'无效文件','目前尚不支持此资源.');
                break;
        }

        //检查浏览器缓存
        if(isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) && ($now_time-strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']) < $exp_time)) {
            header("HTTP/1.1 304 Not Modified");
            exit;
        }

        //寻找资源
        $static_target = PathAppRoot.RMR_URI;
        if(!file_exists($static_target)){
            $this->ErrorPage(404,'文件不存在','您所请求的资源文件不存在.');
        }

        //发送头部
        header("Accept-Ranges:bytes");
        header("Expires: " .gmdate("D, d M Y H:i:s", $now_time+$exp_time )." GMT");
        header("Last-Modified:".gmdate("D, d M Y H:i:s", $now_time+$exp_time )." GMT");
        header("Cache-Control: max-age=".$exp_time);
        header("Cache-Power: Maxfs Static Rewrite");

        //根据配置确定是否采用压缩模式
        if(AppStaticRewriteGzip&&extension_loaded("zlib")&&extension_loaded('zlib') ){
            $content = gzencode(file_get_contents($static_target),AppStaticRewriteGzip);
            header("Content-Encoding: gzip");
            header("Vary: Accept-Encoding");
            header("Content-Length: ".strlen($content));
            header("Content-Type: ".$static_mime);
            Exit($content);
        }else{
            header("Content-Length: ".  filesize($static_target));
            header("Content-Type: ".$static_mime);
            readfile($static_target);
            Exit;
        }
    }

    /**
     * APP运行方法
     * @param $runx
     */
    private function Runner($runx) {
        Error::Init(PathAppLog, 'Main.log', MF_CGI_LOG, MF_CGI_PRI);

        //定位应用程序并加载


        if (!class_exists('App')) {
            $this->ErrorPage(404,'运行时错误','应用程序未找到.');
        }

        $app     = new App;
        $str     = $runx['Opration'];
        $can     = get_class_methods('App');
        $routeto = $do      = $runx['Opration'];

        if (in_array('Init', $can)) {
            $app->Init();
        }

        if (empty($str)) {
            if (in_array('Main', $can)) {
                $app->Main();
            } else {
                $this->ErrorPage(500,'运行时错误','此应用程序没有主操作.');
            }
        } else {

            if ($runx['Params'] !== null) {
                //如果传来参数
                $arr = explode('-', $runx['Params']);
                if (in_array($do, $can)) {
                    $exec = '$app->' . $do . '(';
                    foreach ($arr as $val) {
                        $exec .= '\'' . $val . '\',';
                    }
                    $exec = substr($exec, 0, -1) . ');';
                    eval($exec);
                    exit;
                } else {
                    $this->ErrorPage(500,'运行时错误','应用程序无法完成此操作.');
                }
            } else {
                if (in_array($routeto, $can)) {

                    $exec = '$app->' . $routeto . '();';
                    eval($exec);
                    exit;
                } else {
                    $this->ErrorPage(500,'运行时错误','应用程序无法完成此操作.');
                }
            }
        }
    }

    private  function ErrorPage($httpcode,$title,$info){
        header("HTTP/1.1 {$httpcode}");
        header("Status: {$httpcode}");
        define('MaxfsErrorPageTitle', $title);
        define('MaxfsErrorPageInfo', $info);
        include(PathSysPage.'Error.php');
        exit;
    }

}

?>
