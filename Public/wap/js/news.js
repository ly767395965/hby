
$(function() {
	var pagenum = 5; //默认显示条数
	var p = 1; //默认翻页为0
	News(pagenum, p);
	//上一页
	$(".up").on("click", function() {
		p -= 1;
		if(p < 0) {
			p = 0;
		}
		News(pagenum, p);
	})

	//下一页
	$(".down").on("click", function() {
		p += 1;
		News(pagenum, p);
	})

})

function News(pagenum, p) {
	$.ajax({
		url: urls + 'app/index/news?pagenum=' + pagenum + '&p=' + p,
		type: 'get',
		dataType: 'json',
		success: function(ret) {
			console.log(ret)
			$(".news-list").empty(); //每次加载列表先清除上一次的列表数据
			if(ret.error == 0) {

				var imgUrl = '';
				var str = '';
				for(var i = 0; i < ret.car.length; i++) {
					if(ret.car[i].cover == "" || ret.car[i].cover == null) {
						imgUrl = "../images/default-img.jpg";
					} else {
						imgUrl = urls + "/Public" + ret.car[i].cover;
					}
					str += '<li id="' + ret.car[i].id + '" ><div class="news-dis">' +
						'<div class="news-list-title">' + ret.car[i].title + '</div>' +
						'<span>' + ret.car[i].addtime + '</span>' +
						'<p>' + ret.car[i].subtitle + '</p>' +
						'</div><div class="news-img">' +
						'<img src="' + imgUrl + '" width="100%" /></div></li>';
				}
				$(".down").removeAttr("disabled", "disabled").css("color", "#000000");
				$(".news-list").append(str);
				$("#num").html(p); //设置当前显示页数
				//判断上下页的状态
				if($("#num").html() == 1) {
					$(".up").attr("disabled", "disabled").css("color", "#999999");
				} else {
					$(".up").removeAttr("disabled", "disabled").css("color", "#000000");
				}
				if($(".news-list").find("li").length < 5) {
					$(".down").attr("disabled", "disabled").css("color", "#999999");
				} else {
					$(".down").removeAttr("disabled", "disabled").css("color", "#000000");
				}

			} else {
				$(".down").attr("disabled", "disabled").css("color", "#999999");
			}

			//详情
			$(".news-list").on("click", "li", function() {
				var conId = $(this).attr("id");
				setTimeout(function() {
					window.location.href =urls + "app/index/news?id="+conId;
				},500);
			})

		}
	});
}