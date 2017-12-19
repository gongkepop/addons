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
	
	
	
	
	$pagenum=10;
	$page=$_GPC['page']?$_GPC['page']:1;
	$where="";
	//搜索
	if($_GPC['card']){
		$where.=" and po.card='{$_GPC['card']}'";
	}
	if($_GPC['name']){
		//查询会员卡
		$where.=" and c.name like '%{$_GPC['name']}%' ";
	}
	
	if($_GPC['tel']){
		$where.=" and c.tel='{$_GPC['tel']}'";
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
		$where.=" and po.createtime between {$startime} and {$endtime}";
	}
	
	if($_GPC['rechage_type']=="online"){
		$where.=" and po.place=7 ";
	}elseif($_GPC['rechage_type']=="offline"){
		$where.=" and po.place in (1,2,4,5) ";
	}elseif($_GPC['rechage_type']=="set"){
		$where.=" and po.place=9 ";
	}
	
	//查询收银记录
	$sql="select count(*) from ".tablename("super_card_credit_log")." po
		left join ".tablename("super_card")." c on c.id=po.card_id
			where po.weid={$_W['weid']} and types=2 and num>0  {$where} ";
	$num=pdo_fetchcolumn($sql);
	$pager = pagination($num, $page, $pagenum);
	$page_start=($page-1)*$pagenum;
	$sql="select po.*,c.name,c.tel ,staff.name as staff_name,shop.name as shop_name from ".tablename("super_card_credit_log")." po
		left join ".tablename("super_card")." c on c.id=po.card_id
		left join ".tablename("super_staff")."	staff on staff.id=po.staff_id
		left join ".tablename("super_shops")."	shop on shop.id=po.shop_id
		where po.weid={$_W['weid']} and types=2  and num>0 {$where} order by po.id desc limit {$page_start},{$pagenum} ";
	
		
	$list= pdo_fetchall($sql);
	
	//统计收银总量
	$sql="select sum(num) from ".tablename("super_card_credit_log")." po
		left join ".tablename("super_card")." c on c.id=po.card_id
		where po.weid={$_W['weid']} and types=2 and num>0 {$where} ";
	$allprice=pdo_fetchcolumn($sql);	
	
	include $this->template('web/recharge/list');
}elseif($task=="offline"){
	
	
	
	
	
}