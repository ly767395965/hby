<?php

function ChangeString($str)
{
//str含有HTML标签的文本
//    $str = str_replace("\n","<br>",$str);
    $str = str_replace("<","&lt;",$str);
    $str = str_replace(">","&gt;",$str);
    $str = str_replace(" ","&nbsp;",$str);
    $str = str_replace("&","&amp;",$str);
    return $str;
}
