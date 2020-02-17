
$(function() {

	$.ajax({
		type: "get",
		url: urls + "app/index/trip?pagenum=7&p=1",
		async: true,
		dataType: 'json',
		success: function(ret) {
			if(ret.error == 1) {
				return false;
			} else {
				var imgUrl = "";
				var str = '';
				for(var i = 0; i < ret.car.length; i++) {
					if(ret.car[i].cover == "" || ret.car[i].cover == null) {
						imgUrl = "../images/default-img.jpg";
					} else {
						imgUrl = urls + "/Public" + ret.car[i].cover;
					}
					str += '<div class="trip-list" id="' + ret.car[i].id + '">' +
						'<img src="' + imgUrl + '" height="90%"/><div class="trip-list-title">' +
						'<div class="trip-list-trip1">' + ret.car[i].title + '</div>' +
						'<div class="trip-list-trip2">' + ret.car[i].subtitle + '</div></div></div>';
				}
				$("#youhuiList").append(str);
			}
			//详情
			$("#youhuiList").on("click", ".trip-list", function() {
				var conId = $(this).attr("id");
				
				setTimeout(function() {
				window.location.href = urls+"app/index/trip?id="+conId;
				},500);
			});
		}
	});
	var len = window.location.search.length;
	if(len > 0) {
		$("#home li").click(function() {
			var Name = GetQueryString("username"); //获取路径上的用户名
			var phone = GetQueryString("phone"); //电话
			var userid = GetQueryString("userid"); //id
			var str = $(this).find('a').attr("href");
			var hr = str + "?userid=" + userid + "&phone=" + phone + "&username=" + Name;
			$(this).find('a').attr("href", hr);
		})
	}

})