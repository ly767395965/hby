
$(function() {
	var pagenum = 4;
	var p = 1;
	var type = 0;
	var userid = $.cookie("userId"); //id 当前登录用户的id
	Coupon(userid, pagenum, p, type)
	$(".screen-ul").on("click", "li", function() {
		$(this).addClass("on").siblings("li").removeClass("on");
		var index = $(this).index();
		type = index;
		$(".coupon_ul").empty();
		var str = '';
		var ispay = false;
		switch(index) {
			case 0:
				Coupon(userid, pagenum, p, type);
				break;
			case 1:
				Coupon(userid, pagenum, p, type);
				break;
			case 2:
				Coupon(userid, pagenum, p, type);
				break;
			case 3:
				Coupon(userid, pagenum, p, type);
				break;
		}
	});

	//上一页
	$(".up").on("click", function() {
		p -= 1;
		if(p < 0) {
			p = 0;
		}
		Coupon(userid, pagenum, p, type);
	})

	//下一页
	$(".down").on("click", function() {
		p += 1;
		Coupon(userid, pagenum, p, type);
	})
})

function Coupon(userid, pagenum, p, type) {

	$.ajax({
		url: urls + "/app/index/coupon",
		type: 'get',
		dataType: 'json',
		data: {
			uid: userid,
			pagenum: pagenum,
			p: p,
			type: type
		},
		success: function(ret) {
			if(ret) {
				console.log(ret)
				if(ret.error == 1) {
					$(".down").attr("disabled", "disabled").css("color", "#999999");
					return false;
				} else {

					$(".coupon_ul").empty();
					var imgUrl = '';
					var str = '';
					var Bedueimg = "";
					for(var i = 0; i < ret.length; i++) {
						if(compareDate(ret[i].nowtime, ret[i].termofvalidity) && ret[i].use_type!=1) {
							imgUrl = "/Public/wap/images/type3.png";
							Bedueimg = "/Public/wap/images/guoqi.png";
						} else if(ret[i].coupon_type == 0) {
							//imgUrl = urls + "/Public" + ret[i].imgurl;
							imgUrl = "/Public/wap/images/money_img.png";
							if(ret[i].use_type==1){
								Bedueimg = "/Public/wap/images/useing.png";
							}else {
								Bedueimg ="";
							}
						} else if(ret[i].coupon_type == 1) {
							imgUrl = "/Public/wap/images/Coupon.png";
							if(ret[i].use_type==1){
								Bedueimg = "/Public/wap/images/useing.png";
							}else {
								Bedueimg ="";
							}
						}

						if(ret[i].coupon_type == 0) {
							jname = '￥' + ret[i].money;

						}
						if(ret[i].coupon_type == 1) {
							jname = ret[i].discount / 10 + '<span style = "font-size:0.8em;">折</span>';
						}
						str += '<li class="coupon_ul_li" id="' + ret[i].id + '">' +
							'<div class="li_show" style="background: url(' + imgUrl + ')left top no-repeat;background-size: 100% 100%;">' +
							'<div class="show_top">' +
							'<div class="top_left"><span id="coup_name">' + jname + '</span></div>' +
							'<div class="top_right" style = "background: url(' + Bedueimg + ')left no-repeat;background-size: 70px 38px;"></div>' +
							'</div>' +
							'<div class="show_bottom">' +
							'<span class="Term_validity ">' + ret[i].termofvalidity + '</span>' +
							'<span class="use_explain">使用说明</span>' +
							'</div>' +
							'</div>' +

							'<div class="li_hidde">' +
							'<div class="explain_name">' + ret[i].coupon_name + '</div>' +
							'<div class="explain_time">有效时间</div>' +
							'<div class="explain_exp">' + ret[i].addtime + '&nbsp;&nbsp;至&nbsp;&nbsp;' + ret[i].termofvalidity + '时段内可正常使用，超过指定日期作废处理。</div>' +
							'<div class="use_gz">使用规则</div>' +
							'<ul class="usegz_ul">' +
							'<li>'+ ret[i].info+'</li>' +

							'</ul>' +
							'<div class="use_fw">使用范围</div>' +
							'<div class="fw_dels">仅限线上订单可使用(除优惠车辆订单以外)</div>' +
							'<div style="height: 15px;width: 100%;" class="dubu"></div>' +
							'</div>' +
							'</li>';

					}

					$(".down").removeAttr("disabled", "disabled").css("color", "#000000");
					$(".coupon_ul").append(str);
					$(".coupon_ul li").on("click", ".use_explain", function() {
						$(this).toggleClass("on");
						if($(this).hasClass("on")) {
							$(this).css({
								"background": "url(/Public/wap/images/user_shmi2.png) no-repeat",
								"background-size": " 8px 7px",
								"background-position": " 52px 20px"
							});
							$(this).parents(".coupon_ul_li").find(".li_hidde").slideDown();
							var id = $(this).parents(".coupon_ul_li").attr("id");
							$.ajax({
								url: urls + "/app/index/couponInfo",
								type: 'get',
								dataType: 'json',
								data: {
									id: id,
								},
								success: function(ret) {
									console.log(ret);
								}
							})
						} else {
							$(this).css({
								"background": "url(/Public/wap/images/user_shmi.png) no-repeat",
								"background-size": " 8px 7px",
								"background-position": " 52px 22px"
							});
							$(this).parents(".coupon_ul_li").find(".li_hidde").slideUp();
						}
					})

					$("#num").html(p); //设置当前显示页数
					//判断上下页的状态
					if($("#num").html() == 1) {
						$(".up").attr("disabled", "disabled").css("color", "#999999");
					} else {
						$(".up").removeAttr("disabled", "disabled").css("color", "#000000");
					}
					if($(".coupon_ul").find("li").length < 4) {
						$(".down").attr("disabled", "disabled").css("color", "#999999");
					} else {
						$(".down").removeAttr("disabled", "disabled").css("color", "#000000");
					}
				}
			} else {

			}

		}
	})
}
// 时间比较的方法，如果d1时间比d2时间大，则返回true   
function  compareDate(d1,  d2)  {     
	return  Date.parse(d1.replace(/-/g,  "/"))  >  Date.parse(d2.replace(/-/g,  "/"))   
}  