<?php

/**
 * 蜘蛛-数据模块
 */
class Lib_Spider_Db extends Maxfs {

    //这个是用于缓存已经Check过的url，并不是每次都需要去读取数据库。时效性仅限于当前进程。
    private $cache_url = array();

    public function Opt() {
        Import('#Mxlog');
        Import('#Mysql');
        $this->Extend('Mysql', 'dbc');
        $this->Extend('Mxlog', 'log');
        $this->log->opt(PathAppLog . 'lsd.log');
        $this->dbc->opt(CfgDbHost, CfgDbUser, CfgDbPass, CfgDbName);
        $this->dbc->Connect();
        return true;
    }

    public function ReadAllWebSite() {
        $time = time();

        $sql = "SELECT * FROM website WHERE `status`=1 AND ({$time}-`lastupdate`)>`perupdate`";
        $this->log->Write($sql);
        return $this->dbc->Query('SELECT * FROM website')->GetAll();
    }

    public function CheckURL($url) {
        $url = addslashes($url);
        if (in_array($url, $this->cache_url)) {
            $nums = count($this->cache_url);
            //如果存在缓存的URL地址中，则跳过。
            $this->log->Write("重复性检查，缓存命中。地址{$url},基数{$nums}");
            return false;
        } else {
            //如果不存在缓存的URL地址中，则返回可以采集的信号。
            $this->cache_url[] = $url;
            return true;
        }
        /*
          $url = addslashes($url);
          $sql = "SELECT url FROM html WHERE url='{$url}'";
          $this->log->Write($sql);
          return $this->dbc->Query($sql)->Get();
         */
    }

    public function AddPage($wid, $dep, $url, $title, $keyword, $description, $content) {
        $this->dbc->Reconnect();
        $time        = time();
        $url         = addslashes($url);
        $title       = addslashes($title);
        $keyword     = addslashes($keyword);
        $description = addslashes($description);
        $content     = addslashes($content);
        $ext         = $this->dbc->Query("SELECT url FROM html WHERE url='{$url}'")->Get();
        if ($ext) {
            $sql = "UPDATE html SET `tile`='{$title}',`keyword`='{$keyword}',`description`='{$description}',`content`='{$content},`time`={$time} WHERE `url`='{$url}'";
            $this->log->Write(substr($sql, 0, 50));
            $this->dbc->Query($sql);
        } else {
            $sql = "INSERT INTO html(`wid`,`dep`,`url`,`title`,`keyword`,`description`,`content`,`time`,`rank`,`status`) VALUES({$wid},{$dep},'{$url}','{$title}','{$keyword}','{$description}','{$content}','{$time}',0,1)";
            $this->log->Write(substr($sql, 80, 50));
            //$this->log->Write($sql);
            $this->dbc->Query($sql);
            $this->log->Write($this->dbc->GetError());
            //插入新数据，更新站点表
            $sql = "UPDATE website SET `pages` = `pages` +1 WHERE `wid`={$wid}";
            $this->dbc->Query($sql);
        }
        return true;
    }

    public function SiteFresh($wid) {
        $time = time();
        $sql  = "UPDATE website SET `lastupdate` = {$time} WHERE `wid`={$wid}";
        $this->dbc->Query($sql);
        return 1;
    }

}