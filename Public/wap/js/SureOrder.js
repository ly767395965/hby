var jwd = [];
var lng, lat;
var couponid = "";//优惠id
var usertype = $.cookie("usertype");
$(function() {
	var startTime = GetQueryString("startTime");
	var endTime = GetQueryString("endTime");
	var isFav = GetQueryString("isFav");
	var sendPrize = parseFloat(GetQueryString("sendPrize"));
	var senddays = GetQueryString("senddays");
	var today = GetQueryString("today");
	var allPrize = parseFloat(GetQueryString("allPrize"));
	var renday = parseFloat(GetQueryString("renday"));
	var Carid = GetQueryString("Carid"); //当前车辆id
	var typeid = GetQueryString("typeid"); //当前优惠车辆的typeid
	var userid = $.cookie("userId"); //id 当前登录用户的id
	var phone = $.cookie("phone"); //电话

	var isSendCar = 0;
	var isdriving = 0;
	var subject = "";
	var body = "";
	var amount = "";
	var tradeNO = "";
	var ispay = false;
	var allUrl = '';
	var lonat = "";//储存经纬度
	var	ress = "";//储存地址

	if(isFav == "isFav") {
		allUrl = urls + "/app/Index/favorable?id=" + Carid;
	} else {
		allUrl = urls + "/app/Index/car?carid=" + Carid;
	}
	$.ajax({
		url: allUrl,
		type: 'get',
		dataType: 'json',
		data: {},
		success: function(ret) {
			if(ret.error == 0) {
				$(".orderInfo-img img").attr("src", urls + '/Public' + ret.car[0].frontimg);
				$("#pp").html(ret.car[0].brand);
				$("#cartype").html(ret.car[0].carmodelname);
				$("#orderCarInfo span").eq(0).html(ret.car[0].agestyle);
				$("#orderCarInfo span").eq(1).html(ret.car[0].sitecount);
				$("#orderCarInfo span").eq(2).html(ret.car[0].bearboxtype);
				$("#orderCarInfo span").eq(3).html(ret.car[0].displacement);
				$("#startTime").html(startTime);
				$("#endTime").html(endTime);
				$("#orderPrizeType").html(sendPrize);
				$("#renTime").html(senddays);
				$("#allPrize").html(allPrize);
				$("#showAll").html(allPrize);

				if(isFav == "isFav" || usertype == 1) {
					$("#coup").css("display", "none");
				} else {
					$("#showAll").attr('class',allPrize);
					DBF(allPrize);

				}
			} else {
				return false;
			}

			//选择服务
			$("#driving").on("click", function() {
				$(this).toggleClass("on");
				if($(this).hasClass("on")) {

					isdriving = 1;

					$("#dj").html(Math.round(renday * 200));
					//判断 优惠券是否被点击
					if($(".Couponbtn").find(".checkbox").hasClass("on")) {
						//判断获取到的优惠券的类型 0现金抵扣券/1折扣券
						if($(".Couponbtn").attr("da") == 0) { //现金券
							var nums = $(".Couponbtn").attr("id"); //获取优惠券的抵用金额/折扣百分比
							var allmoneys = Math.round((renday * 200) + allPrize) - nums;
							if (allmoneys <0){
								allmoneys = 0;
							}
							$("#allPrize").html(allmoneys); //计算总价
							$("#showAll").html(allmoneys);


						} else if($(".Couponbtn").attr("da") == 1) { //折扣券

							$("#allPrize").html(Math.round(((renday * 200) + allPrize)) * nums); //计算总价
							$("#showAll").html(Math.round(((renday * 200) + allPrize)) * nums);
							var almors = Math.round(((renday * 200) + allPrize)) * nums;

						}
					} else {
						$("#allPrize").html(Math.round((renday * 200) + allPrize));
						$("#showAll").html(Math.round((renday * 200) + allPrize));

						var qian = Math.round((renday * 200) + allPrize);
						$("#showAll").attr('class',qian);
						DBF(qian)

					}


				} else {//代驾取消
					isdriving = 0;

					$("#dj").html("0");
					//判断 优惠券是否被点击
					if($(".Couponbtn").find(".checkbox").hasClass("on")) {
						//判断获取到的优惠券的类型 0现金抵扣券/1折扣券
						if($(".Couponbtn").attr("da") == 0) {
							var po = $(".Couponbtn").attr("id");
							var allmoneys = Math.round((((renday * 200) + allPrize - (renday * 200))) - po);
							if (allmoneys <0){
								allmoneys = 0;
							}
							$("#allPrize").html();
							$("#showAll").html(allmoneys);

							var qian = Math.round((((renday * 200) + allPrize - (renday * 200))) - po);

						} else if($(".Couponbtn").attr("da") == 1) {
							var po = $(".Couponbtn").attr("id");

							$("#allPrize").html(Math.round((((renday * 200) + allPrize) - (renday * 200)) * po));
							$("#showAll").html(Math.round((((renday * 200) + allPrize) - (renday * 200)) * po));
							var qian = Math.round((((renday * 200) + allPrize) - (renday * 200)) * po);

						}
					} else {
						$("#allPrize").html(Math.round(((renday * 200) + allPrize) - (renday * 200)));
						$("#showAll").html(Math.round(((renday * 200) + allPrize) - (renday * 200)));
						var qian = Math.round(((renday * 200) + allPrize) - (renday * 200));
						$("#showAll").attr('class',qian);
						DBF(qian)
					}
				}
			});

			//是否送车
			$("#sCar").on("click", function() {

				$(this).toggleClass("on");
				if($(this).hasClass("on")) {
					isSendCar = 1;
					$(".sc").css({ color: "#333" });
					$(".adress").slideDown();
					var map, geolocation;
					//加载地图，调用浏览器定位服务
					map = new AMap.Map('adress', {
						resizeEnable: true
					});
					map.plugin('AMap.Geolocation', function() {
						geolocation = new AMap.Geolocation({
							enableHighAccuracy: true,//是否使用高精度定位，默认:true
							timeout: 10000,          //超过10秒后停止定位，默认：无穷大
							buttonOffset: new AMap.Pixel(10, 20)//定位按钮与设置的停靠位置的偏移量，默认：Pixel(10, 20)

						});
						map.addControl(geolocation);
						geolocation.getCurrentPosition();
						AMap.event.addListener(geolocation, 'complete', onComplete);//返回定位信息
						
					});

				} else {
					$(".sc").css({ color: "#888" });
					$(".adress").slideUp();
					isSendCar = 0;
				}
			});
			//解析定位结果
			function onComplete(data) {

				lonat = data.position.getLng()+','+data.position.getLat();
				changeaddress(data.position.getLng(),data.position.getLat());

			}
			//逆地址转换
			function changeaddress(lon,lat) {
				var lnglatXY = [lon, lat]; //已知点坐标
				var geocoder = new AMap.Geocoder({
					radius: 1000,
					extensions: "all"
				});
				geocoder.getAddress(lnglatXY, function(status, result) {
					if (status === 'complete' && result.info === 'OK') {
						geocoder_CallBack(result);
					}
				});

			}
			//返回地址描述
			function geocoder_CallBack(data) {
				var address = data.regeocode.formattedAddress; //返回地址描述
				document.getElementById("adress").innerHTML = address;
			}
			//优惠券
			$("#Couponbtn").on("click", function() {

				$(this).toggleClass("on");
				$('#activity').removeClass("on");
				$(".Couponbtn").empty();

				if($(this).hasClass("on")) {
					$(".Couponbtn").slideDown();
					$.ajax({
						url: urls + "/app/Index/useCoupon",
						type: 'get',
						dataType: 'json',
						data: {
							uid: userid
						},
						success: function(ret) {
							if(ret) {
								var str = "";
								var coupon_name = "";

								for(var i = 0; i < ret.length; i++) {

									if(ret[i].coupon_type==0){
										coupon_name = ret[i].money + "元-现金卷(" + ret[i].coupon_name+')';
									}
									if(ret[i].coupon_type==1){
										coupon_name = ret[i].discount*10 + "折-现金卷(" + ret[i].coupon_name+')';

									}


									str += '<span class="Couponbtn-l" id="' + ret[i].id + '" >' +
										'<div class="checkbox Couponbtn_cou" id="' + ret[i].coupon_type + '">' +
										'</div> ' + coupon_name +
										'<span id="use_limit" class = "' + ret[i].use_limit + '" use_condition="'+ret[i].use_condition+'"></span>' +
										'<div id="Cou_val" class="' + ret[i].money + '" data="' + ret[i].discount + '" style="display: none;"></div>' +
										'</span>';

								}


								//添加优惠券到页面。 显示当前用户可用优惠券
								$(".Couponbtn").append(str);
								$(".Couponbtn span").on("click", function() {

									var use_limit = parseInt($(this).find("#use_limit").attr("class")); //当前优惠券的使用限制
									var use_condition = parseInt($(this).find("#use_limit").attr("use_condition")); //当前优惠券的具体使用限制
									var coupon_type = $(this).find(".checkbox").attr("id"); //当前优惠券的类型 （现金券/折扣券）
									var monyy = parseInt($(this).find("#Cou_val").attr("class")); //当前优惠券的现金抵用值
									var discounts = parseInt($(this).find("#Cou_val").attr("data")); //当前优惠券的折扣值

									if(use_limit < 2) {//判断是否有限制条件
										if($("#driving").hasClass("on")) {//判断代驾是否被选中
											var deo = Math.round(((renday * 200) + allPrize));//计算总价
										} else {
											var deo = Math.round(((renday * 200) + allPrize) - (renday * 200));
										}
										switch (use_limit){
											case 0:
												if (deo < use_condition){
													var min_con = '使用该优惠劵的最低消费为:'+use_condition;
												}
												break;
											case 1:
												if (renday < use_condition){
													var min_con = '使用该优惠劵的最低租车时长为:'+use_condition;
												}
												break;
										}
										if(deo < min_con) {//判断总价是否大于最低消费 小于返回false
											msgtips(min_con);
											return false;
										}
									}

									//判断抵用券是否大于当前租车价

									//优惠券选择
									$(this).find(".checkbox").toggleClass("on");
									if($(this).find(".checkbox").hasClass("on")) {
										couponid = $(this).attr("id");
										if(coupon_type == 0) {//判断优惠券类型  现金券
											if($("#driving").hasClass("on")) {//判断代驾是否被选择
												var sum = Math.round(((renday * 200) + allPrize) - monyy);//计算总价
											} else {
												var sum = Math.round((((renday * 200) + allPrize) - (renday * 200)) - monyy);
											}
											var db = monyy;
											$(".Couponbtn").attr("id", db);//设置 优惠券的抵用金
											$(".Couponbtn").attr("da", coupon_type);//设置优惠券类型
											if (sum <0){
												sum = 0;
											}
											$("#allPrize").html(sum);//页面显示总价
											$("#showAll").html(sum);
											$("#showAll").attr('class',sum);
										} else if(coupon_type == 1) {//折扣券
											if($("#driving").hasClass("on")) { //判断代驾是否被选择
												var pro = Math.round(((renday * 200) + allPrize) * (discounts / 100));//计算总价
											} else {
												var pro = Math.round(((((renday * 200) + allPrize) - (renday * 200))) * (discounts / 100));
											}
											var db = discounts / 100;
											$(".Couponbtn").attr("id", db);//设置 折扣券 百分比
											$(".Couponbtn").attr("da", coupon_type);//设置优惠券类型

											$("#allPrize").html(pro);//页面显示总价
											$("#showAll").html(pro);
											$("#showAll").attr('class',pro);
										}
										$("#Cou_val").html("1");
									} else {
										if($("#driving").hasClass("on")) {//判断代驾是否被选择
											var zero = Math.round(((renday * 200) + allPrize));
										} else {
											var zero = Math.round((((renday * 200) + allPrize) - (renday * 200)));
										}

										$("#allPrize").html(zero);
										$("#showAll").html(zero);
										$("#showAll").attr('class',zero);
										$("#Cou_val").html("");
									}

									$(this).siblings().find(".checkbox").removeClass("on");//移除 同级 元素 的 on样式 ，实现单选

								});

							} else {
								msgtips("您没有可用的优惠劵哦~")
							}

						}
					})
				} else {

					$(".Couponbtn").slideUp();
					$(".Couponbtn").empty();

				}
				if($("#driving").hasClass("on")) {//判断代驾是否被选中
					var deo = Math.round(((renday * 200) + allPrize));//计算总价
				} else {
					var deo = Math.round(((renday * 200) + allPrize) - (renday * 200));
				}
				DBF(deo);
			});

			//优惠活动
			$("#activity").on("click", function() {

				$(this).toggleClass("on");
				$('#Couponbtn').removeClass("on");
				$(".Couponbtn").empty();
				if($("#driving").hasClass("on")) {//判断代驾是否被选中
					var deo = Math.round(((renday * 200) + allPrize));//计算总价
				} else {
					var deo = Math.round(((renday * 200) + allPrize) - (renday * 200));
				}
				if ($("#activity").hasClass("on")){

				}
				DBF(deo);
				
			});

			//取消交易
			$("#payBg,#nopay").on("click", function() {
				$("#payBg").fadeOut();
				$("#payMain").css({ transform: "translateY(250px)" });
				msgtips('取消交易');

			});
			//地理编码返回结果展示
			function geocoder_CallBack(data) {

				//地理编码结果数组
				var geocode = data.geocodes;
				if(geocode!=''){
					for (var i = 0; i < geocode.length; i++) {
						//拼接输出html

						lonat =  geocode[i].location.getLng() + ", " + geocode[i].location.getLat()//重新赋值经纬度
					}
				}
				else{
					msgtips('送车地址有误！');
				}
				map.setFitView();
				document.getElementById("result").innerHTML = resultStr;
			}
			//提交订单
			$("#subOrder").on("click", function() {
				if($("#sCar").hasClass("on") && ($.trim($("#adress").val()) == "" || $.trim($("#adress").val()) == null)) {
					msgtips('请填写正确地址！');

					return false;
				}else{
					if($("#Couponbtn").hasClass("on") && ($(".Couponbtn span").find("#Cou_val").html()=="")) {
						msgtips('请取消优惠券选项！');

						return false;
					}


					$("#payBg").fadeIn();
					$("#pay").removeAttr("disabled");
					$("#payMain").css({ transform: "translateY(0)" }).on("click", function(e) {
						e.stopPropagation();
					});
					var geocoder = new AMap.Geocoder({
						city: "010", //城市，默认：“全国”
						radius: 1000 //范围，默认：500
					});
					//地理编码,返回地理编码结果
					geocoder.getLocation("北京市海淀区苏州街", function(status, result) {
						if (status === 'complete' && result.info === 'OK') {
							geocoder_CallBack(result);
						}
					});



				}




			});

			//支付
			$("#pay").on('click', function() {

				var urserId = userid;
				var carId = 0;

				if(isFav == "isFav") {
					carId = Carid;
					typeid = typeid;
				} else {
					carId = 0;
					typeid = Carid;
				}
				var djPrize = $("#dj").text();
				var allPrizes = allPrize;
				// var allPrizes = $("#showAll").attr('class');
				var carAD = $("#adress").val();
				var stardate = startTime.replace("年", "-").replace("月", "-").replace("日", "").replace("时", ":").replace("分", "");
				var enddate = endTime.replace("年", "-").replace("月", "-").replace("日", "").replace("时", ":").replace("分", "");


				//订单生成接口
				$.ajax({
					url: urls + '/app/Index/orderTrade',
					type: 'POST',
					dataType: 'json',
					data: {
						urserId: urserId, //用户id
						carId: carId, //优惠车辆  等于0时为普通车辆
						startTime: stardate, //取车时间
						endTime: enddate, //还车时间
						sendPrize: sendPrize, //单价
						allPrize: allPrizes , //总价
						djPrize: djPrize, //代驾费
						carAD: carAD, //送车地址
						isSendCar: isSendCar, //是否送车 0不送 1送
						isdriving: isdriving, //是否代驾  0不需要  1需要
						typeid: typeid, //车辆id
						couponid:couponid //优惠券id
					},
					success: function(ret) {

						$("#pay").attr("disabled", true);
						$("#payBg").fadeOut();
						$("#payMain").css({ transform: "translateY(250px)" });
						window.location.href = urls + 'app/Index/wapPay?tradeId=' + ret.msg.tradeId;

					}
				})
			})
		}
	})

	//活动价格计算
	function DBF(v) {
		var timess = startTime.split(" ");
		var timessone = timess[0].replace("年", "").replace("月", "").replace("日", "");
		var stardate = startTime.replace("年", "-").replace("月", "-").replace("日", "").replace("时", ":").replace("分", "");
		var enddate = endTime.replace("年", "-").replace("月", "-").replace("日", "").replace("时", ":").replace("分", "");
		var senddayss = senddays.split(/[天|小时]/);
		var num = senddayss[0];
		if(senddayss[1]>=2&&senddayss[1]<=6){
			num = parseInt(senddayss[0])+0.5;

		}
		else if(senddayss[1]>6){
			num = parseInt(senddayss[0])+1;

		}
		if (active){
			$('.activity').show();
		}else{
			$('.activity').hide();
		}

		if (active && !$("#Couponbtn").hasClass("on")){
			var pk_date = $('#pk_date').val();
			var re_date = $('#re_date').val();
			var sum;

			var reg = couponClass(active,Date.parse(new Date())/1000,v,stardate,enddate);
			if (reg[0]['coupon_pass'][2]){
				sum = v - reg[0]['coupon_pass'][2]['specific'];
				if (sum < 0){sum = 0;}
				$("#allPrize").css("text-decoration","line-through");
				$("#hysp").css("display","block");
				$("#yhmoney").html(sum);
				$("#yhmo").html(sum);
				$('#showAll').html(v);
				$("#showAll").css("text-decoration","line-through");
				$("#showAll").attr('class',sum);
				$("#yh").css("display","block");
				$('#allPrize').html(v);
				couponAct(1,active)
			}else{
				$("#coup").css("display", "block");
				$(".Couponbtn").attr("id", allPrize)
				couponAct(1,active)
			}
		}else {
			couponAct(0,active)
			$("#allPrize").css("text-decoration","none");
			$("#hysp").css("display","none");
			$("#yhmoney").html(v);
			$("#yhmo").html(v);
			$("#showAll").css("text-decoration","none");
			$("#showAll").attr('class',v);
			$("#yh").css("display","none");
			$('#allPrize').html(v);
			$('#showAll').html(v);
		}

	}



});

function msgtips(msgs) {
	$(".msg").css("display", "block");
	$(".msg-text").html(msgs).addClass("a-shake");
	setTimeout(function() {
		$(".msg-text").removeClass("a-shake");
		$(".msg").css("display", "none");
	}, 2000);

}

function msgYes(msgs) {
	$(".msg").css("display", "block");
	$(".msg-text").html(msgs).addClass("a-fadein");
	setTimeout(function() {
		$(".msg-text").removeClass("a-fadein");
		$(".msg").css("display", "none");
	}, 2000);
}

//优惠活动栏控制方法
function couponAct(type,active) {
	if (type){
		$(".Couponbtn").slideDown();
		if (!$("#activity").hasClass("on")){
			$("#activity").toggleClass("on");
		}
		// var str = '<span class="Couponbtn-l" id="' + ret[i].id + '" >' +
		// 	'<div class="checkbox Couponbtn_cou" id="' + ret[i].coupon_type + '">' +
		// 	'</div> ' + 6666 +
		// 	'<span id="use_limit" class = "' + ret[i].use_limit + '" use_condition="'+ret[i].use_condition+'"></span>' +
		// 	'<div id="Cou_val" class="' + ret[i].money + '" data="' + ret[i].discount + '" style="display: none;"></div>' +
		// 	'</span>';
		var str = '<div class="act_introduce">活动名称:<span>'+active[0]['name']+'</span></div><div class="act_introduce">活动简介:<span>'+active[0]['act_synopsis']+'</span></div><div class="act_introduce">活动内容:<span>'+active[0]['act_content']+'</span></div><div class="act_introduce">活动时间:<span>'+active[0]['start_time']+' 至 '+active[0]['end_time']+'</span></div>';

		//添加优惠活动到页面。 显示当前用户可参与的优惠活动
		$(".Couponbtn").append(str);
	}else {
		$("#activity").removeClass("on");
	}
}

