
$(function(){
	 //获得失去焦点文本框样式
        $(".input-row input").eq(0).keyup(function () {
            if($(this).val().length>0){
                $(".input-del").css({display:"block"});
            }else{
                $(".input-del").css({display:"none"});
            }
        });
        $(".input-del").click(function () {
            $(".input-row input").eq(0).val("");
            $(this).hide();
        });
        //显示隐藏密码
        $(".input-eyes").toggle(function () {
            $(".input-row input").eq(1).attr("type","text");
            $(this).addClass("input-eyes1");
        },function () {
            $(".input-row input").eq(1).attr("type","password");
            $(this).removeClass("input-eyes1");
        });
        
	$("#login").on("click",function(){
		var loginUser=$("#loginUser").val();//登录账号
        var loginPwd=$("#loginPwd").val();//登录密码
        var p=/^[0-9a-zA-Z_]{6,16}$/;
        var te=/^(1)(3[0-9]|4[0-9]|5[0-9]|7[0-9]|8[0-9])[0-9]{8}$/;
        if(loginUser.length == 0){
        	msgtips("请输入您的账号！");
            
           	return;
        }
      	else if(!te.test(loginUser)){
      		msgtips("请使用正确的手机号登录！");
        	
            return;
        }else if(loginPwd.length == 0){
        	msgtips("请输入登录密码！");
             return;
        }
        else if(!p.test(loginPwd)){
            $("#pwd1").addClass("a-shake");
            setTimeout(function(){
               	$("#loginPwd").removeClass("a-shake");
            },2000)
            return; 
        }
       
        
        
        $.ajax({
            url: urls + "app/Index/wapLogin",
            type : 'post',
            dataType : 'json',
            data : {
          		loginUser : loginUser,
                loginPwd : loginPwd
               
            },
            success:function(ret){
               	if(ret){
                	if(ret.error==1){
                      
                        msgtips("登录失败！手机号或密码错误！");
                        
                        return false;
                        
                   }else{
                        msgtips("登录成功！");

                        $.cookie('login','true',{ path: '/'});
                        $.cookie('phone',ret.login[0].phone,{path: '/'});
                        $.cookie('userId',ret.login[0].id,{ path: '/'});
                        $.cookie('username',ret.login[0].username,{ path: '/'});
                        $.cookie('usertype',ret.login[0].usertype,{ path: '/'});
                     	setTimeout(function(){
                     		window.location.href = "../Myinfor/Index";
                     	},2000);
                        
                        return false;
                    }
                }else{
                	msgtips("网络出现问题了！");
                	return false;
                }
            }
        })
       
	})
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
