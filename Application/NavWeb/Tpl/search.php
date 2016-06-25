<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
        <title>搜索</title>
        <link href="<?PHP UrlStatic('style/common.css',false); ?>" rel="stylesheet" type="text/css" />
        <link href="<?PHP UrlStatic('style/search.css',false); ?>" rel="stylesheet" type="text/css" />
        <script type="text/javascript" src="<?PHP UrlStatic('script/jquery.js',false); ?>"></script>
        <script type="text/javascript" src="<?PHP UrlStatic('script/functions.js',false); ?>"></script>
        <script type="text/javascript"">
            $(document).keyup(function(event){if(event.keyCode==13){search();}});
        </script>
    </head>

    <body>

        <div class="search_form">
            <div class="search_logo">
                <a href="/">
                    <img src="<?PHP UrlStatic('img/logo_mini.jpg',false); ?>" width="184" height="37" />
                </a>
            </div>
            <input type="text"  class="input" autocomplete="off" id="txtSearch" value="<?PHP IF ($TPL_SEARCH_KEYW != '') {ECHO $TPL_SEARCH_KEYW;}?>"/>
            <input type="submit"  value="搜索" class="sub_button" onclick="search()" />
        </div>

        <div class="clear"></div>

        <div class="search_data">
            <?php
            IF ($TPL_SEARCH_KEYW != '') {
                ECHO "搜索到约 <strong>{$TPL_SEARCH_NUMS}</strong> 项结果，用时 <strong>{$TPL_SEARCH_TIME}</strong> 秒 ";
            }
            ?>
        </div>

        <div class="search_result"><!--搜索结果开始-->

            <?PHP
            IF (count($TPL_SEARCH_DATA) != 0) {
                FOREACH ($TPL_SEARCH_DATA AS $DATA) {
                    $HTML = '';
                    $HTML .= '<div class="result_list">';
                    $HTML .= '<h3><a href="' . $DATA['url'] . '" target="_blank">' . HighLight($DATA['title'], $TPL_SEARCH_KEYD) . '</a> - 权重值:'.$DATA['se_weight'].' </h3>';
                    $HTML .= '<div class="search_intro">' . HighLight($DATA['description'], $TPL_SEARCH_KEYD) . '</div>';
                    $HTML .= '<p class="search_info">' . CutStr($DATA['url'], 60, '...') . ' / ' . date('Y-m-d H:i:s', $DATA['time']) . '</p>';
                    $HTML .= '</div>';
                    Bprint($HTML);
                    unset($HTML);
                }
            }
            ?>
<?PHP IF (strlen($TPL_SEARCH_PAGI) > 0) {
    ECHO'<div class="page">' . $TPL_SEARCH_PAGI . '</div>';
} ?>

        </div><!--搜索结果结束-->



    </body>
</html>
