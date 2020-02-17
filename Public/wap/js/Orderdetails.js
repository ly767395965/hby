
$(function() {
	var id = GetQueryString("id"); //id
	$.ajax({
		url: urls + "app/index/orderPage?id=" + id,
		type: 'get',
		dataType: 'json',
		success: function(ret) {
			console.log(ret);
			if(ret.error == 1) {
				return false;
			} else {
				$("#deal").on("click",function () {
					window.location.href=urls + "wap/Deails/Index?id="+id;
				});
				$("#order_code").html(ret.order[0].order_code);
				$("#pp").html(ret.order[0].brand);
				$("#cartype").html(ret.order[0].carmodelname);
				$("#agestyle").html(ret.order[0].agestyle);
				$("#sitecount").html(ret.order[0].sitecount);
				$("#bearboxtype").html(ret.order[0].bearboxtype);
				$("#displacement").html(ret.order[0].displacement);
				$("#orderPrizeType").html(ret.order[0].price_rec);
				$("#username").html(ret.order[0].username);
				$("#u_price").html(ret.order[0].u_price);
				$("#pk_date").html(ret.order[0].pk_date);
				$("#endTime").html(ret.order[0].re_date);
				$("#renTime").html(ret.order[0].time);
				$("#pk_way").html(ret.order[0].pk_way);
				$("#drive_state").html(ret.order[0].drive_state);
				$("#dp_price").html(ret.order[0].dp_price);
				$("#in_cost").html(ret.order[0].in_cost);
				$("#oil_price").html(ret.order[0].oil_price);
				$("#in_price").html(ret.order[0].in_price);
				$("#tolls").html(ret.order[0].tolls);
				$("#in_dep").html(ret.order[0].in_dep);
				$("#re_price").html(ret.order[0].re_price);
				$("#wash_price").html(ret.order[0].wash_price);
				$("#in_code").html(ret.order[0].in_code);
				$("#frontimg").attr("src", urls+'Public' + ret.order[0].frontimg);
				if(ret.order[0].send_location != null && ret.order[0].send_location != "") {
					$(".adress").show();
					$("#adress").html(ret.order[0].send_location);
				} else {
					$(".adress").hide();
				}

				//订单状态
				if(ret.order[0].order_state == 0) {
					$("#status").html("未付款").css({ color: "#ff8800", boxShadow: "0 0 1px #ff8800 inset" });
				}
				if(ret.order[0].order_state == 1) {
					$("#status").html("已付款").css({ color: "#00A2E1", boxShadow: "0 0 1px #00A2E1 inset" });
				}
				if(ret.order[0].order_state == 2) {
					$("#status").html("已派车").css({ color: "#00A2E1", boxShadow: "0 0 1px #00A2E1 inset" });
				}
				if(ret.order[0].order_state == 3) {
					$("#status").html("已取车").css({ color: "#00A2E1", boxShadow: "0 0 1px #00A2E1 inset" });
				}
				if(ret.order[0].order_state == 4) {
					$("#status").html("已还车").css({ color: "#ccc", boxShadow: "0 0 1px #ccc inset" });
				}
				if(ret.order[0].order_state == 5 && ret.order[0].collections_rec == ret.order[0].price_rec && ret.order[0].check_out == 1) {
					$("#status").html("已结账").css({ color: "#00A2E1", boxShadow: "0 0 1px #00A2E1 inset" });
				}
				if(ret.order[0].order_state == 6) {
					$("#status").html("待退押金").css({ color: "#ff8800", boxShadow: "0 0 1px #ff8800 inset" });
				}
				if(ret.order[0].order_state == 7) {
					$("#status").html("正常结单").css({ color: "#00A2E1", boxShadow: "0 0 1px #00A2E1 inset" });
				}
				if(ret.order[0].order_state == 11) {
					$("#status").html("退款受理中").css({ color: "#ff8800", boxShadow: "0 0 1px #ff8800 inset" });
				}
				if(ret.order[0].order_state == 12) {
					$("#status").html("退款审核中").css({ color: "#ff8800", boxShadow: "0 0 1px #ff8800 inset" });
				}
				if(ret.order[0].order_state == 13) {
					$("#status").html("退款完成").css({ color: "#ccc", boxShadow: "0 0 1px #ccc inset" });
				}
				if(ret.order[0].order_state == 10) {
					$("#status").html("订单已取消").css({ color: "#ccc", boxShadow: "0 0 1px #ccc inset" });
				}
				if (ret.order[0].order_state == 5 && ret.order[0].collections_rec != ret.order[0].price_rec && ret.order[0].check_out == 2){
					var sum = Math.round(ret.order[0].price_rec - ret.order[0].collections_rec);
					$("#status").html("未结清,欠款:￥"+sum).css({ color: "red", boxShadow: "0 0 1px #ccc inset" });
				}
			}

		}
	})
})