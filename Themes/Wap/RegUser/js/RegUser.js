var urls = "http://www.hbzc777.com";
var te=/^(1)(3[0-9]|4[0-9]|5[0-9]|7[0-9]|8[0-9])[0-9]{8}$/;//手机号验证
$(function(){
	//输入手机号检测
        $("#mobile").keyup(function () {
            var phone=$(this).val();
            var te=/^(1)(3[0-9]|4[0-9]|5[0-9]|7[0-9]|8[0-9])[0-9]{8}$/;
            if(te.test(phone)){
                $('.hQyzm').css({background:"#00a2e1"});
                $('.hQyzm').attr("disabled",false);
            }else{
                $('.hQyzm').css({background:"#d3d3d3"});
                $('.hQyzm').attr("disabled",true);
            }
        });
        //点击改变按钮状态
        $('.hQyzm').click(function(){
            var btn = $(this);
            var count = 60;
            var resend = setInterval(function(){
                count--;
                if (count > 0){
                    btn.val(count+"s后重发");
                    $('.hQyzm').css({background:"#d3d3d3"});
                }else{
                    clearInterval(resend);
                    btn.val("获取验证码").attr("disabled",false);
                    $('.hQyzm').css({background:"#00a2e1"});
                }
            }, 1000);
            btn.attr('disabled',true);
        });
        //获取验证码
        $(".hQyzm").on("click",function () {
            var mobile = $("#mobile").val();
           	if (mobile.length == 0){
            	
                msgtips("手机号不能为空！");
                return;
            }
            else if(!te.test(mobile)){
            	
                msgtips("手机号格式错误！");
                return;
            }
           	$.ajax({
                url : urls + '/app/Index/setSms',
                type : 'post',
                dataType : 'json',
                data:{
                    mobile : mobile
                },
                success: function(msg){
	                if(msg){
	                    if(msg.state==0){
	                    	
	                       
	                    }else{
	                    	
	                        
	                    }
	                }else{
	                	msgtips("网络出现问题了！");
	                    
	                }
                }
            });
        });
        
        
        
        
         //注册
        $("#reg").on("click",function () {
         
            var userName=$("#userName").val();//用户名
            var mobile=$("#mobile").val();//手机号
            var idCard=$("#idCard").val();//身份证
            var pwd1=$("#pwd1").val();//密码
            var pwd2=$("#pwd2").val();//确认密码
            var code=$(".yzm").val();//验证码
            var u=/^[\u4E00-\u9FA5]{2,5}$/;
            var card=/(^\d{15}$)|(^\d{18}$)|(^\d{17}(\d|X|x)$)/;
            var p=/^[0-9a-zA-Z_]{6,16}$/;
           
            if (userName.length == 0) {
               
                msgtips("请输入姓名!");
                return;
            }
            else if(!u.test(userName)){
            	
                msgtips("姓名2-5个中文字符！！");
                return;
            }
            else if (mobile.length == 0){
            	
                msgtips("手机号不能为空！");
                return;
            }
            else if(!te.test(mobile)){
            	
                msgtips("手机号格式错误！");
                return;
            }
            else if(code.length==0){
            	
                msgtips("验证码不能为空！");
                return;
            }
            else if (idCard.length == 0){
            	
                msgtips("身份证号不能为空！");
                return;
            }
            else if(!card.test(idCard)){
            	
                msgtips("身份证号格式错误！！");
                return;
            }
            else if (pwd1.length == 0) {
            	
                msgtips("密码不能为空！");
                return;
            }
            else if(!p.test(pwd1)){
            	
               $("#pwd1").addClass("a-shake");
               setTimeout(function(){
               		$("#pwd1").removeClass("a-shake");
               },2000)
                return;
            }else if (pwd2.length == 0) {
            	
              	msgtips("确认密码不能为空！");
                return;
            }
            if (pwd2 != pwd1) {
            	
                msgtips("两次密码输入不一致！");
                return;
            }

            var city={11:"北京",12:"天津",13:"河北",14:"山西",15:"内蒙古",21:"辽宁",22:"吉林",23:"黑龙江 ",31:"上海",32:"江苏",33:"浙江",34:"安徽",35:"福建",36:"江西",37:"山东",41:"河南",42:"湖北 ",43:"湖南",44:"广东",45:"广西",46:"海南",50:"重庆",51:"四川",52:"贵州",53:"云南",54:"西藏 ",61:"陕西",62:"甘肃",63:"青海",64:"宁夏",65:"新疆",71:"台湾",81:"香港",82:"澳门",91:"国外 "};
            if(!idCard || !/^\d{6}(18|19|20)?\d{2}(0[1-9]|1[12])(0[1-9]|[12]\d|3[01])\d{3}(\d|X)$/i.test(idCard)){
            	
                msgtips("身份证号格式错误！！");
                return;
            }
            else if(!city[idCard.substr(0,2)]){
            	msgtips("地址编码错误！");
              
                return;
            }
            else{
                //18位身份证需要验证最后一位校验位
                if(idCard.length == 18){
                    idCard = idCard.split('');
                    //∑(ai×Wi)(mod 11)
                    //加权因子
                    var factor = [ 7, 9, 10, 5, 8, 4, 2, 1, 6, 3, 7, 9, 10, 5, 8, 4, 2 ];
                    //校验位
                    var parity = [ 1, 0, 'X', 9, 8, 7, 6, 5, 4, 3, 2 ];
                    var sum = 0;
                    var ai = 0;
                    var wi = 0;
                    for (var i = 0; i < 17; i++)
                    {
                        ai = idCard[i];
                        wi = factor[i];
                        sum += ai * wi;
                    }
                    var last = parity[sum % 11];
                    if(parity[sum % 11] != idCard[17]){
                       
                        msgtips("校验位错误！");
                       
                        return;
                    }
                }
            }
           
            $.ajax({
                url : urls + '/app/Index/appReg',
                type : 'post',
                dataType : 'json',
                data : {
                    userName : userName,
                    mobile : mobile,
                    idCard : idCard,
                    pwd1 : pwd1,
                    pwd2 : pwd2,
                    code : code,
                    sex : 1
                },
            	success:function (ret) {
              		console.log(ret)
                    if(ret.reg == 'IsExist'){
                        msgtips("该手机号已注册！");
                        return;
                    }else if(ret.reg == 'CodeOverdue'){
                        msgtips("验证码过期，请重新获取！");    
                        return; 
                    }
                    else if(ret.reg == 'CodeError'){
                        msgtips("验证码错误！");
                        return;
                    }
                    else if(ret.reg == 'RegError'){
                     	msgtips("注册失败！");
                        return;
                    }else{
                    	msgtips("注册成功，即将跳转登录页面");
                    	return;
                    	setTimeout(function(){
                    		window.location.href = "index1.html";
                    	},2000);
                    }
            	}     
        	})
    });
})
function msgtips(msgs){
	$(".msg").css("display","block");
    $(".msg-text").html(msgs).addClass("a-shake");
        setTimeout(function(){
           	$(".msg-text").removeClass("a-shake");
            $(".msg").css("display","none");
    },2000);
    
}
function msgYes(msgs){
	$(".msg").css("display","block");
    $(".msg-text").html(msgs).addClass("a-fadein");
        setTimeout(function(){
           	$(".msg-text").removeClass("a-fadein");
            $(".msg").css("display","none");
    },2000);
}
