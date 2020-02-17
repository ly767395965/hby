/**
 * Created by Administrator on 2017/5/12.
 */
//    节假日活动公共方法begin
function commoncou(v,active) {
    var obj = $('#couponInfoId');
    if ($(obj).val() > 0){
        couponUse(obj,active);
    }else{
        cou1(v,active);
    }

}
//    节假日活动公共方法end

//    节假日活动业务方法begin
function cou1(v,active) {
    var startNum = $("#start").val();
    var days=$('#postDay').val();
    var pk_date = $("#start").val() + " " + $(".startT").val();
    var re_date = $("#end").val() + " " + $(".endT").val();
    var sum;

    if (active['activity_num'] != 0){                                                  //判断活动数量书否为0,如果不为0,则调用函数进行筛选,查看该订单是否满足优惠条件
        // console.log(active);
        var reg = couponClass(active,Date.parse(new Date())/1000,v,pk_date,re_date);
        if (reg[0]['coupon_pass'].length != 0){
            sum = v - reg[0]['coupon_pass'][2]['specific'];
            orderPrice(1,v,1,sum);
        }else{
            orderPrice(0,v);
        }

       }else {
           orderPrice(0,v);
       }
}
//    节假日活动业务方法end

//使用优惠劵进行价格计算
function couponUse(obj,active) {
    var id = $('#couponInfoId').val();
    var couponInfoId =  $(obj).find("option:selected").attr('value');
    var use_limit =  $(obj).find("option:selected").attr('use_limit');
    var use_condition =  $(obj).find("option:selected").attr('use_condition');
    var discount =  $(obj).find("option:selected").attr('discount');
    var atype =  $(obj).find("option:selected").attr('atype');
    var all = $(".bmon").attr("id");
    var days=$("#postDay").val();//租车天数
    var coupon = 0;
    if (couponInfoId != '0') {
        switch (use_limit){
            case '0':
                if (all >= use_condition){
                    coupon = 1;
                }else{
                    coupon = '使用该优惠劵的最低消费为:'+use_condition;
                }
                break;
            case '1':
                if (days >= use_condition){
                    coupon = 1;
                }else{
                    coupon = '使用该优惠劵的最低租车时长为:'+use_condition;
                }
                break;
            case '2':
                coupon = 1;
                break;
        }

        if (coupon == 1){
            var coupon_price = couponprice(all,atype,discount);
            orderPrice(1,all,0,coupon_price);    //改变价格显示的样式
        }else {
            orderPrice(0,all);    //改变价格显示的样式
        }

    }else {
        cou1(all,active);
//                orderPrice(0,all);    //改变价格显示的样式
    }
}


//改变订单总价或优惠价格的样式
function orderPrice(reg,old_price,type,new_price) {
    switch (reg){
        case 0:
            $("#all").html(old_price);
            $('#all').parent('span').css({'color':'#f90','font-size':'24px','text-decoration':'none'});
            $('#clearFix').hide();
            break;
        case 1:
            $("#all").html(old_price);
            $('#all').parent('span').css({'color':'grey','text-decoration':'line-through','font-size':'18px'});
            $('#clearFix').show();
            $('.min-order').text('￥'+new_price).css({'color':'#f90','font-size':'24px'});
            if (type){
                $('#coupon_price').text('活动价格:');
            }else{
                $('#coupon_price').text('优惠价格:')
            }
            break;
    }
}








