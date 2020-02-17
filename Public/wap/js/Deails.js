$(function() {
	var id = GetQueryString("id"); //id
			$.ajax({
				url: urls + "/app/Index/orderPage?id=" + id,
				type: 'get',
				dataType: 'json',
				success: function(ret) {
					if(ret.error == 1) {
						return false;
					} else {
						var str = '';
						for(var i = 0; i < ret.order[0].trade.length; i++) {
							str += '<ul class="orderInfo-ul"><li>' +
								'<span class="orderDate-l">交易订单号</span>' +
								'<span class="orderDate-r" id="username">' + ret.order[0].trade[i].trade_code + '</span></li><li> <span class="orderDate-l">交易时间</span>' +
								'<span class="orderDate-r">' + ret.order[0].trade[i].charge_time + '</span>' +
								'</li><li><span class="orderDate-l">交易金额</span>' +
								'<span class="orderDate-r">￥<i id="u_price">' + ret.order[0].trade[i].charge_sum + '</i></span></li><li><span class="orderDate-l">交易项目</span>' +
								'<span class="orderDate-r" id="endTime">' + ret.order[0].trade[i].charge_road + '</span></li><li>' +
								'<span class="orderDate-l">交易状态</span>' +
								'<span class="orderDate-r" id="renTime">' + ret.order[0].trade[i].pay_way + '</span></li></ul>';
						}
						$("#dealBox").html(str);
					}
				}
			})
		})