/**
 * Created by Administrator on 2017/6/28.
 */

/*优惠活动处理方法*/


//判断是否可参加某个优惠活动以及该活动能带来的优惠
function couponClass(activity,order_date,price,pk_date,re_date){

    for (var key in activity) {
        var pk_date = Date.parse(new Date(pk_date))/1000;
        var re_date = Date.parse(new Date(re_date))/1000;
        var act_start = Date.parse(new Date(activity[key]['start_time']))/1000;
        var act_end = Date.parse(new Date(activity[key]['end_time']))/1000;
        //判断该订单是否可参与该活动
        switch (activity[key]['partake_type']){
            case 0:
                if (pk_date < act_start || pk_date >= act_end){
                    continue;
                }
                break;
            case 1:
                if (order_date < act_start || order_date >= act_end){
                    continue;
                }
                break;
            case 2:
                if (!((order_date >= act_start && order_date < act_end) && (pk_date >= act_start && pk_date < act_end))){
                    continue;
                }
                break;
            case 3:
                if (!((order_date >= act_start && order_date < act_end) || (pk_date >= act_start && pk_date < act_end))){
                    continue;
                }
                break;
        }

        var rent_time;              //计算租车时长
        if (pk_date < act_start){
            if (re_date >= act_end){
                rent_time = act_end - act_start;
            }else{
                rent_time = re_date - act_start;
            }
        }else{
            if (re_date >= act_end){
                rent_time = act_end - pk_date;
            }else{
                rent_time = re_date - pk_date;

            }
        }
        rent_time = rent_time/86400;    //得出租车时长(单位为天)

        var coupon_pass = [];
        for (var i=0; i<activity[key]['coupon_num']; i++){
            var coupon = activity[key]['coupon'+i];

            var reg = counponPrice(coupon,price,rent_time);
            switch (coupon['type']){
                // case '0':                                                        筛选现金卷
                //     if (reg){
                //         if (coupon_pass[0]){
                //             if (coupon_pass[0]['specific'] < coupon['money']){
                //                 coupon_pass[0]['specific'] = coupon['money'];
                //                 coupon_pass[0]['coupon_num'] = i;
                //             }
                //         }else{
                //             var ary = [];
                //             ary['specific'] = coupon['money'];
                //             ary['coupon_num'] = i;
                //             coupon_pass[0] = ary;
                //         }
                //     }
                //     break;
                // case '1':                                                        筛选折扣卷
                //     if (reg){
                //         if (coupon_pass[1]){
                //             if (coupon_pass[1]['specific'] < coupon['discount']){
                //                 coupon_pass[1]['specific'] = coupon['discount'];
                //                 coupon_pass[1]['coupon_num'] = i;
                //             }
                //         }else{
                //             var ary = [];
                //             ary['specific'] = coupon['discount'];
                //             ary['coupon_num'] = i;
                //             coupon_pass[1] = ary;
                //         }
                //     }
                //     break;
                case '2':                                                         //筛选直接减免,三种筛选都是取该活动下用户可以获取的最大的优惠
                    if (reg){
                        if (coupon_pass[2]){
                            if (coupon_pass[2]['specific'] < coupon['money']){
                                coupon_pass[2]['specific'] = coupon['money'];
                                coupon_pass[2]['coupon_num'] = i;
                            }
                        }else{
                            var ary = [];
                            ary['specific'] = coupon['money'];
                            ary['coupon_num'] = i;
                            coupon_pass[2] = ary;
                        }
                    }
                    break;
            }
        }

        activity[key]['coupon_pass'] = coupon_pass;
    }
    return activity;
}


//根据优惠的限制条件,判断该订单是否可享受该优惠
function counponPrice (coupon,price,rent_time){
    var reg = 0;
    switch (coupon['condition_limit']){
        case '0':
            if (price >= coupon['coupon_condition']){
                reg = 1;
            }
            break;
        case '1':
            if (rent_time >= coupon['coupon_condition']){
                reg = 1;
            }
            break;
        case '2':
            reg = 1;
            break;
    }

    return reg;
}
