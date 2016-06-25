<?php

/**
 * 框架配置文件，用于设定错误控制。
 * 分别设置
 * CLI模式下是否记录运行过程产生的错误信息
 * CLI模式下是否显示运行过程产生的错误信息
 * CGI模式下是否记录运行过程产生的错误信息
 * CGI模式下是否显示运行过程产生的错误信息
 */
define('MF_CLI_LOG',true);
define('MF_CLI_PRI',true);
define('MF_CGI_LOG',true);
define('MF_CGI_PRI',true);

define('MF_CGI_ERROR_DISPLAY',TRUE);
