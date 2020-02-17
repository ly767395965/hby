<?php
    if(C('LAYOUT_ON')) {
        echo '{__NOLAYOUT__}';
    }
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>跳转提示</title>
<style type="text/css">
*{ padding: 0; margin: 0; }
html,body{ width:100%; height:100%; overflow:hidden}
body{ background: #fff; font-family: '微软雅黑'; color: #333; font-size: 16px; background:url(__PUBLIC__/images/loginbg3.png);}
.system-message{ padding: 24px 48px;width:300px;margin:200px auto;box-shadow: 0 0 8px 1px rgba(59,149,200,0.3);; background:#FFF;}
.system-message img{ display:block; margin:0 auto; width:200px;}
.system-message p{ text-align:center;}
.system-message h1{ font-size: 100px; font-weight: normal; line-height: 120px; margin-bottom: 12px; }
.system-message .jump{ padding-top: 10px;color:#606066; font-size:14px;}
.system-message .jump a{ color: #333;}
.system-message .success,.system-message .error{ line-height: 1.8em; font-size: 25px;}
.system-message .success{ color:#3b95c8;}
.system-message .error{ color:#f00;}
.system-message .detail{ font-size: 12px; line-height: 20px; margin-top: 12px; display:none;color:#606066;}
</style>
</head>
<body>
<div class="system-message">
<?php if(isset($message)) {?>
<img src="__PUBLIC__/images/succ.gif"/>
<p class="success"><?php echo($message); ?></p>
<?php }else{?>
<img src="__PUBLIC__/images/erro.gif"/>
<p class="error"><?php echo($error); ?></p>
<?php }?>
<p class="detail"></p>
<p class="jump">
页面自动 <a id="href" href="<?php echo($jumpUrl); ?>">跳转</a> 等待时间： <b id="wait"><?php echo($waitSecond); ?></b>
</p>
</div>
<script type="text/javascript">
(function(){
var wait = document.getElementById('wait'),href = document.getElementById('href').href;
var interval = setInterval(function(){
	var time = --wait.innerHTML;
	if(time <= 0) {
		location.href = href;
		clearInterval(interval);
	};
}, 1000);
})();
</script>
</body>
</html>
