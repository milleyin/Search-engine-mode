<?php

class App extends Maxfs{
    
    public function Init(){
        //重新设定错误处理
        Error::Init(PathAppLog, 'Index.log', false, false);
        Import('#Mview');
        $this->Extend('Mview','mvi');
        $this->mvi->opt(PathAppTpl);
    }
    
    public function Main(){
        $this->mvi->Display('index.php');
    }
}