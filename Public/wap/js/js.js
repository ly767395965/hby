//获取URL
GetQueryString=function(name)
{
    var reg = new RegExp("(^|&)"+ name +"=([^&]*)(&|$)");
    var r = window.location.search.substr(1).match(reg);
    if(r!=null)return decodeURI(r[2]); return null;
}



//计算时间之差
function diffDate(startTime, endTime, diffType) {
    //将时间格式转换为可读的格式
    startTime = startTime.replace(/\-/g, "/");
    endTime = endTime.replace(/\-/g, "/");

    //将计算间隔类性字符转换为小写
    diffType = diffType.toLowerCase();
    var sTime = new Date(startTime);      //开始时间
    var eTime = new Date(endTime);  //结束时间
    //作为除数的数字
    var dNum = 1;
    switch (diffType) {
        case "s":
            dNum = 1000;
            break;
        case "m":
            dNum = 1000 * 60;
            break;
        case "h":
            dNum = 1000 * 3600;
            break;
        case "d":
            dNum = 1000 * 3600 * 24;
            break;
        default:
            break;
    }
    return parseInt((eTime.getTime() - sTime.getTime()) / parseInt(dNum));
}
//给YYYY-MM-dd加天数
function dateOperator(date,days,operator){
    date = date.replace("年","-").replace("月","-").replace("日",""); //更改日期格式  
    var nd = new Date(date);
    nd = nd.valueOf();
    if(operator=="+"){
        nd = nd + days * 24 * 60 * 60 * 1000;
    }else if(operator=="-"){
        nd = nd - days * 24 * 60 * 60 * 1000;
    }else{
        return false;
    }
    nd = new Date(nd);
    var y = nd.getFullYear();
    var m = nd.getMonth()+1;
    var d = nd.getDate();
    if(m <= 9) m = "0"+m;
    if(d <= 9) d = "0"+d;
    var cdate = y+"年"+m+"月"+d+"日";
    return cdate;
}



/* 给日期加天数*/
function DateAdd(sdate, days) {
    var a = new Date(sdate);
    a = a.valueOf();
    a = a + days * 24 * 60 * 60 * 1000;
    a = new Date(a);
    var Y=a.getFullYear();
    var M=a.getMonth()+1;
    var D=a.getDate();
    if (M < 10) {
        M = "0" + M;
    }
    if (D < 10) {
        D= "0" + D;
    }
    date=Y+"-"+M+"-"+D;
    return date;
}



