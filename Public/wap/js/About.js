
$(function(){
	$.ajax({
		type:"get",
		url: urls + "app/index/about",
		async:true,
		ssuccess: function(ret){
			console.log(ret)
			
		}
	});
})