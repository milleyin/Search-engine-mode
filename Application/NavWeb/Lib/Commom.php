<?php

/**
 * 关键字高亮函数
 * @param type $str
 * @param type $keywords
 * @param type $color
 * @return type
 */
function HighLight($content, $key, $color = 'red') {
    if (is_array($key)) {
        foreach ($key as $k) {
            $k       = htmlspecialchars(str_replace(' ', '', $k));
            $content = preg_replace("/($k)/i", "<font color=\"$color\">$0</font>", $content);
        }
    } else {
        $key     = htmlspecialchars(str_replace(' ', '', $key));
        $content = preg_replace("/($key)/i", "<font color=\"$color\">$0</font>", $content);
    }

    return $content;
}