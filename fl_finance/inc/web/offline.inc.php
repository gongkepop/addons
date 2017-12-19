<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
global $_W,$_GPC;
$nav="log";
$task=$_GPC['task']?$_GPC['task']:"index";

//检测
if(!basefun()->getplugin("fl_card")){
	message("您尚未安装会员卡，请联系管理员安装后访问。");
}

if($task=="list"){
	
	//查看店铺
	$sql="select * from ".tablename("super_shops")." where weid={$_W['weid']}";
	$shop=pdo_fetchall($sql);
	
	//查询店员
	if($_GPC['shop_id']){
		$sql="select * from ".tablename("super_staff")." where weid={$_W['weid']} and sid={$_GPC['shop_id']}";
		$staff=pdo_fetchall($sql);
		
		
	}
	
	
	$where="";
	
	$pagenum=10;
	$page=$_GPC['page']?$_GPC['page']:1;
	
	//搜索
	if($_GPC['shop_id']>0){
		$where.=" and s.sid={$_GPC['shop_id']}";
	}
	if($_GPC['staff_id']>0){
		$where.=" and po.staff_id={$_GPC['staff_id']}";
	}
	
	if($_GPC['pay_status']>0){
		if($_GPC['pay_status']==1){
			$where.=" and po.paystatus in (1,3) ";
			
		}elseif($_GPC['pay_status']==2){
			$where.=" and po.paystatus in (2,3) and po.paymoney>0 ";
		}elseif($_GPC['pay_status']==3){
			$where.=" and po.paystatus=4 ";
		}
		//$where.=" and cl.pay_status={$_GPC['pay_status']}";
	}
	
	if($_GPC['is_member']>0){
//		if($_GPC['is_member']==2){
//			
//			$where.=" and cl.card_num>0 ";
//		}else{
//			$where.=" and (cl.card_num=0 or cl.card_num is null) ";
//		}
		
	}
	
	if($_GPC['paytime']){
		$startime=strtotime($_GPC['paytime']['start']);
		
		$endtime=strtotime($_GPC['paytime']['end']);
		$endtime= mktime(0, 0, 0, date("m",$endtime), date("d",$endtime)+1, date("Y",$endtime));
		$where.=" and po.createtime between {$startime} and {$endtime}";
	}
	
	
	//查询收银记录
	$sql="select count(*) from ".tablename("super_pay_order")."  po
			left join ".  tablename("super_staff")." s on s.id=po.staff_id
			left join ".tablename("super_shops")."	sh on sh.id=s.sid
			where po.status=1 and po.weid={$_W['weid']} and po.payplace=1 {$where}";
	$num=pdo_fetchcolumn($sql);
	$pager = pagination($num, $page, $pagenum);
	$page_start=($page-1)*$pagenum;
	$sql="select po.*,s.name as staff_name,sh.name as shop_name from ".tablename("super_pay_order")."  po
			left join ".  tablename("super_staff")." s on s.id=po.staff_id
			left join ".tablename("super_shops")."	sh on sh.id=s.sid
			where po.status=1 and po.weid={$_W['weid']} and po.payplace=1 {$where} order by po.id desc 
		";
	if($_GPC['download']==1){
		
	}else{
		$sql.=" limit {$page_start},{$pagenum} ";
	}
		
	$list= pdo_fetchall($sql);
	
	if($_GPC['download']==1){
		//导出
		header('Content-Type: application/vnd.ms-excel');
		header('Content-Disposition: attachment;filename="'.date("Y-m-d").'.csv"');
		header('Cache-Control: max-age=0');
		$fp = fopen('php://output', 'a');
		$head = array('编号', '收款时间', '应收总金额','实收总金额','优惠金额', '线上支付金额',"会员卡支付金额", '收款方式', '收款店铺','收款人');
		foreach($head as $key=>$value){
			$head[$key] = iconv('utf-8', 'gbk', $value);
		}
		fputcsv($fp, $head);
		foreach($list as $value){
		

			$row=Array(
				iconv('utf-8', 'gbk', $value['id']),
				iconv('utf-8', 'gbk', date("Y-m-d H:i:s",$value["createtime"])),
				iconv('utf-8', 'gbk', $value["money"]+$value['discountmoney']),
				iconv('utf-8', 'gbk', $value["money"]),
				iconv('utf-8', 'gbk', $value["discountmoney"]),
				iconv('utf-8', 'gbk', $value["paymoney"]),
				iconv('utf-8', 'gbk', $value["cardmoney"]),
				iconv('utf-8', 'gbk', getofflinetype($value["paystatus"])),
				iconv('utf-8', 'gbk', $value["shop_name"]),
				iconv('utf-8', 'gbk', $value["staff_name"]),
			);

			fputcsv($fp, $row);
		}
		die;
	}
	
	
	//统计收银总量
	$sql="select sum(money) as money,sum(cardmoney) as cardmoney,sum(paymoney) as paymoney,sum(discountmoney) as discountmoney from ".tablename("super_pay_order")."  po
			left join ".  tablename("super_staff")." s on s.id=po.staff_id
			left join ".tablename("super_shops")."	sh on sh.id=s.sid
			where po.status=1 and po.weid={$_W['weid']} and po.payplace=1 {$where}";	
	$allprice=pdo_fetch($sql);	
	
	include $this->template('web/offline/list');
}elseif($task=="getstafflist"){
	$shop_id=$_GPC['shop_id'];
	if($shop_id){
		$sql="select * from ".tablename("super_staff")." where sid={$shop_id}";
		$staff=pdo_fetchall($sql);
	}
	
	include $this->template('web/cashier/stafflist');
}


function getofflinetype($status){
	$array=Array(
		"","会员卡","微信","微信+会员卡","支付宝"
	);
	return $array[$status];
}