var phones = "";
var Name = $.cookie('username');//获取路径上的用户名
var phone = $.cookie('phone');//电话;
var userid =  $.cookie('userId');//id;
var login = $.cookie("login");
var usertype = $.cookie("usertype");
$(function(){
    if(login=="true")
    {
        if (usertype == 1){
            $(".me-list li").eq(1).css("display","none");
        }
        $("#login").html("您好,"+Name);

        $('#userPhone').html(phone);
        $("#login").on("click",function(){
            return false;
        })
        //优惠券
        $("#Coupon").on("click",function(){

            var url = "../Coupon/index";
            window.location.href = url;
        })
        //我的订单跳转
        $("#myOrder").on("click",function(){
            var url = "../MyOrder/Index";
            window.location.href = url;
        });

        $("#home li").click(function(){
            var str = $(this).find('a').attr("href");
            var hr = str + "?userid="+userid+"&phone="+phone+"&username="+Name;
            $(this).find('a').attr("href",hr);
        })
        $("#tuichu").css("display","block");
        $("#tuichu").on("click",function(){
            msgtips("是否注销登录");
            //确定
            $(".yes-btn").on("click",function(){
                clearCookie();
                var url = "../Login/index";
                window.location.href = url;

            })
            //取消
            $(".no-btn").on("click",function(){
                no_btn();
            })
        })

    }
    else{
        $("#login").html("登录/注册");
        $('#userPhone').html("");

        //我的订单跳转
        $("#myOrder").on("click",function(){
            var url = "../Login/Index";
            window.location.href = "../Login/index";
        })
        //优惠券
        $("#Coupon").on("click",function(){

            var url = "../Login/index";
            window.location.href = url;
        })
    }

    function clearCookie() {
        var keys = document.cookie.match(/[^ =;]+(?=\=)/g);
        if (keys) {
            for (var i = keys.length; i--;)
                document.cookie = keys[i] + '=0;expires=' + new Date(0).toUTCString()
        }
    }

    //个人信息
    $("#peronInfo").on("click", function () {
        phones = $('#userPhone').html();
        var url = "../PeronInfo/index?id="+phones;
        if (phones != ""&&phones != null) {
            window.location.href = url ;
            return;
        } else {
            window.location.href = "../Login/index";
            return;
        }
    });
    //修改密码
    $("#setPwd").on("click", function () {
        phones = $('#userPhone').html();
        var url = "../SetPwd/index?id="+phones;
        if (phones != ""&&phones != null) {
            window.location.href = url;
            return;
        } else {
            window.location.href = "../Login/index";
            return;
        }
    });
    //点击登录
    $("#login,.me-header,#user,#userPhone").on("click", function () {
        phones = $('#userPhone').html();
        var url = "../PeronInfo/index?id="+phones;
        if (phones != ""&&phones != null) {
            window.location.href = url;
            return;
        } else {
            window.location.href = "../Login/index";
            return;
        }
    });
    //关于我们
    $("#about").on("click",function(){
        window.location.href = urls +"/app/index/about";
    })
})
function msgtips(msgs){
    $(".msg").css("display","block");
    $(".msg-text").html(msgs);
}
function no_btn(){
    $(".msg").css("display","none");

}
function msgYes(msgs){
    $(".msg").css("display","block");
    $(".msg-text").html(msgs).addClass("a-fadein");
    setTimeout(function(){
        $(".msg-text").removeClass("a-fadein");
        $(".msg").css("display","none");
    },2000);
}
