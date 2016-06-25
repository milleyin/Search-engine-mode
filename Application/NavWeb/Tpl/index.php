<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
        <title>搜索</title>
        <link href="<?PHP UrlStatic('style/common.css',false); ?>" rel="stylesheet" type="text/css" />
        <link href="<?PHP UrlStatic('style/index.css',false); ?>" rel="stylesheet" type="text/css" />
        <link href="<?PHP UrlStatic('style/jquery_autocomplete.css',false); ?>" rel="stylesheet" type="text/css" />
        <script type="text/javascript" src="<?PHP UrlStatic('script/jquery_vsdoc.js',false); ?>"> </script>
        <script type="text/javascript" src="<?PHP UrlStatic('script/jquery.js',false); ?>"></script>
        <script type="text/javascript" src="<?PHP UrlStatic('script/jquery_autocomplete.js',false); ?>"></script>
        <script type="text/javascript" src="<?PHP UrlStatic('script/data.js',false); ?>"></script>
        <script type="text/javascript" src="<?PHP UrlStatic('script/functions.js',false); ?>"></script>

        <script type="text/javascript">
            $(function(){$("#txtSearch").autocomplete(arrUserName,{minChars:0,formatItem:function(data,i,total){return data[0];},formatMatch:function(data,i,total){return data[0];},formatResult:function(data){return data[0];}})});$(document).keyup(function(event){if(event.keyCode==13){search();}});
        </script>
    </head>

    <body>
        <div class="index_main"><!--主体内容开始-->
            <div class="logo">
                <img src="<?PHP UrlStatic('img/logo.jpg',false); ?>" width="259" height="53"  title="搜索"/>
            </div>

            <div class="search_box">
                <input type="text"  class="input"  id="txtSearch"/><input type="submit" value="搜索" class="sub_button" onclick="search()" />
            </div>
            <div class="clear"></div>

        </div><!--主体内容结束-->
    </body>
</html>
