<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
global $_W,$_GPC;
$nav="cashier";
$task=$_GPC['task']?$_GPC['task']:"index";

if($task=="index"){
	
	
	include $this->template('web/index/index');
	
}elseif($task=="getstafflist"){
	
	$shop_id=$_GPC['shop_id'];
	if($shop_id){
		$sql="select * from ".tablename("super_staff")." where sid={$shop_id}";
		$staff=pdo_fetchall($sql);
	}
	
	include $this->template('web/cashier/stafflist');
	
}

