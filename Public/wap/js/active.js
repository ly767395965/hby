
$(function() {
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
	$.ajax({
		type: "get",
		url: urls + "App/index/activity?pagenum=7&p=1",
		async: true,
		dataType: 'json',
		success: function(msg) {
			if(msg.error == 1) {
				$("#dbNum").html("没有数据了");
			} else {
				var imgUrl = "";
				var str = '';
				for(var i = 0; i < msg.car.length; i++) {
					if(msg.car[i].cover == "" || msg.car[i].cover == null) {
						imgUrl = "../images/default-img.jpg";
					} else {
						imgUrl = urls + "/Public" + msg.car[i].cover;
					}
					str += '<div class="bbb" id="' + msg.car[i].id + '">' +
						'<img src="' + imgUrl + '"/>' +
						'<div class="trip-list-title">' +
						'<div class="trip-list-trip1">' + msg.car[i].theme + '</div>' +
						'<div class="trip-list-trip2">' + msg.car[i].describetxt + '</div>' +
						'</div></div>';
				}
				$("#youhuiList").append(str);
			}
			//详情
			$("#youhuiList").on("click", ".bbb", function() {
				var conId = $(this).attr("id");
				setTimeout(function() {
					window.location.href = urls+"App/index/activity?id="+conId;
				},500);
			});
		}
	});

})