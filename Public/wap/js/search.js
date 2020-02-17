
$(function(){
	$("#searchs").on("click",function(){
		var searchmodel = $("#searchT").val();
		
		$.ajax({
			type:"get",
			url: urls + "app/index/search?name="+ searchmodel +"&pagenum=7&p=1 ",
			async:true,
			dataType: 'json',
			success: function(ret){
				
				if(ret.error == 1)
				{
					$(".search-content ul").html("<center><span style='display:block;margin-top:60px;'>没有数据了~!</span></center>");
				}else{
					$(".search-content ul").empty();
					var str = "";
	   				var imgUrl = "";
	    			for (var i = 0; i < ret.carlist.length; i++) {
	        			if (ret.carlist[i].frontimg == "" || ret.carlist[i].frontimg == null){
		            		imgUrl = "../images/default-img.jpg";
		        		} else {
		        			imgUrl = urls + "/Public" + ret.carlist[i].frontimg;
		        		}
			        	str += '<li id="' + ret.carlist[i].id + '"><div class="list-pic">' +
			            '<img src="' + imgUrl + '" />' +
			            '</div><div class="list-dis"><div class="list-dis-left">' +
			           	'<p><span class="txt-line">'+ret.carlist[i].brand+' |</span><span  style="color: #787878;padding-left:5px">' + ret.carlist[i].carmodelname + '</span></p>' +
			           	'<span>' + ret.carlist[i].agestyle + '年</span> <span class="txt-line">|</span> ' +
			            '<span>' + ret.carlist[i].bearboxtype + '</span> <span class="txt-line">|</span> ' +
			            '<span>排量' + ret.carlist[i].displacement + '</span>' +'</span> <span class="txt-line">|</span> '+
			            '<span>' + ret.carlist[i].sitecount + '座</span>' +
			            '</div><div class="list-dis-right"><span></span>' +
			            '<i></i></div><div class="clear"></div><div class="list-dis-line"></div><div class="list-dis-zct">' +
			            '<div>短租:<i class="short">' + ret.carlist[i].shortdayprice + '</i>/天 <span class="txt-line">|</span> 周租:<i class="week">' + ret.carlist[i].weekdayprice + '</i>/天 <span class="txt-line">|</span> 月租:<i class="month">' + ret.carlist[i].monthdayprice + '</i>/天 </div>' +
			            '</div></div></li>';
	    			}
	    			$(".search-content ul").append(str);
					/*点击跳转*/
					$(".search-content").on('click', 'li', function() {
						var lId = $(this).attr("id");
						var url = "../Cardetails/index?id="+lId;
						window.location.href = url;

					});
				}
			}
		});
	})
})