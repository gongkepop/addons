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

$pagenum=10;
$page=$_GPC['page']?$_GPC['page']:1;
$page_start=0;
if($task=="numbers"){
	
	
	
	//查询卡类型
	$sql="select * from ".tablename("super_card_type")." where weid={$_W['weid']}";
	$cardtype=pdo_fetchall($sql);
	
	
	
	$where="";
	
	
	
	//搜索
	if($_GPC['shop_id']>0){
		$where.=" and ccl.shop_id={$_GPC['shop_id']}";
	}
	if($_GPC['staff_id']>0){
		$where.=" and ccl.staff_id={$_GPC['staff_id']}";
	}
	
	
	if($_GPC['cardtype']>0){
		$where.=" and t.id={$_GPC['cardtype']}";
	}
	
	
	if($_GPC['paytime']){
		$startime=strtotime($_GPC['paytime']['start']);
		
		$endtime=strtotime($_GPC['paytime']['end']);
		$endtime= mktime(0, 0, 0, date("m",$endtime), date("d",$endtime)+1, date("Y",$endtime));
		$where.=" and ccl.createtime between {$startime} and {$endtime}";
	}
	
	
	//查询收银记录
	$sql="select count(*) from ".tablename("super_card_credit_log")."  ccl
			left join ".  tablename("super_staff")." s on s.id=ccl.staff_id
			left join ".tablename("super_shops")."	sh on sh.id=ccl.shop_id
			left join ".tablename("super_card")." sc on sc.id=ccl.card_id
			left join ".tablename("super_card_type")." t on t.id=sc.tid	 
			where ccl.types=3 and ccl.weid={$_W['weid']} and ccl.num<0 {$where}";

	$num=pdo_fetchcolumn($sql);
	$pager = pagination($num, $page, $pagenum);
	$page_start=($page-1)*$pagenum;
	$sql="select ccl.*,s.name as staff_name,sh.name as shop_name,t.name as tname from ".tablename("super_card_credit_log")."  ccl
			left join ".  tablename("super_staff")." s on s.id=ccl.staff_id
			left join ".tablename("super_shops")."	sh on sh.id=ccl.shop_id
			left join ".tablename("super_card")." sc on sc.id=ccl.card_id
			left join ".tablename("super_card_type")." t on t.id=sc.tid	 
			where ccl.types=3 and ccl.weid={$_W['weid']} and ccl.num<0 {$where} order by ccl.id desc 
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
		$head = array('编号', '时间', '卡号', '卡类型',"核销次数", '核销店铺', '核销员工');
		foreach($head as $key=>$value){
			$head[$key] = iconv('utf-8', 'gbk', $value);
		}
		fputcsv($fp, $head);
		foreach($list as $value){
		

			$row=Array(
				iconv('utf-8', 'gbk', $value['id']),
				iconv('utf-8', 'gbk', date("Y-m-d H:i:s",$value["createtime"])),
				iconv('utf-8', 'gbk', $value["card"]),
				iconv('utf-8', 'gbk', $value["tname"]),
				iconv('utf-8', 'gbk', abs($value["num"])),
				iconv('utf-8', 'gbk', $value["shop_name"]),
				iconv('utf-8', 'gbk', $value["staff_name"]),
			);

			fputcsv($fp, $row);
		}
		die;
	}
	
	$sql="select sum(ccl.num) as num from ".tablename("super_card_credit_log")."  ccl
			left join ".  tablename("super_staff")." s on s.id=ccl.staff_id
			left join ".tablename("super_shops")."	sh on sh.id=ccl.shop_id
			left join ".tablename("super_card")." sc on sc.id=ccl.card_id
			left join ".tablename("super_card_type")." t on t.id=sc.tid	 
			where ccl.types=3 and ccl.weid={$_W['weid']} and ccl.num<0 {$where} order by ccl.id desc 
		";
	$allprice['num']= pdo_fetchcolumn($sql);
	
	include $this->template('web/card/numbers');
}elseif($task=="credit2"){
	$where="";
	
	
	
	//搜索
	if($_GPC['shop_id']>0){
		$where.=" and ccl.shop_id={$_GPC['shop_id']}";
	}
	if($_GPC['staff_id']>0){
		$where.=" and ccl.staff_id={$_GPC['staff_id']}";
	}
	
	
	if($_GPC['cardtype']>0){
		$where.=" and t.id={$_GPC['cardtype']}";
	}
	
	
	if($_GPC['paytime']){
		$startime=strtotime($_GPC['paytime']['start']);
		
		$endtime=strtotime($_GPC['paytime']['end']);
		$endtime= mktime(0, 0, 0, date("m",$endtime), date("d",$endtime)+1, date("Y",$endtime));
		$where.=" and ccl.createtime between {$startime} and {$endtime}";
	}
	//查询收银记录
	$sql="select count(*) from ".tablename("super_card_credit_log")."  ccl
			left join ".  tablename("super_staff")." s on s.id=ccl.staff_id
			left join ".tablename("super_shops")."	sh on sh.id=ccl.shop_id
			left join ".tablename("super_card")." sc on sc.id=ccl.card_id
			left join ".tablename("super_card_type")." t on t.id=sc.tid	 
			where ccl.types=2 and ccl.weid={$_W['weid']} and ccl.num<0 and ccl.place=1 {$where}";

	$num=pdo_fetchcolumn($sql);
	$pager = pagination($num, $page, $pagenum);
	
	$sql="select ccl.*,s.name as staff_name,sh.name as shop_name from ".tablename("super_card_credit_log")."  ccl
			left join ".  tablename("super_staff")." s on s.id=ccl.staff_id
			left join ".tablename("super_shops")."	sh on sh.id=ccl.shop_id
			left join ".tablename("super_card")." sc on sc.id=ccl.card_id
			where ccl.types=2 and ccl.weid={$_W['weid']} and ccl.num<0 and ccl.place=1 {$where} order by ccl.id desc 
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
		$head = array('编号', '时间', '卡号', '卡类型',"核销次数", '核销店铺', '核销员工');
		foreach($head as $key=>$value){
			$head[$key] = iconv('utf-8', 'gbk', $value);
		}
		fputcsv($fp, $head);
		foreach($list as $value){
		

			$row=Array(
				iconv('utf-8', 'gbk', $value['id']),
				iconv('utf-8', 'gbk', date("Y-m-d H:i:s",$value["createtime"])),
				iconv('utf-8', 'gbk', $value["card"]),
				iconv('utf-8', 'gbk', $value["tname"]),
				iconv('utf-8', 'gbk', abs($value["num"])),
				iconv('utf-8', 'gbk', $value["shop_name"]),
				iconv('utf-8', 'gbk', $value["staff_name"]),
			);

			fputcsv($fp, $row);
		}
		die;
	}
	
	$sql="select sum(ccl.num) as num from ".tablename("super_card_credit_log")."  ccl
			left join ".  tablename("super_staff")." s on s.id=ccl.staff_id
			left join ".tablename("super_shops")."	sh on sh.id=ccl.shop_id
			left join ".tablename("super_card")." sc on sc.id=ccl.card_id
			left join ".tablename("super_card_type")." t on t.id=sc.tid	 
			where ccl.types=2 and ccl.weid={$_W['weid']} and ccl.num<0 and ccl.place=1  {$where} order by ccl.id desc 
		";
	$allprice['num']= pdo_fetchcolumn($sql);
	
	include $this->template('web/card/credit2');
}

