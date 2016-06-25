<?php

class App extends Maxfs {

    public function Init() {
        //重新设定错误处理
        Error::Init(PathAppLog, 'Search.log', false, false);
        
        define('SearchPerPage', 15);

        Import('#Mview');
        Import('#Pagination');
        Import('$Lib.Search');
        Import('$Commom');

        $this->Extend('Pagination', 'pag');
        $this->Extend('Lib_Search', 'lsd');
        $this->Extend('Mview', 'mvi');

        $this->lsd->opt();
        $this->mvi->opt(PathAppTpl);
    }

    public function Go($keyw = '', $page = 1) {

        if ($keyw != '') {
            $data = $this->lsd->SearchData($keyw,$page, SearchPerPage);
            $nums = $this->lsd->SearchNums();
            $time = $this->lsd->SearchTime();
            $keyd = $this->lsd->SearchKeyw();
            $this->pag->opt(array(
                'first'  => '<a href="' . UrlOpration('Search', 'Go', "$keyw-*",true) . '">首页</a>',
                'last'   => '',
                'now'    => '<strong>*</strong>',
                'other'  => '<a href="' . UrlOpration('Search', 'Go', "$keyw-*",true) . '">*</a>',
                'before' => '<a href="' . UrlOpration('Search', 'Go', "$keyw-*",true) . '">上一页</a>',
                'after'  => '<a href="' . UrlOpration('Search', 'Go', "$keyw-*",true) . '">下一页</a>',
                    ), 8);
            $pagi = $this->pag->make($nums, $page, SearchPerPage);
            
        } else {
            $nums = 0;
            $data = array();
            $pagi = '';
            $time = 0;
            $keyd = array();
        }
        
        $this->mvi->Add('TPL_SEARCH_KEYW', $keyw);
        $this->mvi->Add('TPL_SEARCH_KEYD', $keyd);
        $this->mvi->Add('TPL_SEARCH_DATA', $data);
        $this->mvi->Add('TPL_SEARCH_PAGI', $pagi);
        $this->mvi->Add('TPL_SEARCH_NUMS', $nums);
        $this->mvi->Add('TPL_SEARCH_TIME', $time);
        $this->mvi->display('search.php');
        //Bprint('调试模式开启。');
    }

}