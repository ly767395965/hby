var urlsd="";

$(function() {
	var pagenum = 5;
	var p = 1;
	var type = 0;
	var userid = $.cookie('userId'); //当前登录用户的id
	var usertype = $.cookie('usertype');
	if(usertype==1){
		$("#kjd").html("可结单");
		$(".screen-ul li").eq(1).css("display","none");
		$(".screen-ul li").css("width","33%");
		alllist(userid, pagenum, p, type,usertype);
		$(".screen-ul").on("click", "li", function () {
			$(this).addClass("on").siblings("li").removeClass("on");
			var index = $(this).index();
			type = index;

			$(".myorderUl").empty();
			var str = '';
			var ispay = false;
			switch (index) {
				case 0:
					alllist(userid, pagenum, p, type,usertype);
					break;
				case 1:
					alllist(userid, pagenum, p, type,usertype);
					break;
				case 2:
					alllist(userid, pagenum, p, type,usertype);
					break;
				case 3:
					alllist(userid, pagenum, p, type,usertype);
					break;
			}
		});
		//上一页
		$(".up").on("click", function () {
			p -= 1;
			if (p < 0) {
				p = 0;
			}
			alllist(userid, pagenum, p, type,usertype);
		})

		//下一页
		$(".down").on("click", function () {
			p += 1;
			alllist(userid, pagenum, p, type,usertype);
		})
	}else {
		alllist(userid, pagenum, p, type,"");
		$(".screen-ul").on("click", "li", function () {
			$(this).addClass("on").siblings("li").removeClass("on");
			var index = $(this).index();
			type = index;
			$(".myorderUl").empty();
			var str = '';
			var ispay = false;
			switch (index) {
				case 0:
					alllist(userid, pagenum, p, type,"");
					break;
				case 1:
					alllist(userid, pagenum, p, type,"");
					break;
				case 2:
					alllist(userid, pagenum, p, type,"");
					break;
				case 3:
					alllist(userid, pagenum, p, type,"");
					break;
			}
		});
		//上一页
		$(".up").on("click", function () {
			p -= 1;
			if (p < 0) {
				p = 0;
			}
			alllist(userid, pagenum, p, type,"");
		})

		//下一页
		$(".down").on("click", function () {
			p += 1;
			alllist(userid, pagenum, p, type,"");
		})
	}
})

function alllist(userid, pagenum, p, type,usertype) {

	$.ajax({
		url: urls + "app/index/carOrder?urserId=" + userid + "&pagenum=" + pagenum + "&p=" + p + "&orderclass=" + type+"&usertype="+usertype,
		type: 'get',
		dataType: 'json',
		success: function(ret) {
			if(ret) {
				console.log(ret)
				if(ret.error == 1) {
					$(".down").attr("disabled", "disabled").css("color", "#999999");
					return false;
				} else {

					$(".myorderUl").empty();
					var imgUrl = '';
					var str = '';
					for(var i = 0; i < ret.orderlist.length; i++) {
						if(ret.orderlist[i].frontimg == "" || ret.orderlist[i].frontimg == null) {
							imgUrl = "../images/default-img.jpg";
						} else {
							imgUrl = urls + "/Public" + ret.orderlist[i].frontimg;
						}
						str += '<li order_state="'+ret.orderlist[i].order_state+'" pay="'+ ret.orderlist[i].pay_way+'" id="' + ret.orderlist[i].id + '" receipts="' + ret.orderlist[i].collections_rec + '"  status="' + ret.orderlist[i].order_state + '"> ' +
							'<div class="orderInfo" id="' + ret.orderlist[i].id + '"><div class="orderInfo-img">' +
							'<img src="' + imgUrl + '" width="95%"/>' +
							'</div><div class="orderInfo-dis"><h4>' +
							'<span id="pp">' + ret.orderlist[i].brand + '</span>' +
							' <span class="cartype" id="cartype">' + ret.orderlist[i].carmodelname + '</span></h4><div class="orderCarInfo" id="orderCarInfo">' +
							'<span>' + ret.orderlist[i].agestyle + '</span>款 | <span>' + ret.orderlist[i].sitecount + '</span>座 | <span>' + ret.orderlist[i].bearboxtype + '</span> | 排量<span>' + ret.orderlist[i].displacement + '</span></div>' +
							'<div class="orderPrize">订单金额:<font color="#f02f08">￥</font><i class="orderPrizeType" id="orderPrizeType">' + ret.orderlist[i].price_rec + '</i></div></div></div>' +
							'<div class="orderStatus"><div class="xdtime"><h6>下单时间</h6><i>' + ret.orderlist[i].order_date + '</i></div>' +
							'<div class="orderStatus-btn"><div class="buttons">' +
							'<div class="cancel" tapmode="hover">取消</div>' +
							'<div class="status"></div>' +
							'</div>' + '<div class="buttons buttons2">' +
							'<div class="refund" tapmode="hover" >申请退款</div>' +
							'<div class="pay" tapmode="hover">付款</div>' +
							'<div class="payed">已付款</div>' +
							'</div></div></div></liorder_state>';
					}
					$(".down").removeAttr("disabled", "disabled").css("color", "#000000");
					$("#myOrder").append(str);
					$("#num").html(p); //设置当前显示页数
					//判断上下页的状态
					if($("#num").html() == 1) {
						$(".up").attr("disabled", "disabled").css("color", "#999999");
					} else {
						$(".up").removeAttr("disabled", "disabled").css("color", "#000000");
					}
					if($("#myOrder").find("li").length < 5) {
						$(".down").attr("disabled", "disabled").css("color", "#999999");
					} else {
						$(".down").removeAttr("disabled", "disabled").css("color", "#000000");
					}
				}

				$("#myOrder li").each(function() {
					/**----------------------------订单状态判订显示按钮-----------------------------------*/
					if ( $.cookie('usertype') == "大客户"){
						if($(this).attr("pay")!=0&&type!=3)
						{
							if(type!=0) {
								$(this).hide();
							}
						}

							if ($(this).attr("order_state") == 0) {
								$(this).find(".pay").show().siblings("div").hide();
								$(this).find(".cancel").show().siblings("div").hide();
							}
							if ($(this).attr("order_state") == 1) {
								$(this).find(".payed").show().siblings("div").hide();
								$(this).find(".cancel").show().siblings("div").hide();
							}
							if ($(this).attr("order_state") == 2) {
								$(this).find(".status").show().html("已派车").css({"color": "#00A2E1"}).siblings("div").hide();
								$(this).find(".buttons2").hide();
							}
							if ($(this).attr("order_state") == 3) {
								$(this).find(".status").show().html("已取车").css({"color": "#ccc"}).siblings("div").hide();
								$(this).find(".buttons2").hide();
							}
							if ($(this).attr("order_state") == 4 && $(this).attr("pay")==0) {
								$(this).find(".status").show().html("已还车").css({"color": "#ccc"}).siblings("div").hide();

								$(this).find(".pay").show();
							}
							if ($(this).attr("order_state") == 5) {
								$(this).find(".status").show().html("已结账").css({"color": "#ccc"}).siblings("div").hide();
								$(this).find(".buttons2").hide();
							}
							if ($(this).attr("order_state") == 6) {
								$(this).find(".status").show().html("待退押金").css({"color": "#00A2E1"}).siblings("div").hide();
								$(this).find(".buttons2").hide();
							}
							if ($(this).attr("order_state") == 7) {
								$(this).find(".status").show().html("正常结单").css({"color": "#00A2E1"}).siblings("div").hide();
								$(this).find(".buttons2").hide();
							}
							if ($(this).attr("order_state") == 11) {
								$(this).find(".status").show().html("退款受理中").css({"color": "#ff8800"}).siblings("div").hide();
								$(this).find(".buttons2").hide();
							}
							if ($(this).attr("order_state") == 12) {
								$(this).find(".status").show().html("退款审核中").css({"color": "#00A2E1"}).siblings("div").hide();
								$(this).find(".buttons2").hide();
							}
							if ($(this).attr("order_state") == 13) {
								$(this).find(".status").show().html("退款完成").css({"color": "#ccc"}).siblings("div").hide();
								$(this).find(".buttons2").hide();
							}
							if ($(this).attr("order_state") == 10 && $(this).attr("receipts") > 0) {
								$(this).find(".refund").show().siblings("div").hide();
								$(this).find(".status").show().html("已取消").css({"color": "#ccc"}).siblings("div").hide();
							}
							if ($(this).attr("order_state") == 10 && $(this).attr("receipts") <= 0) {
								$(this).find(".refund").parents(".buttons").hide();
								$(this).find(".status").show().html("订单已取消").css({
									"color": "#ccc",
									"borderRadius": "20px",
									"boxShadow": "0 0 1px #cacaca inset",
									"padding": "0 5px"
								}).siblings("div").hide();
							}

						if (type==3){
							$(this).find(".pay").hide().siblings("div").hide();


						}

					}
					else {
						if ($(this).attr("status") == 0) {
							$(this).find(".pay").show().siblings("div").hide();
							$(this).find(".cancel").show().siblings("div").hide();
						}
						if ($(this).attr("status") == 1) {
							$(this).find(".payed").show().siblings("div").hide();
							$(this).find(".cancel").show().siblings("div").hide();
						}
						if ($(this).attr("status") == 2) {
							$(this).find(".status").show().html("已派车").css({"color": "#00A2E1"}).siblings("div").hide();
							$(this).find(".buttons2").hide();
						}
						if ($(this).attr("status") == 3) {
							$(this).find(".status").show().html("已取车").css({"color": "#ccc"}).siblings("div").hide();
							$(this).find(".buttons2").hide();
						}
						if ($(this).attr("status") == 4) {
							$(this).find(".status").show().html("已还车").css({"color": "#ccc"}).siblings("div").hide();
							$(this).find(".buttons2").hide();
						}
						if ($(this).attr("status") == 5) {
							$(this).find(".status").show().html("已结账").css({"color": "#ccc"}).siblings("div").hide();
							$(this).find(".buttons2").hide();
						}
						if ($(this).attr("status") == 6) {
							$(this).find(".status").show().html("待退押金").css({"color": "#00A2E1"}).siblings("div").hide();
							$(this).find(".buttons2").hide();
						}
						if ($(this).attr("status") == 7) {
							$(this).find(".status").show().html("正常结单").css({"color": "#00A2E1"}).siblings("div").hide();
							$(this).find(".buttons2").hide();
						}
						if ($(this).attr("status") == 11) {
							$(this).find(".status").show().html("退款受理中").css({"color": "#ff8800"}).siblings("div").hide();
							$(this).find(".buttons2").hide();
						}
						if ($(this).attr("status") == 12) {
							$(this).find(".status").show().html("退款审核中").css({"color": "#00A2E1"}).siblings("div").hide();
							$(this).find(".buttons2").hide();
						}
						if ($(this).attr("status") == 13) {
							$(this).find(".status").show().html("退款完成").css({"color": "#ccc"}).siblings("div").hide();
							$(this).find(".buttons2").hide();
						}
						if ($(this).attr("status") == 10 && $(this).attr("receipts") > 0) {
							$(this).find(".refund").show().siblings("div").hide();
							$(this).find(".status").show().html("已取消").css({"color": "#ccc"}).siblings("div").hide();
						}
						if ($(this).attr("status") == 10 && $(this).attr("receipts") <= 0) {
							$(this).find(".refund").parents(".buttons").hide();
							$(this).find(".status").show().html("订单已取消").css({
								"color": "#ccc",
								"borderRadius": "20px",
								"boxShadow": "0 0 1px #cacaca inset",
								"padding": "0 5px"
							}).siblings("div").hide();
						}
					}
					/**------------------------------------------------------------------------------*/

					$(this).find(".cancel").on("click", function() {
						var btn = $(this);
						var id = $(this).parents("li").attr("id");

						msgtips("是否取消订单?");
						//确定
						$(".yes-btn").on("click", function() {
							$.ajax({
								url: urls + 'app/index/offOrder',
								type: 'get',
								dataType: 'json',
								data: {
									id: id
								},
								success: function(ret) {
									if(ret) {
										if(ret.error == 0) {
											$(".msg").css("display", "none");
											if(btn.parents("li").attr("receipts") > 0) {

												btn.siblings(".status").show().html("已取消").css({ "color": "#ccc" }).siblings("div").hide();
												btn.parents(".buttons").siblings(".buttons").children(".refund").show().siblings("div").hide();
											} else {

												btn.siblings(".status").show().html("订单已取消").css({
													"color": "#ccc",
													"borderRadius": "20px",
													"boxShadow": "0 0 1px #cacaca inset",
													"padding": "0 5px"
												}).siblings("div").hide();
												btn.parents(".buttons").siblings(".buttons").hide();
											}
											msgtips2("已取消订单！")

										}
										return;
									} else {

										return;
									}
								}
							});

						})
						//取消
						$(".no-btn").on("click", function() {
							no_btn();
						})

					});

					/**-------------------退款-------------------------*/
					$(this).find(".refund").on("click", function() {
						var btn = $(this);
						var id = $(this).parents("li").attr("id");

						msgtips("是否申请退款？");

						//确定
						$(".yes-btn").on("click", function() {
							$.ajax({
								url: urls + 'app/index/refundApply',
								type: 'get',
								dataType: 'json',
								data: {
									id: id
								},
								success: function(ret) {
									if(ret) {

										btn.parents(".buttons").siblings(".buttons").children(".status").show().html("退款受理中").css({ color: "#ff8800" }).siblings("div").hide();
										btn.parents(".buttons2").hide();
										msgtips2('退款申请成功')

									} else {
										msgtips2("网络出现问题了！")

										return;
									}
								}
							})
						})
						//取消
						$(".no-btn").on("click", function() {
							no_btn();
						})

					});

					var isclick = false;
					//付款
					$(this).find(".pay").on("click", function() {
						var pay = $(this);
						var id = pay.parents("li").attr("id");
						$("#payBg").fadeIn();
						$("#pay").removeAttr("disabled");
						$("#payMain").css({ transform: "translateY(0)" }).on("click", function(e) {
							e.stopPropagation();
						});
						$("#pay").on('click', function() {
							$("#pay").attr("disabled", true);
							$("#payBg").fadeOut();
							$("#payMain").css({ transform: "translateY(250px)" });
							window.location.href = urls + 'app/index/wapPay?tradeId=' + id;
						})
					})
					//取消交易
					$("#payBg,#nopay").on("click", function() {
						$("#payBg").fadeOut();
						$("#payMain").css({ transform: "translateY(250px)" });

					});

					//查看订单详情
					$("#myOrder").on("click", "li .orderInfo", function() {
						var id = $(this).attr("id");
						window.location.href = '../Orderdetails/index?id=' + id;
					});
				})
			} else {
				msgtips2("您还没有下单哦！")
			}
		}
	})
}

function msgtips(msgs) {
	$(".msg").css("display", "block");
	$(".msg-text").html(msgs);
}

function no_btn() {
	$(".msg").css("display", "none");

}

function msgYes(msgs) {
	$(".msg").css("display", "block");
	$(".msg-text").html(msgs).addClass("a-fadein");
	setTimeout(function() {
		$(".msg-text").removeClass("a-fadein");
		$(".msg").css("display", "none");
	}, 2000);
}

function msgtips2(msgs) {
	$(".msg1").css("display", "block");
	$(".msg-text1").html(msgs).addClass("a-shake");
	setTimeout(function() {
		$(".msg-text1").removeClass("a-shake");
		$(".msg1").css("display", "none");
	}, 2000);

}