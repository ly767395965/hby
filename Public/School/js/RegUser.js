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

         //注册
        $("#reg").on("click",function () {
            var school  = $('#dept option:selected').val();
            var userName=$("#userName").val();//用户名
            var mobile=$("#mobile").val();//手机号
            var idCard=$("#idCard").val();//身份证
            var pwd1=$("#pwd1").val();//密码
            var pwd2=$("#pwd2").val();//确认密码
            var code=$(".yzm").val();//验证码
            var u=/^[\u4E00-\u9FA5]{2,5}$/;
            var card=/(^\d{15}$)|(^\d{18}$)|(^\d{17}(\d|X|x)$)/;
            var p=/^[0-9a-zA-Z_]{6,16}$/;

            if (school == 0){
                msgtips("请选择注册院校！");
                return;
            }
            else if (userName.length == 0) {
               
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
               $.ajax({
                   url :urls + '/School/UserReg/index',
                   type : 'post',
                   dataType : 'json',
                   data : {
                       userName : userName,
                       mobile : mobile,
                       idCard : idCard,
                       pwd1 : pwd1,
                       code : code,
                       school : school
                   },
                   success:function (ret) {
                       // alert(ret);
                       console.log(ret);
                       if(ret == 2){
                           msgtips("该手机号已注册！");
                           return;
                       }
                       else if(ret == 4){
                           msgtips("验证码错误！");
                           return;
                       }
                       else if(ret == 3){
                           msgtips("注册失败！");
                           return;
                       }else{
                           msgtips("注册成功，即将跳转登录页面");
                           setTimeout(function(){
                               window.location.href = "../../../School/Login/index";
                           },2000);
                           return;
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
