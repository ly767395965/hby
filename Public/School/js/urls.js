var urls;
var weburl = window.location.href;
str = weburl; //这是一字符串
var strs= new Array(); //定义一数组
strs=str.split("/"); //字符分割
weburl = "http://"+strs[2]+"/";
urls = weburl;

