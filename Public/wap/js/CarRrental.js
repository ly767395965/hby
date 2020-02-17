var pagenum = 5;//显示个数
var p = 1;//页数
var brands = "";//品牌
var types = "";//车型
var autos = "";//变速箱
var sorts = "asc";//排序
$(function(){
	
	CarList(pagenum,p,brands,types,autos,sorts);//默认加载车型列表函数
	//上一页
	$(".up").on("click",function(){
		p -= 1;
		if(p < 0){
			p =0; 
		}
		CarList(pagenum,p,brands,types,autos,sorts);
	})
	//下一页
	$(".down").on("click",function(){
		p += 1;
		CarList(pagenum,p,brands,types,autos,sorts);
		
	})
	
	/*-----------金额排序------------*/
	$("#sort").toggle(function(){
		sorts = "desc";
		CarList(pagenum,p,brands,types,autos,sorts);
		$("#sort span").removeClass("shengxu1").addClass("shengxu");
		$("#sort span").html("按价格升序");
	},function(){
		sorts = "asc";
		CarList(pagenum,p,brands,types,autos,sorts);
		$("#sort span").removeClass("shengxu").addClass("shengxu1");
		$("#sort span").html("按价格降序");
		
	})
	/*-----------筛选框-----------*/
	$(".shalou").on("click",function(){
		$(".screening").css("display","block");
		$(".screening-footer").stop().animate({opacity:"1",bottom:"0"},500);
		$(".condition").stop().animate({bottom:"43px"},900);
		/*---------读牌子---------*/
		getBrand(" ");//默认加载牌子
	})
	
	/**-----------筛选取消-----------*/
	$(".scr-exit").on("click",function(){
		$(".screening-footer").stop().animate({bottom:"-43px"},500);
		$(".condition").stop().animate({bottom:"-400px"},900,function(){
			$(".screening").css("display","none");
		});
		
		
	})
	
	/*--------按牌子查询数据库-----------*/
	$("#enList i").on("click", function () {
		var texts = $(this).html();
        $(this).addClass("on").siblings("i").removeClass("on");
        getBrand(texts);
    });
    
    /**----------------所有选中样式-------------*/
    //选择
    $(".screen-info-list span").click(function () {
        $(this).toggleClass("on").siblings("span").removeClass("on");
    });
        
    /**----------------点击确定进行筛选--------------------*/
   	$(".scr-suer").on("click",function(){
   		//车子牌子
   		$(".carBrand span").each(function () {
            if($(this).hasClass("on")){
                brands = $.trim($(this).text());
            }
      	});
      	//车子类型
      	$(".carType span").each(function () {
            if($(this).hasClass("on")){
               	types=$(this).attr("data-id");
            }
        });
        //车子变速箱
        $(".carBsx span").each(function () {
            if($(this).hasClass("on")){
                autos=$.trim($(this).text());
            }
        });
        //加载车型列表
        CarList(pagenum,p,brands,types,autos,sorts);
       
   	})
		
})
//读品牌
function getBrand(abc){
    $.ajax({
        url: urls + 'app/index/brand?en='+abc,
        type: 'get',
        async:true,
       	dataType: 'json',
    	success: function(ret) {
    		console.log(ret);
    		$("#brandList").empty();
	        var str = '';
	        if (ret.error == 1) {
	            $("#brandList").html("<center>暂时没有数据~！</center>");
	               
	        } else {
	            for (var i = 0; i < ret.brand.length; i++) {
	               	str += '<span>' + ret.brand[i].brand + '</span>';
	           	}
	            $("#brandList").append(str);
	        }
	        $("#brandList span").click(function () {
	            $(this).toggleClass("on").siblings("span").removeClass("on");
	        });
	        
        }
    });
}
/*-------------车型列表函数----------------*/
function CarList(pagenum,p,brands,types,autos,sorts){
	$.ajax({
		type:"get",
		url:urls + "app/index/carlist?pagenum=" + pagenum + "&p=" + p+"&brand="+brands+"&type="+types+"&auto="+autos+"&sort="+sorts,
		async:true,
		dataType: 'json',
		success: function(ret) {
			console.log(ret)
			if(ret.error==1){
				$(".home-list").html("<div style='background-color: #E4E4E4;width:100%;height:100px;text-align: center;line-height: 100px;font-size:14px;'>没有数据~！</div>");
			}
			else{
				$(".home-list").empty();//每次加载列表先清除上一次的列表数据
				var str = "";
	   			var imgUrl = "";
	    		for (var i = 0; i < ret.car.length; i++) {
	        		if (ret.car[i].frontimg == "" || ret.car[i].frontimg == null){
		            	imgUrl = "../images/default-img.jpg";
		        	} else {
		        		imgUrl = urls + "/Public" + ret.car[i].frontimg;
		        	}
		        	str += '<li id="' + ret.car[i].id + '"><div class="list-pic">' +
		            '<img src="' + imgUrl + '" />' +
		            '</div><div class="list-dis"><div class="list-dis-left">' +
		           	'<p><span class="txt-line">'+ret.car[i].brand+' |</span><span  style="color: #787878;padding-left:5px">' + ret.car[i].carmodelname + '</span></p>' +
		           	'<span>' + ret.car[i].agestyle + '年</span> <span class="txt-line">|</span> ' +
		            '<span>' + ret.car[i].bearboxtype + '</span> <span class="txt-line">|</span> ' +
		            '<span>排量' + ret.car[i].displacement + '</span>' +'</span> <span class="txt-line">|</span> '+
		            '<span>' + ret.car[i].sitecount + '座</span>' +
		            '</div><div class="list-dis-right"><span></span>' +
		            '<i></i></div><div class="clear"></div><div class="list-dis-line"></div><div class="list-dis-zct">' +
		            '<div>短租:<i class="short">' + ret.car[i].shortdayprice + '</i>/天 <span class="txt-line">|</span> 周租:<i class="week">' + ret.car[i].weekdayprice + '</i>/天 <span class="txt-line">|</span> 月租:<i class="month">' + ret.car[i].monthdayprice + '</i>/天 </div>' +
		            '</div></div></li>';
	    		}
	    		$(".home-list").append(str);
	    		$("#num").html(p);//设置当前显示页数
	    		//判断上下页的状态
   				if($("#num").html() == 1)
   				{
   					$(".up").attr("disabled","disabled").css("color","#999999");
   				}
   				else{
   					$(".up").removeAttr("disabled","disabled").css("color","#000000");
   				}
   				if($(".home-list").find("li").length < 5)
   				{
   					$(".down").attr("disabled","disabled").css("color","#999999");
   				}
   				else{
   					$(".down").removeAttr("disabled","disabled").css("color","#000000");
   				}
   				//数据加载成功退出筛选界面
   				$(".screening-footer").stop().animate({bottom:"-43px"},500);
				$(".condition").stop().animate({bottom:"-400px"},900,function(){
					$(".screening").css("display","none");
				});
				
				
				
				/*点击跳转*/
				$(".home-list").on('click', 'li', function() {
					var lId = $(this).attr("id");
					var url = "../Cardetails/index?id="+lId;
					window.location.href = url;
									
				});
    		}
			
		}
	});
}
