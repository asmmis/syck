<?php
// 应用公共文件

//用户昵称显示  所有的都是
function show_nickname(string $nickname){
    return json_decode($nickname,true);
}
