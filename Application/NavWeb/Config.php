<?php

#############项目常量################
define('AppType', 'CGI');
define('AppVersion', 1.0);
define('AppName', 'NavWeb');
#############重写配置################
define('AppStaticRewrite', 1);         //是否启用Maxfs重写资源文件，只有启用了重写，下面规则才会生效。
define('AppStaticRewriteGzip', 0);     //启用Gzip压缩内容,0为不启用，其他数字为压缩级别。
define('AppStaticRewriteOfMed', 86400); //针对媒体文件的缓存时间
define('AppStaticRewriteOfPic', 86400); //针对图片文件的缓存时间
define('AppStaticRewriteOfCss', 86400); //针对样式文件的缓存时间
define('AppStaticRewriteOfJs', 86400); //针对脚本文件的缓存时间
//程序全局常量
define('CfgDbHost', '127.0.0.1');
define('CfgDbUser', 'root');
define('CfgDbPass', '131.c0m');
define('CfgDbName', 'navx');
