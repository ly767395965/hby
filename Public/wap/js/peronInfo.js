
$(function(){
	var id = GetQueryString("id");
	$.ajax({
            url: urls + 'app/Index/member?id='+ id,//电话
            type: 'get',
            dataType : 'json',
        	success:function (ret){
           	console.log(ret)
                if (ret.error == 1) {
                    return false;
                } else {
                    $("#name").val(ret.info[0].username);
                    $("#phone").val(ret.info[0].phone);
                    $("#card").val(ret.info[0].identitys);
                    if(ret.info[0].usertype==0){
                        $("#usertype").val("普通用户");
                    }else{
                        $("#usertype").val("大客户");
                    }
                    if(ret.info[0].check_cycle==0){
                        $("#paytype").val("及时结账");
                    }else{
                        $("#paytype").val(ret.info[0].check_cycle+"个月");
                    }
                    $("#date").val(ret.info[0].addtime);
                }
        	}
        	
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