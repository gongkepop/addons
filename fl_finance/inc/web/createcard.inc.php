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
//查看店铺
$sql="select * from ".tablename("super_shops")." where weid={$_W['weid']}";
$shop=pdo_fetchall($sql);
//查询店员
if($_GPC['shop_id']){
	$sql="select * from ".tablename("super_staff")." where weid={$_W['weid']} and sid={$_GPC['shop_id']}";
	$staff=pdo_fetchall($sql);


}
//查询卡类型
$sql="select * from ".tablename("super_card_type")." where weid={$_W['weid']}";
$cardtype=pdo_fetchall($sql);	

$pagenum=10;
$page=$_GPC['page']?$_GPC['page']:1;
$where="";

if($task=="list"){
	

	
	
	if($_GPC['shop_id']>0){
		$where.=" and ccl.shop_id={$_GPC['shop_id']} ";
	}
	if($_GPC['staff_id']>0){
		$where.=" and ccl.staff_id={$_GPC['staff_id']} ";
	}
	if($_GPC['cardtype']>0){
		$where.=" and ccl.tid={$_GPC['cardtype']} ";
	}
	
	
	if($_GPC['pay_status']==2){
		$where.=" and ccl.staff_id=0 ";
	}elseif($_GPC['pay_status']==1){
		
		$where.=" and ccl.staff_id>0 ";
	}
	
	
	
	if($_GPC['paytime']){
		$startime=strtotime($_GPC['paytime']['start']);
		
		$endtime=strtotime($_GPC['paytime']['end']);
		$endtime= mktime(0, 0, 0, date("m",$endtime), date("d",$endtime)+1, date("Y",$endtime));
		$where.=" and ccl.createtime between {$startime} and {$endtime}";
	}
	
	$sql="select sum(ccl.price) as price,count(*) as allnum,sum(re_count) as allcount,sum(re_credit1) as credit1,sum(re_credit2) as credit2 from ".tablename("super_card_create_log")." ccl
		where ccl.weid={$_W['weid']} {$where} ";
	$allprice= pdo_fetch($sql);
	
	$pager = pagination($allprice['allnum'], $page, $pagenum);
	$page_start=($page-1)*$pagenum;
	
	$sql="select ccl.*,ct.name as tname,staff.name as staff_name,shop.name as shop_name from ".tablename("super_card_create_log")." ccl
		left join ".tablename("super_card_type")." ct on ct.id=ccl.tid
		left join ".tablename("super_staff")."	staff on staff.id=ccl.staff_id
		left join ".tablename("super_shops")."	shop on shop.id=ccl.shop_id
		where ccl.weid={$_W['weid']} {$where} order by id desc ";
	if($_GPC['download']==1){
		
	}else{
		$sql.=" limit {$page_start},{$pagenum} ";
	}	
	$list=pdo_fetchall($sql);
	
	
	
	if($_GPC['download']==1){
		//导出
		header('Content-Type: application/vnd.ms-excel');
		header('Content-Disposition: attachment;filename="'.date("Y-m-d").'.csv"');
		header('Cache-Control: max-age=0');
		$fp = fopen('php://output', 'a');
		$head = array('编号', '时间', '卡号', '卡类型',"价格", '次数', '积分','余额','套餐名称','开卡店铺','开卡人');
		foreach($head as $key=>$value){
			$head[$key] = iconv('utf-8', 'gbk', $value);
		}
		fputcsv($fp, $head);
		foreach($list as $value){
		

			$row=Array(
				iconv('utf-8', 'gbk', $value['id']),
				iconv('utf-8', 'gbk', date("Y-m-d H:i:s",$value["createtime"])),
				iconv('utf-8', 'gbk', $value["card_num"]),
				iconv('utf-8', 'gbk', $value["tname"]),
				iconv('utf-8', 'gbk', $value["price"]),
				iconv('utf-8', 'gbk', $value["re_count"]),
				iconv('utf-8', 'gbk', $value["re_credit1"]),
				iconv('utf-8', 'gbk', $value["re_credit2"]),
				iconv('utf-8', 'gbk', $value["package"]),
				iconv('utf-8', 'gbk', $value["shop_name"]?$value["shop_name"]:"自助购卡"),
				iconv('utf-8', 'gbk', $value["staff_name"]?$value["staff_name"]:"自助购卡"),
				
			);

			fputcsv($fp, $row);
		}
		die;
	}
	
	
	
	include $this->template('web/createcard/list');
}elseif($task=="changelists"){
	if($_GPC['shop_id']>0){
		$where.=" and ctl.shop_id={$_GPC['shop_id']} ";
	}
	if($_GPC['staff_id']>0){
		$where.=" and ctl.staff_id={$_GPC['staff_id']} ";
	}
	if($_GPC['cardtype']>0){
		$where.=" and ctl.newtid={$_GPC['cardtype']} ";
	}	
	if($_GPC['paytime']){
		$startime=strtotime($_GPC['paytime']['start']);
		
		$endtime=strtotime($_GPC['paytime']['end']);
		$endtime= mktime(0, 0, 0, date("m",$endtime), date("d",$endtime)+1, date("Y",$endtime));
		$where.=" and ctl.createtime between {$startime} and {$endtime}";
	}
	
	
	$sql="select count(*) as allnum,sum(nct.price) as price from ".tablename("super_card_type_log")." ctl
		left join ". tablename("super_card_type")."	nct on nct.id=ctl.newtid
		where ctl.weid={$_W['weid']} {$where}";
	$allprice= pdo_fetch($sql);
	
	$pager = pagination($allprice['allnum'], $page, $pagenum);
	$page_start=($page-1)*$pagenum;
	
	
	$sql="select ctl.*,c.card as card_num,ct.name as old_name,nct.name as new_name,nct.price as new_price,staff.name as staff_name,shop.name as shop_name from ".tablename("super_card_type_log")." ctl
		left join ". tablename("super_card")." c on c.id=ctl.cardid
		left join ". tablename("super_card_type")." ct on ct.id=ctl.oldtid
		left join ". tablename("super_card_type")."	nct on nct.id=ctl.newtid
		left join ".tablename("super_staff")."	staff on staff.id=ctl.staff_id
		left join ".tablename("super_shops")."	shop on shop.id=ctl.shop_id
		where ctl.weid={$_W['weid']} {$where} order by ctl.id desc";
		
	if($_GPC['download']==1){
		
	}else{
		$sql.=" limit {$page_start},{$pagenum} ";
	}		
		
	$list=pdo_fetchall($sql);
	
	
	
	if($_GPC['download']==1){
		//导出
		header('Content-Type: application/vnd.ms-excel');
		header('Content-Disposition: attachment;filename="'.date("Y-m-d").'.csv"');
		header('Cache-Control: max-age=0');
		$fp = fopen('php://output', 'a');
		$head = array('编号', '时间', '卡号', '原卡类型',"新卡类型", '新卡价格','修改店铺','修改员工');
		foreach($head as $key=>$value){
			$head[$key] = iconv('utf-8', 'gbk', $value);
		}
		fputcsv($fp, $head);
		foreach($list as $value){
		

			$row=Array(
				iconv('utf-8', 'gbk', $value['id']),
				iconv('utf-8', 'gbk', date("Y-m-d H:i:s",$value["createtime"])),
				iconv('utf-8', 'gbk', $value["card_num"]),
				iconv('utf-8', 'gbk', $value["old_name"]),
				iconv('utf-8', 'gbk', $value["new_name"]),
				iconv('utf-8', 'gbk', $value["new_price"]),
				iconv('utf-8', 'gbk', $value["shop_name"]?$value["shop_name"]:"自助购卡"),
				iconv('utf-8', 'gbk', $value["staff_name"]?$value["staff_name"]:"自助购卡"),
			);

			fputcsv($fp, $row);
		}
		die;
	}
	
	include $this->template('web/createcard/changelist');
}


