<?php

class Lib_Search extends Maxfs {

    private $res;

    const SHPINX_WORDCUT_AROUND = 5; //检索引擎内容摘要设置，每个关键词块左右选取的词的数目。整数，默认为 5.
    const SHPINX_WORDCUT_HSTRIP = "strip"; //检索引擎内容摘要设置,HTML标签剥离模式设置。默认为"index"，表示使用index的设置。
    const SHPINX_WORDCUT_LITWRD = 20; //检索引擎内容摘要设置，限制摘要中可以包含的最大词汇数。整数值，默认为 20 (不限制).

    public function Opt() {
        Import('$sphinxapi');
        Import('#Mysql');

        $this->Extend('SphinxClient', 'csk');
        $this->Extend('Mysql', 'dbc');

        $this->dbc->opt(CfgDbHost, CfgDbUser, CfgDbPass, CfgDbName);
        $this->csk->SetServer('127.0.0.1', 9312);
        //$this->csk->SetSortMode(SPH_SORT_EXTENDED,'@weight DESC');
        //$this->csk->SetGroupBy ( "wid", SPH_GROUPBY_ATTR, "@group desc" );
        //$this->csk->SetGroupBy ( "time", SPH_GROUPBY_DAY, "@count desc" );
        //$this->csk->SetSortMode(SPH_SORT_RELEVANCE);
        $this->csk->SetSortMode(SPH_SORT_EXPR, "@weight + ( view*0.1 ) + (dep*5)");
        $this->csk->SetArrayResult(true);
        $this->csk->SetFieldWeights(array(
            'title'       => 500,
            'keyword'     => 20,
            'description' => 10,
            'content'     => 5,
        ));
    }

    public function SearchNums() {
        return $this->res['total_found'];
    }

    public function SearchTime() {
        return $this->res['time'];
    }

    public function SearchKeyw(){
        return array_keys($this->res['words']);
    }

    public function SearchData($keyword, $page, $nums) {
        //格式化参数
        if ($page < 1) {
            $page      = 1;
        }
        //设定分页范围
        $start     = ($page - 1) * $nums;
        $endin     = $nums;
        $this->csk->SetLimits($start, $endin);
        $this->res = $this->csk->Query($keyword, '*');

        //创建查询范围
        $key = '';
        $mch = array();
        foreach ($this->res['matches'] as $line) {
            $key .= $line['id'] . ',';
            $mch[$line['id']]['weight'] = $line['weight'];
        }
        $key                        = substr($key, 0, -1);

        //从数据库取数据
        $this->dbc->Connect();
        $this->dbc->Query("SELECT `title`,`description`,`time`,`url`,`hid` FROM html WHERE `hid` IN({$key})");
        $this->dbc->ByeBye();
        $tmpc = $this->dbc->GetAll();

        //附加特殊信息
        $tmpa = array();
        $tmpb = array();
        foreach ($tmpc as $row) {
            $tmpa['hid']         = $row['hid'];
            $tmpa['title']       = $row['title'];
            $tmpa['time']        = $row['time'];
            $tmpa['url']         = $row['url'];
            $tmpa['description'] = $row['description'];
            //附加数据
            $tmpa['se_weight']   = $mch[$row['hid']]['weight'];

            $tmpb[$tmpa['hid']] = $tmpa;
        }

        //重新排序
        $tmpc = array();
        foreach ($this->res['matches'] as $line) {
            $tmpc[] = $tmpb[$line['id']];
        }

        return $tmpc;
        /*
          //内容摘要设置
          $dat = $this->dbc->GetAll();

          $tet = array();
          $set = array(
          'around'          => self::SHPINX_WORDCUT_AROUND,
          'html_strip_mode' => self::SHPINX_WORDCUT_HSTRIP,
          'limit_words'     => self::SHPINX_WORDCUT_LITWRD,
          );

          foreach($dat as $row){
          $tet[] = $row['content'];
          }
          //print_r($tet);
          $dat = $this->csk->BuildExcerpts($tet, 'in', $keyword,$set);
          var_dump($dat);
          return $dat;
         */
    }

}
