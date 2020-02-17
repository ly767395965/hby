
$(function() {
	
	var str = '';
	var pagenum = 6;
	var p = 1;
	Noticcar(pagenum, p)
	//上一页
	$(".up").on("click", function() {
		p -= 1;
		if(p < 0) {
			p = 0;
		}
		Noticcar(pagenum, p);
	})

	//下一页
	$(".down").on("click", function() {
		p += 1;
		Noticcar(pagenum, p);
	})

})

function Noticcar(pagenum, p) {
	$.ajax({
		url: urls + 'app/index/favorable?pagenum=' + pagenum + '&p=' + p,
		type: 'get',
		dataType: "json",
		success: function(ret) {

			if(ret.error == 1) {
				$(".down").attr("disabled", "disabled").css("color", "#999999");
				return false;
			} else {
				$(".oddcar").empty(); //每次加载列表先清除上一次的列表数据
				var imgUrl = '';
				var str = '';
				for(var i = 0; i < ret.car.length; i++) {
					if(ret.car[i].leftanterior == "" || ret.car[i].frontimg == null) {
						imgUrl = "../images/default-img.jpg";
					} else {
						imgUrl = urls + "/Public" + ret.car[i].frontimg;
					}
					str += '<li tapmode="hover" id="' + ret.car[i].id + "_"+ret.car[i].typeid+'"><div class="oddcar-img">' +
						'<img src="' + imgUrl + '" /></div><div class="addcar-dis">' +
						'<div class="oddcar-title"><span class="oddcar-title-brand">' + ret.car[i].brand + '</span>' +
						'<span class="carmodelname">' + ret.car[i].carmodelname + '</span></div><div class="oddcarLine"></div>' +
						'<div class="oddcarP"><span class="oddcaricon"></span><span><font color="#ff8800">￥</font>' +
						'<i id="thPrize">' + ret.car[i].goodprice + '</i>元/天</span> <span class="yj">￥<i>' + ret.car[i].shortdayprice + '</i></span>' +
						'</div></div></li>';
				}
				$(".down").removeAttr("disabled", "disabled").css("color", "#000000");
				$(".oddcar").append(str);
				$("#num").html(p); //设置当前显示页数
				$("#dbNum").show().html("上拉显示更多数据");
				//判断上下页的状态
				if($("#num").html() == 1) {
					$(".up").attr("disabled", "disabled").css("color", "#999999");
				} else {
					$(".up").removeAttr("disabled", "disabled").css("color", "#000000");
				}
				if($(".oddcar").find("li").length < 6) {
					$(".down").attr("disabled", "disabled").css("color", "#999999");
				} else {
					$(".down").removeAttr("disabled", "disabled").css("color", "#000000");
				}

				/*点击跳转*/
				$(".oddcar").on('click', 'li', function() {
					var lId = $(this).attr("id");
					var yhuiid = lId.split("_");
					var nocid = yhuiid[0]; 
					var typeid =  yhuiid[1];
					window.location.href = "../Cardetails/Index?id="+nocid+"&isFav=isFav"+"&typeid="+typeid;
					
					
					
										
				});
			}

		}
	});
}