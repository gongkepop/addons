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
if(!basefun()->getplugin("fl_cashier")){
	message("您尚未安装收银台，请联系管理员安装后访问。");
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
	
	
	
	
	$pagenum=10;
	$page=$_GPC['page']?$_GPC['page']:1;
	
	//搜索
	if($_GPC['shop_id']>0){
		$where.=" and cl.shop_id={$_GPC['shop_id']}";
	}
	if($_GPC['staff_id']>0){
		$where.=" and cl.staff_id={$_GPC['staff_id']}";
	}
	
	if($_GPC['pay_status']>0){
		$where.=" and cl.pay_status={$_GPC['pay_status']}";
	}
	
	if($_GPC['is_member']>0){
		if($_GPC['is_member']==2){
			
			$where.=" and cl.card_num>0 ";
		}else{
			$where.=" and (cl.card_num=0 or cl.card_num is null) ";
		}
		
	}
	
	if($_GPC['paytime']){
		$startime=strtotime($_GPC['paytime']['start']);
		
		$endtime=strtotime($_GPC['paytime']['end']);
		$endtime= mktime(0, 0, 0, date("m",$endtime), date("d",$endtime)+1, date("Y",$endtime));
		$where.=" and cl.createtime between {$startime} and {$endtime}";
	}
	
	
	//查询收银记录
	$sql="select count(*) from ".tablename("super_cashier_log")." cl where cl.weid={$_W['weid']} and cl.status=1 {$where}";
	$num=pdo_fetchcolumn($sql);
	$pager = pagination($num, $page, $pagenum);
	$page_start=($page-1)*$pagenum;
	$sql="select cl.*,sh.name as shop_name,s.name as staff_name from ".tablename("super_cashier_log")." cl
		left join ".tablename("super_shops")." sh on sh.id=cl.shop_id 
		left join ".tablename("super_staff")." s on s.id=cl.staff_id	
		where cl.weid={$_W['weid']} and cl.status=1 {$where}
		order by id desc limit {$page_start},{$pagenum}
		";
	
		
	$list= pdo_fetchall($sql);
	
	//统计收银总量
	$sql="select sum(cl.price) as price from ".tablename("super_cashier_log")." cl where cl.weid={$_W['weid']} and cl.status=1 {$where}";	
	$allprice=pdo_fetchcolumn($sql);	
	
	include $this->template('web/cashier/list');
}