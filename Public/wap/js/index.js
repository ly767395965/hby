
var timer = ""; //定时器
var ptc = 1; //优惠活动默认翻页
$(function() {
	var len = window.location.search.length;
	var pagenum = 5; //默认显示条数
	var p = 1; //默认翻页为0

	var serach = ""; //初始搜索条件为空
	Banners(); //banner广告图

	Discount(ptc); //优惠活动加载函数
	timer = setInterval(function() {
		Discount(ptc);
	}, 1000);

	Carmodels(pagenum, p); //默认加载车型列表

	//上一页
	$(".up").on("click", function() {
		p -= 1;
		if(p < 0) {
			p = 0;
		}
		Carmodels(pagenum, p);
	})

	//下一页
	$(".down").on("click", function() {
		p += 1;
		Carmodels(pagenum, p);
	})

		//新闻
		$("#news").on("click", function() {
			var url = "../News/index";
			window.location.href = url;
		})
		//须知
		$("#xuzhi").on("click", function() {
			var url = "../Zucarxuzhi/index";
			window.location.href = url;
		})
		//特惠
		$("#tehui").on("click", function() {
			var usertype = $.cookie("usertype");
			var url = "../NoticCar/index";
			if (usertype == 0){
				window.location.href = url;
			} else {
				alert("该优惠活动仅对线上普通会员有效");
				return false;
			}

		})
		//租车
		$("#carrental").on("click", function() {
			var url = "../CarRrental/index";
			window.location.href = url;
		})


})

//首页广告图
function Banners() {
	$.ajax({
		url: urls + "App/Index/banner",
		type: "get",
		data: {},
		async: true,
		dataType: 'json',
		success: function(msg) {

			var str = "";
			for(var i = 0; i < msg.car.length; i++) {
				str += '<li id=' + msg.car[i].classid + '>' +
					'<a href="#">' +
					'<img src="' + urls + "/Public" + msg.car[i].imgurl + '" width="100%" height="140"/>' +
					'</a>' +
					'</li>';
			}
			$("#slid ul").append(str);
			TouchSlide({
				slideCell: "#banner",
				titCell: ".hd ul",
				mainCell: ".bd ul",
				effect: "leftLoop",
				autoPlay: true,
				autoPage: true,
				delayTime: 800,
				interTime: 2000,
			});
		}
	})
}

/*------------优惠活动广告------------*/
function Discount(p) {
	$.ajax({
		url: urls + "App/index/activity?pagenum=2&p=" + p,
		type: "get",
		data: {},
		async: true,
		dataType: 'json',
		success: function(dat) {
			if(dat.error == 1) {
				clearInterval(timer);
			} else {
				console.log(dat.car.length);
				var str = "";
				if(dat.car.length > 1) {
					str += '<div class="swiper-slide">' +
						'<div class="swi-left" id="' + dat.car[0].id + '">' +
						'<img src="' + urls + "/Public" + dat.car[0].cover + '" width="100%" />' +
						'<p class="swi-text">' + dat.car[0].theme + '</p>' +
						'</div>' +
						'<div class="swi-cen"></div>' +
						'<div class="swi-right" id="' + dat.car[1].id + '">' +
						'<img src="' + urls + "/Public" + dat.car[1].cover + '" width="100%" />' +
						'<p class="swi-text">' + dat.car[1].theme + '</p>' +
						'</div>' +
						'</div>';
				} else if(dat.car.length = 1) {
					str += '<div class="swiper-slide">' +
						'<div class="swi-left" id="' + dat.car[0].id + '">' +
						'<img src="' + urls + "/Public" + dat.car[0].cover + '" width="100%" />' +
						'<p class="swi-text">' + dat.car[0].theme + '</p>' +
						'</div>' +
						'<div class="swi-cen"></div>' +
						'</div>';
				}

				ptc++;
				$(".swiper-wrapper").append(str);
				var swiper = new Swiper('.swiper-container', {
					paginationClickable: true,
					spaceBetween: 30,
					centeredSlides: true,
					autoplay: 3000,
					autoplayDisableOnInteraction: false
				});

				//查看优惠活动页
				$(".swiper-wrapper").on("click", ".swi-left", function() {
					var swid = $(this).attr("id");
					window.location.href = urls+"App/Index/activity?id=" + swid;
				})
				$(".swiper-wrapper").on("click", ".swi-right", function() {
					var swid = $(this).attr("id");
					window.location.href = urls+"App/index/activity?id=" + swid;
				})
			}
		}
	})
}

//ajax请求数据函数 -----推荐车列表
function Carmodels(pagenum, p) {
	$.ajax({
		url: urls + "App/Index/car?carclass=tj&pagenum=" + pagenum + "&p=" + p,
		type: "get",
		data: {},
		async: true,
		dataType: 'json',
		success: function(ret) {
			$(".car-list ul").empty(); //每次加载列表先清除上一次的列表数据

			//判断是否有数据，有就加载列表
			if(ret.error == 0) {
				var str = "";
				var imgUrl = "";
				for(var i = 0; i < ret.car.length; i++) {
					if(ret.car[i].frontimg == "" || ret.car[i].frontimg == null) {
						imgUrl = "../images/default-img.jpg";
					} else {
						imgUrl = urls + "/Public" + ret.car[i].frontimg;
					}
					str += '<li id="' + ret.car[i].id + '"><div class="list-pic">' +
						'<img src="' + imgUrl + '" />' +
						'</div><div class="list-dis"><div class="list-dis-left">' +
						'<p><span class="txt-line">' + ret.car[i].brand + ' |</span><span  style="color: #787878;padding-left:5px">' + ret.car[i].carmodelname + '</span></p>' +
						'<span>' + ret.car[i].agestyle + '年</span> <span class="txt-line">|</span> ' +
						'<span>' + ret.car[i].bearboxtype + '</span> <span class="txt-line">|</span> ' +
						'<span>排量' + ret.car[i].displacement + '</span>' + '</span> <span class="txt-line">|</span> ' +
						'<span>' + ret.car[i].sitecount + '座</span>' +
						'</div><div class="list-dis-right"><span></span>' +
						'<i></i></div><div class="clear"></div><div class="list-dis-line"></div><div class="list-dis-zct">' +
						'<div>短租:<i class="short">' + ret.car[i].shortdayprice + '</i>/天 <span class="txt-line">|</span> 周租:<i class="week">' + ret.car[i].weekdayprice + '</i>/天 <span class="txt-line">|</span> 月租:<i class="month">' + ret.car[i].monthdayprice + '</i>/天 </div>' +
						'</div></div></li>';
				}
				$(".down").removeAttr("disabled", "disabled").css("color", "#000000");
				$(".car-list ul").append(str);

				$("#num").html(p); //设置当前显示页数

				//判断上下页的状态
				if($("#num").html() == 1) {
					$(".up").attr("disabled", "disabled").css("color", "#999999");
				} else {
					$(".up").removeAttr("disabled", "disabled").css("color", "#000000");
				}
				if($(".car-list ul").find("li").length < 5) {
					$(".down").attr("disabled", "disabled").css("color", "#999999");
				} else {
					$(".down").removeAttr("disabled", "disabled").css("color", "#000000");
				}

				/*点击跳转*/
				$(".car-list").on('click', 'li', function() {
					var lId = $(this).attr("id");
					window.location.href = "../Cardetails/index?id="+lId;
									
				});

			} else {
				$(".car-list ul").html("<center>没有数据了~！</center>");
				$(".down").attr("disabled", "disabled").css("color", "#999999");
			}

		},
		error: function(XMLHttpRequest, textStatus, errorThrown) {

		},
		complete: function(XMLHttpRequest, textStatus) {
			this; // 调用本次AJAX请求时传递的options参数
		}

	})
}