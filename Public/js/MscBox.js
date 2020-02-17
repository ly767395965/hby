//icon图标
var alter = '<img src="../../../../../../../../Public/images/ticon.png" />';
var error = '<img src="../../../../../../../../Public/images/erro.png" />';
var t = [alter, error];
//确认取消窗
var msBox = '<style type="text/css">' +
    'cite{font-style: normal;font-size: 12px;}dl,dt,dd,span{margin:0;padding:0;display:block;}input{outline: none;border: none;}' +
    'a,a:focus{text-decoration:none;color:#000;outline:none;blr:expression(this.onFocus=this.blur());}' +
    'a:hover{color:#00a4ac;text-decoration:none;}' +
    '.tip{width:485px; height:260px;background:#fcfdfd;box-shadow:1px 6px 8px 1px #9b9b9b;border-radius:1px;behavior:url(../../../../../../../../Public/js/pie.htc); z-Index:111111; display:none;}' +
    '.tiptop{height:40px; line-height:40px; background:url(../../../../../../../../Public/images/tcbg.gif)  repeat-x; cursor:pointer;}' +
    '.tiptop:hover{cursor:move}' +
    '.tiptop span{font-size:14px; font-weight:bold; color:#fff;float:left; text-indent:20px; line-height:40px;}' +
    '.tiptop a{display:block; background:url(../../../../../../../../Public/images/close.png) no-repeat; width:22px; height:22px;float:right;margin-right:7px; margin-top:10px; cursor:pointer;}' +
    '.tiptop a:hover{background:url(../../../../../../../../Public/images/close1.png) no-repeat;}' +
    '.tipinfo{padding-top:30px;margin-left:65px; height:95px;}' +
    '.tipinfo span{width:95px; height:95px;float:left;}' +
    '.tipright{float:left;padding-top:15px; padding-left:10px;}' +
    '.tipright p{font-size:14px; font-weight:bold; line-height:35px;}' +
    '.tipright cite{color:#858686;}' +
    '.tipbtn{margin:0 auto; margin-top:25px;}' +
    '.sure ,.cancel{width:96px; height:35px; line-height:35px; color:#fff; background:url(../../../../../../../../Public/images/btnbg1.png) repeat-x; font-size:14px; font-weight:bold;border-radius: 3px; cursor:pointer;}' +
    '.sure{margin-left:125px;}' +
    '.cancel{background:url(../../../../../../../../Public/images/btnbg2.png) repeat-x;color:#000;font-weight:normal;}' +
    '</style>' +
    '<div class="tip" id="tip" style="position:fixed;top:50%; left:50%;margin-left:-242.5px;margin-top:-130px;">' +
    '<div class="tiptop" id="tiptop"><span id="msTitle">提示信息</span><a id="msClose"></a></div>' +
    '<div class="tipinfo">' +
    '<span id="msIcon"></span>' +
    '<div class="tipright">' +
    '<p id="msContent">是否确认对信息的修改 ？</p>' +
    '<cite id="msExplain">如果是请点击确定按钮 ，否则请点取消。</cite>' +
    '</div></div>' +
    '<div class="tipbtn" id="tipbtn">' +
    '<input name="" type="button" id="sure"  class="sure" value="确定" />&nbsp;' +
    '<input name="" type="button" id="cancel"  class="cancel" value="取消" />' +
    '</div></div>';
var winCon = '<style type="text/css">' +
    'dl,dt,dd,span{margin:0;padding:0;display:block;}a,a:focus{text-decoration:none;color:#000;outline:none;blr:expression(this.onFocus=this.blur());}a:hover{color:#00a4ac;text-decoration:none;}' +
    '.winContent{ display:none; width:50%; height:70%; background:#fcfdfd; position:fixed; z-Index:9999999999; top:10%; left:50%; margin-left:-25%;box-shadow:1px 8px 10px 1px #9b9b9b; border-radius:1px; overflow:hidden; padding:0 15px; padding-bottom:20px;}' +
    '.winTop{ width:100%; height:40px; background:url(../../../../../../../../Public/images/tcbg.gif); padding:0 20px; margin-left:-20px;}' +
    '.winTop span{ line-height:40px; color:#FFF; font-weight:bold; font-size:14px; float:left;}' +
    '.winTop i{display:block; background:url(../../../../../../../../Public/images/close.png); width:22px; height:22px;float:right; margin-top:10px; cursor:pointer;}' +
    '.winTop i:hover{ background:url(../../../../../../../../Public/images/close1.png);}' +
    '.winContent-box{ width:95%; height:520px; padding:10px; text-align:center; font-size:13px; line-height:25px; color:#606066; overflow-y:scroll;}</style>' +
    '<div class="winContent" id="winBox">' +
    '<div class="winTop">' +
    '<span id="winTitle"></span><i id="winClose"></i></div>' +
    '<div class="winContent-box" id="wintext"></div></div>';
document.write(msBox);//写入弹窗
document.write(winCon);//写入文章信息
//确认取消窗方法
function MscBox(msTitle, msIcon, msContent, msExplain, msUrl) {
    document.getElementById("msTitle").innerHTML = msTitle;
    document.getElementById("msIcon").innerHTML = msIcon;
    document.getElementById("msContent").innerHTML = msContent;
    document.getElementById("msExplain").innerHTML = msExplain;


    var tip = document.getElementById("tip");
    var sure = document.getElementById("sure");
    var cancel = document.getElementById("cancel");
    var msClose = document.getElementById("msClose");
    tip.style.display = "block"
    tip.style.position="fixed";
    tip.style.top="50%";
    tip.style.left="50%";
    tip.style.marginLeft="-242.5px";
    tip.style.marginTop="-130px";
    sure.onclick = function () {
        window.self.location.href = msUrl;
    }
    cancel.onclick = function () {
        tip.style.display = "none";
    }
    msClose.onclick = function () {
        tip.style.display = "none";
    }
}
//确认框方法
function MsBox(msTitle, msIcon, msContent, msExplain) {
    MscBox(msTitle, msIcon, msContent, msExplain);

    var tipbtn = document.getElementById("tipbtn");
    var cancel = document.getElementById("cancel");
    var sure = document.getElementById("sure");
    var su = '<input name="" type="button" id="su"  class="sure" value="编辑" />';

    tipbtn.removeChild(cancel);
    tipbtn.removeChild(sure);
    tipbtn.innerHTML = su;
    var sur = document.getElementById("su");
    sur.style.display = "block";
    sur.style.margin = "0 auto";
    sur.onclick = function () {
        tip.style.display = "none";
    }
}
//文章
function winC(Title, text) {
    document.getElementById("winBox").style.display = "block";
    document.getElementById("winTitle").innerHTML = Title;
    document.getElementById("wintext").innerHTML = text;
    var closeWin = document.getElementById("winClose");
    closeWin.onclick = function () {
        document.getElementById("winBox").style.display = "none";
    }
}
//拖动
fdiv = document.getElementById("tip");
document.getElementById("tiptop").onmousedown=function(e)
{
    if(!e) e = window.event;  //IE

    document.onmousemove = mousemove;
}
document.onmouseup = function()
{
    document.onmousemove = null;
}
function mousemove(ev)
{
    if(ev==null) ev = window.event;//IE
    fdiv.style.left = (ev.clientX+10) + "px";
    fdiv.style.top = (ev.clientY+110) + "px";
}

//批量删除
function delCheckboxes(url, checkboxName) {
    checkboxName = "'" + checkboxName + "'";
    var ids = $("input[name=" + checkboxName + "]:checked");
    var size = ids.size();
    if (size > 0) {
        var flag = window.confirm('您确定要删除吗？');
        var arr = [];
        for (var i = 0; i < size; i++) {
            arr.push(ids[i].value);
        }
        if (flag) {
            $.ajax({
                url: url,
                type: 'post',
                dataType: 'json',
                data: {
                    ids: arr.join(',')
                },
                success: function (response) {
                    if (response.state == 1) {
                        location.reload();
                    } else if (response.state == 0) {
                        alert(response.msg);
                        location.reload();

                    }
                }
            });
        }
    } else {
        alert('请先选择要删除的数据！');
    }
}
//MscBox(标题,图标状态t[i],内容，内容说明，地址); MsBox(标题,图标状态t[i],内容，内容说明)  winC(标题,主要内容);
//selectAll(当前对象,checkbox的class值带点);delCheckboxes(ajax的url，checkbox的name值);

