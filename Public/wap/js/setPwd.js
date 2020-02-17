
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
        
        
        //获取验证码
        $(".hQyzm").on("click",function () {
            var mobile=$("#mobile").val();
            $.ajax({
                url : urls + 'app/index/setSms',
                type : 'POST',
                dataType : 'json',
             
                data : {
                
                    mobile : mobile,
                
                },
            	success:function (ret,err) {
	                if(ret){
	                    if(ret.state==0){
	                        api.toast({msg: '发送失败，请检查手机号码是否正确！', location: 'middle'});
	                    }else{
	                        api.toast({msg: '发送成功！', location: 'bottom'});
	                    }
	                }else{
	                    api.toast({msg: '网络出现问题了！', location: 'middle'});
	                }
	            }    
            })
        });
        //修改密码
        $("#surePwd").on("click",function () {
            var mobile=$("#mobile").val();
            var newPwd=$("#newPwd").val();
            var code=$(".yzm").val();
            var p=/^[0-9a-zA-Z_]{6,16}$/;
            var te=/^(1)(3[0-9]|4[0-9]|5[0-9]|7[0-9]|8[0-9])[0-9]{8}$/;
            if(mobile.length == 0){
            	msgtips('请输入手机号码！');
               
                $("#mobile").focus();
                return;
            }
            else if(!te.test(mobile)){
            	msgtips('请输入正确的手机号！');
               
                $("#mobile").val("").focus();
                return;
            }else if(newPwd.length == 0){
            	msgtips('请输入新密码！');
               
                $("#newPwd").focus();
                return;
            }
            else if(!p.test(newPwd)){
            	$("#newPwd").addClass("a-shake");
               	setTimeout(function(){
               		$("#newPwd").removeClass("a-shake");
               	},2000)
               
                $("#newPwd").val("").focus();
                return;
            }else if(code.length == 0){
            	msgtips('请输入验证码！')
                
                $(".yzm").focus();
                return;
            }
            api.ajax({
                url: urls + "app/index/editPass",
                type : 'post',
                dataType : 'json',
                data : {
                    mobile : mobile,
                    newPwd : newPwd,
                    code :code
                },
            	success:function(ret){
                 if(ret){
                      if(ret.error=='noUser'){
                        
                            msgtips("用户不存在!");
                         
                            return;
                        
                      }
                      if(ret.error=='codeError'){
                          
                          	msgtips('验证码错误！')
                          
                           	return;
                         
                      }
                      if(ret.error=='overdue'){
                        
                          	msgtips('验证码已过期！')
                          
                            return;
                        
                      }
                      if(ret.error=='fail'){
                          
                         	msgtips('修改失败！')
                         
                            return;
                          
                      }
                      if(ret.error=='success'){
                            $("#surePwd").html("确定");
                            msgtips("修改成功！")
                            setTimeout(function(){
                              	window.location.href = "index1.html";
                            })
                            return;
                         
                      	}
                 	}else{
                 		msgtips( '网络出现问题了！')
                    
                    	return;
                 	}
                } 
            });

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
                }else {
                    clearInterval(resend);
                    btn.val("获取验证码").attr("disabled",false);
                    $('.hQyzm').css({background:"#00a2e1"});
                }
            }, 1000);
            btn.attr('disabled',true);
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