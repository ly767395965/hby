var Urs;
var oldhar = [];
//时  数组；
var harray = ["8", "9", "10", "11", "12", "13", "14", "15", "16", "17", "18", "19", "20"];
var timesarry = [];
var chushitime = ["8:00", "8:30", "9:00", "9:30", "10:00", "10:30", "11:00", "10:30", "12:00", "12:30", "13:00", "13:30", "14:00", "14:30", "15:00", "15:30", "16:00", "16:30", "17:00", "17:30", "18:00", "18:30", "19:00", "19:30", "20:00", "20:30"];
$(function() {
	var Carid = GetQueryString("id");
	var isFav = GetQueryString("isFav");
	var userid = $.cookie('userId'); //用户id
	var today = '';
	if(isFav!=null&&isFav!="")
	{
		Urs = urls + "app/Index/favorable?id=" + Carid;
	}
	else{
		Urs = urls + "app/Index/car?carid=" + Carid;
	}
	$.ajax({
		url: Urs,
		type: 'get',
		dataType: 'json',
		success: function(ret) {

			if(ret.error == 1) {
				return false;
			} else {
				var strpic = '';
				var picpath = [];
				picpath.push(ret.car[0].frontimg);
				picpath.push(ret.car[0].leftanterior);
				picpath.push(ret.car[0].rightimg);
				picpath.push(ret.car[0].rightfront);
				picpath.push(ret.car[0].behindimg);

				for(var i = 0; i < picpath.length; i++) {
					if(picpath[i] == "" || picpath[i] == null) {
						picurl = "../images/default-img.jpg";
					} else {
						picurl = urls + '/Public' + picpath[i];
					}
					strpic += '<li>' +
						'<a href="#">' +
						'<img src="' + picurl + '"/>' +
						'</a>' +
						'</li>';
				}

				$("#slid ul").empty().html(strpic);
				TouchSlide({
					slideCell: "#banner",
					mainCell: ".bd ul",
					effect: "leftLoop",

				});
				var strTitle = '<p>' + ret.car[0].carmodelname + '</p>' +
					'<span>' + ret.car[0].configstyle + '</span> | ' +
					'<span>' + ret.car[0].bearboxtype + '</span> | ' +
					'<span>' + ret.car[0].fuelstand + '</span>';
				$(".carInfo-title").empty().html(strTitle);
				var strInfo = '<li><i>品牌</i><span>' + ret.car[0].brand + '</span>' +
					'</li><li><i>车型</i><span>' + ret.car[0].carmodelname + '</span>' +
					'</li><li><i>年代款</i><span>' + ret.car[0].agestyle + '</span>' +
					'</li><li><i>配置款</i><span>' + ret.car[0].configstyle + '</span>' +
					'</li><li><i>变速箱类型</i><span>' + ret.car[0].bearboxtype + '</span>' +
					'</li><li><i>座位数</i><span>' + ret.car[0].sitecount + '</span>' +
					'</li><li><i>汽油</i><span>' + ret.car[0].fuelstand + '</span>' +
					'</li><li><i>天窗</i><span>' + ret.car[0].skylight + '</span>' +
					'</li><li><i>座椅类型</i><span>' + ret.car[0].chairtype + '</span>' +
					'</li><li><i>油箱容量</i><span>' + ret.car[0].tankcapacity + '</span></li>';
				$(".car-info ul").empty().html(strInfo);
				var strPrize= "";
				if(isFav == "isFav") {
					today="";

					strPrize = '<input type="hidden" id="yhPrize" value="' + ret.car[0].goodprice + '"/>';
				} else {
					today=ret.today;
					strPrize = '<input type="hidden" id="shortdayprice" value="' + ret.car[0].shortdayprice + '"/>' +
						'<input type="hidden" id="weekdayprice" value="' + ret.car[0].weekdayprice + '"/>' +
						'<input type="hidden" id="monthdayprice" value="' + ret.car[0].monthdayprice + '"/>';
				}
				$("#prizes").empty().html(strPrize);

				/**----------------------------------*/
				var shortdayprice = $("#shortdayprice").val();
				var weekdayprice = $("#weekdayprice").val();
				var monthdayprice = $("#monthdayprice").val();
				var yhPrize = $('#yhPrize').val();
				
				if(GetQueryString("isFav") == "isFav") {
					$("#prize").html(yhPrize);
					$("#daysType").html("特惠").css({ color: "#fa4830" });
					
				} else {
					$("#prize").html(shortdayprice);
				}
				$("#allPrize").val(Math.round($("#prize").text()) * ($("#sendDay").val()));
			}

			/**-------------------------------------------------------*/
			//获取服务器时间
			$.ajax({
				url: urls + 'App/Index/getTime',
				type: 'get',
				dataType: 'json',
				success: function(ret) {

					var times = ret.date.split(" "); //截取时间字符串
					var YMD = times[0].split("-"); //截取年月日
					var Htime = times[1].split(":");
					var HH = Htime[0];
					var MM = Htime[1];
					var Ftimne = YMD[0] + "-" + YMD[1] + "-" + YMD[2];

					if(MM > 30) {
						HH = parseInt(HH) + 1;
						MM = "00";
					} else {
						HH = HH;
						MM = 30;
					}

					HH = parseInt(HH) + 2;
					if(HH > 20) {
						Ftimne = DateAdd(Ftimne, 1);
						HH = 8;
					}

					if(HH < 10) {
						HH = "0" + HH;
					}

					$("#startT").val(HH + ":" + MM);

					$("#endT").val(HH + ":" + MM);

					var ymdsf = Ftimne.split("-");
					$("#startD").val(ymdsf[0] + "年" + ymdsf[1] + "月" + ymdsf[2] + "日");
					$("#endD").val(dateOperator(ymdsf[0] + "年" + ymdsf[1] + "月" + ymdsf[2] + "日", 1, "+"));

					/**--------------------------日历控件控制日期  时间-----------------------------------------------*/
					//判断租车日期是否与当前日期吻合，吻合获取当前时，大于时 时间重置
					var har = [];
					for(var i = 0; i < harray.length; i++) {

						if(parseInt(harray[i]) >= HH) {
							har.push(harray[i]);
						}

					}
					var newdate = ymdsf[0] + "/" + ymdsf[1] + "/" + ymdsf[2];
					oldhar = har;
					//遍历舍去不符租车时间之后的小时
					for(var i = 0; i < oldhar.length; i++) {

						if(MM > 30) {
							MM = "00";
						} else {
							MM = 30;
						}
						timesarry.push(oldhar[i] + ":" + MM) //添加到新的数组 重写时间格式  此处二维数组
						MM += 30;

					}
					timesarry = timesarry; //重写数组
					$.datetimepicker.setLocale('ch'); //设置所有日历为中国字

					//开始日期
					$('#datess').datetimepicker({
						timepicker: false,
						format: 'Y年m月d日',
						minDate: newdate,
						onChangeDateTime: function(dp, $input) {
							$("#startD").val($input.val());
							$("#endD").val(dateOperator($input.val(), 1, "+"));
							var riqi = $input.val().split(/[年|月|日]/);
							var times99 = $("#startT").val();

							/**------------获取页面的值进行价格计算--------------*/
							var getEnd = $("#endD").val() + " " + $("#endT").val();
							var startDate = riqi[0] + "-" + riqi[1] + "-" + riqi[2] + " " + times99;
							var endDate = getEnd.replace("年", "-").replace("月", "-").replace("日", "").replace("时", ":").replace("分", "")
							var renD = Math.floor(diffDate(startDate, endDate, "h") / 24); //天数
							var renH = diffDate(startDate, endDate, "h") % 24; //小时
							var renM = (diffDate(startDate, endDate, "m") / 60) % 24; //分钟

							pDate(renD, renH, renM); //判断租车时间
						}
					});
					//开始租车时间
					$('#timer').datetimepicker({
						datepicker: false,
						format: 'H:i',
						allowTimes: timesarry,
						onChangeDateTime: function(dp, $input) {
							$("#startT").val($input.val());
							/**------------获取页面的值进行价格计算--------------*/
							var riqi = $("#startD").val().split(/[年|月|日]/);

							var getEnd = $("#endD").val() + " " + $("#endT").val();
							var startDate = riqi[0] + "-" + riqi[1] + "-" + riqi[2] + " " + $input.val();
							var endDate = getEnd.replace("年", "-").replace("月", "-").replace("日", "").replace("时", ":").replace("分", "")
							var renD = Math.floor(diffDate(startDate, endDate, "h") / 24); //天数
							var renH = diffDate(startDate, endDate, "h") % 24; //小时
							var renM = (diffDate(startDate, endDate, "m") / 60) % 24; //分钟

							pDate(renD, renH, renM); //判断租车时间

						}

					});

					//结束日期
					$('#hcar').on("click", function() {
						var dates2 = dateOperator($("#startD").val(), 1, "+");
						var newdates2 = dates2.split(/[年|月|日]/);
						var newdates3 = newdates2[0] + "/" + newdates2[1] + "/" + newdates2[2];
						$(this).datetimepicker({
							timepicker: false,
							format: 'Y年m月d日',
							minDate: newdates3, //dateOperator($("#startD").val(), 1, "+"),
							onChangeDateTime: function(dp, $input) {
								$("#endD").val($input.val());

								/**------------获取页面的值进行价格计算--------------*/
								var riqi = $("#startD").val().split(/[年|月|日]/);
								var times99 = $("#startT").val();

								var getEnd = $input.val() + " " + $("#endT").val();
								var startDate = riqi[0] + "-" + riqi[1] + "-" + riqi[2] + " " + times99;
								var endDate = getEnd.replace("年", "-").replace("月", "-").replace("日", "").replace("时", ":").replace("分", "")
								var renD = Math.floor(diffDate(startDate, endDate, "h") / 24); //天数
								var renH = diffDate(startDate, endDate, "h") % 24; //小时
								var renM = (diffDate(startDate, endDate, "m") / 60) % 24; //分钟

								pDate(renD, renH, renM); //判断租车时间
							}
						});
					})

					//结束租车时间

					$('#hcar_time').datetimepicker({
						datepicker: false,
						format: 'H:i',
						allowTimes: chushitime,
						onChangeDateTime: function(dp, $input) {

							$("#endT").val($input.val());
							/**------------获取页面的值进行价格计算--------------*/
							var riqi = $("#startD").val().split(/[年|月|日]/);
							var times99 = $("#startT").val();
							var getEnd = $("#endD").val() + " " + $input.val();
							var startDate = riqi[0] + "-" + riqi[1] + "-" + riqi[2] + " " + times99;
							var endDate = getEnd.replace("年", "-").replace("月", "-").replace("日", "").replace("时", ":").replace("分", "")
							var renD = Math.floor(diffDate(startDate, endDate, "h") / 24); //天数
							var renH = diffDate(startDate, endDate, "h") % 24; //小时
							var renM = (diffDate(startDate, endDate, "m") / 60) % 24; //分钟

							pDate(renD, renH, renM); //判断租车时间

						}

					});
				}
			})
			//判断租车时间
			function pDate(renD, renH, renM) {

				if(renM > 0 && renM <= 2) {
					$("#days").text(renD + "天" + renH + "小时");
					$("#sendDay").val(renD);
					msgtips('小于两小时不计费！');

				} else if(renM > 2 && renM <= 6) {
					$("#days").text(renD + "天" + renH + "小时");
					$("#sendDay").val(renD + 0.5);
					msgtips('大于两小时小于六小时按半天计算！');

				} else if(renM > 6) {
					$("#days").text(renD + "天" + renH + "小时");
					$("#sendDay").val(renD + 1);
					msgtips('大于六小时按一天计算！');

				} else {
					$("#days").text(renD + "天" + renH + "小时");
					$("#sendDay").val(renD);
				}

				var senDay = $("#sendDay").val();
				if(senDay > 0 && senDay < 5) {
					if(isFav == "isFav") {
						$("#daysType").html("特惠").css({ color: "#fa4830" });
						$("#prize").html(yhPrize);
					} else {
						$("#daysType").html("短租");
						$("#prize").html(shortdayprice);
					}
				} else if(senDay > 4 && senDay < 21) {
					if(isFav == "isFav") {
						$("#daysType").html("特惠").css({ color: "#fa4830" });
						$("#prize").html(yhPrize);
					} else {
						$("#daysType").html("周租");
						$("#prize").html(weekdayprice);
					}
				} else if(senDay > 20) {
					if(isFav == "isFav") {
						$("#daysType").html("特惠").css({ color: "#fa4830" });
						$("#prize").html(yhPrize);
					} else {
						$("#daysType").html("长租");
						$("#prize").html(monthdayprice);
					}
				}
				$("#allPrize").val(Math.round($("#prize").text()) * ($("#sendDay").val()));
			}

			//点击预约
			
			$("#order").on("click", function() {

				var isFav = GetQueryString("isFav");//优惠车辆标记
				var Carid = GetQueryString("id");//当前车辆id
				var typeid = GetQueryString("typeid");//当前车辆id
				var startTime = $("#startD").val() + " " + $("#startT").val();
				var endTime = $("#endD").val() + " " + $("#endT").val();
				var sendPrize = $("#prize").text();
				var senddays = $("#days").text();
				var renday = $("#sendDay").val();
				var allPrize = $("#allPrize").val();
				var userid = $.cookie("userId"); //用户id
				// if($.cookie("login") ==null){
		         //    window.location.href = "../Login/Index";
		         //
				// }else{
				// 	window.location.href = "../SureOrder/Index?Carid="+Carid+"&startTime="+startTime+"&endTime="+endTime+ "&sendPrize=" + sendPrize +"&senddays="+senddays+"&renday="+renday+"&allPrize="+allPrize+"&isFav="+isFav+"&typeid="+typeid+"&today="+today+"&userid="+userid;
				//
				// }
				if(renday <= 0) {
					
					msgtips('取车时间不能大于或等于还车时间！');
					
					return false;
				}
				
				
				
			})

		}

	})

})

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