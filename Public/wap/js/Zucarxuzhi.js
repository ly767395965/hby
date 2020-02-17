
$(function() {
	var pagenum = 8;
	var p = 1;
	$.ajax({
		url: urls + 'app/Index/notice?pagenum=' + pagenum + '&p=' + p,
		type: 'get',
		dataType: 'json',
		success: function(ret) {

			if(ret) {
				if(ret.error == 1) {

					return false;
				} else {
					var str = '';
					for(var i = 0; i < ret.car.length; i++) {
						str += '<li id="' + ret.car[i].id + '"><div class="notice-list-box">' +
							'<h4>' + ret.car[i].title + '</h4>' +
							'<p>' + ret.car[i].subtitle + '</p>' +
							'</div></li>';
					}
					$(".notice-list").append(str);
					p++;
				}
			} else {

				msgtips('网络出现问题了！');
			}
			//详情
			$(".notice-list").on("click", "li", function() {
				var conId = $(this).attr("id");
				setTimeout(function() {
					window.location.href = urls +  "app/Index/notice?id="+conId;
				})
			});
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