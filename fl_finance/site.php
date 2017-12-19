<?php
/**
 * ![CDATA[微社区-商业版]]
 *
 * @author 疯狼工作组
 * 
 */
defined('IN_IA') or exit('Access Denied');
define("SUPER_CARD_TYPE", "card");
require IA_ROOT.'/addons/fl_update/require.php';
require IA_ROOT.'/addons/fl_update/model.php';
require IA_ROOT.'/addons/fl_update/language.php';

class fl_financeModuleSite extends WeModuleSite {
	var $config;	//系统配置
    var $urls;
	var $config_level;
	var $config_text;
    var $debug=0;
	public function __construct() {
		global $_W,$_GPC;
		//获得配置信息
		$sql="select * from ".tablename("super_card_config")." where weid={$_W['weid']}";
        $this->config=pdo_fetch($sql);
		if(!$this->config){
			$data=Array(
				"weid"=>$_W['weid'],
				"name"=>" ",
				"is_reg"=>1,
				"level_type"=>0,
			);
			pdo_insert("super_card_config",$data);
			$sql="select * from ".tablename("super_card_config")." where weid={$_W['weid']}";
			$this->config=pdo_fetch($sql);
		}
		//获得基础配置信息
		$this->config_level=json_decode($this->config['level_config'],true);
		$this->config_text=json_decode($this->config['text_config'],true);
		//查询系统文字
		$default_language= default_language();
		foreach($default_language as $key=>$value){
			if(!$this->config_text[$key]){
				$this->config_text[$key]=$value;
			}
		}
		
		
		
//		echo "<pre>";
//		print_r($_W);die;
		//查询cover链接
		$sql="select * from ".tablename("modules_bindings")." where module='fl_card' and entry='cover'";
		$this->cover=  pdo_fetchall($sql);
		
	}
	
	protected function pay($params = array(), $mine = array()) {
		global $_W;
		if(!$this->inMobile) {
			message('支付功能只能在手机上使用');
		}
		$params['module'] = $this->module['name'];
		$pars = array();
		$pars[':uniacid'] = $_W['uniacid'];
		$pars[':module'] = $params['module'];
		$pars[':tid'] = $params['tid'];
		if($params['fee'] <= 0) {
			$pars['from'] = 'return';
			$pars['result'] = 'success';
			$pars['type'] = '';
			$pars['tid'] = $params['tid'];
			$site = WeUtility::createModuleSite($pars[':module']);
			$method = 'payResult';
			if (method_exists($site, $method)) {
				exit($site->$method($pars));
			}
		}
		
		$sql = 'SELECT * FROM ' . tablename('core_paylog') . ' WHERE `uniacid`=:uniacid AND `module`=:module AND `tid`=:tid';
		$log = pdo_fetch($sql, $pars);
		if (empty($log)) {
			$log = array(
				'uniacid' => $_W['uniacid'],
				'acid' => $_W['acid'],
				'openid' => $_W['member']['uid'],
				'module' => $this->module['name'],
				'tid' => $params['tid'],
				'fee' => $params['fee'],
				'card_fee' => $params['fee'],
				'status' => '0',
				'is_usecard' => '0',
			);
			pdo_insert('core_paylog', $log);
		}
		if($log['status'] == '1') {
			message('这个订单已经支付成功, 不需要重复支付.');
		}
		$setting = uni_setting($_W['uniacid'], array('payment', 'creditbehaviors'));
		if(!is_array($setting['payment'])) {
			message('没有有效的支付方式, 请联系网站管理员.');
		}
		$pay = $setting['payment'];
		if (empty($_W['member']['uid'])) {
			$pay['credit']['switch'] = false;
		}
		if (!empty($pay['credit']['switch'])) {
			$credtis = mc_credit_fetch($_W['member']['uid']);
		}
		$you = 0;
		if($pay['card']['switch'] == 2 && !empty($_W['openid'])) {
						if($_W['card_permission'] == 1 && !empty($params['module'])) {
				$cards = pdo_fetchall('SELECT a.id,a.card_id,a.cid,b.type,b.title,b.extra,b.is_display,b.status,b.date_info FROM ' . tablename('coupon_modules') . ' AS a LEFT JOIN ' . tablename('coupon') . ' AS b ON a.cid = b.id WHERE a.acid = :acid AND a.module = :modu AND b.is_display = 1 AND b.status = 3 ORDER BY a.id DESC', array(':acid' => $_W['acid'], ':modu' => $params['module']));
				$flag = 0;
				if(!empty($cards)) {
					foreach($cards as $temp) {
						$temp['date_info'] = iunserializer($temp['date_info']);
						if($temp['date_info']['time_type'] == 1) {
							$starttime = strtotime($temp['date_info']['time_limit_start']);
							$endtime = strtotime($temp['date_info']['time_limit_end']);
							if(TIMESTAMP < $starttime || TIMESTAMP > $endtime) {
								continue;
							} else {
								$param = array(':acid' => $_W['acid'], ':openid' => $_W['openid'], ':card_id' => $temp['card_id']);
								$num = pdo_fetchcolumn('SELECT COUNT(*) FROM ' . tablename('coupon_record') . ' WHERE acid = :acid AND openid = :openid AND card_id = :card_id AND status = 1', $param);
								if($num <= 0) {
									continue;
								} else {
									$flag = 1;
									$card = $temp;
									break;
								}
							}
						} else {
							$deadline = intval($temp['date_info']['deadline']);
							$limit = intval($temp['date_info']['limit']);
							$param = array(':acid' => $_W['acid'], ':openid' => $_W['openid'], ':card_id' => $temp['card_id']);
							$record = pdo_fetchall('SELECT addtime,id,code FROM ' . tablename('coupon_record') . ' WHERE acid = :acid AND openid = :openid AND card_id = :card_id AND status = 1', $param);
							if(!empty($record)) {
								foreach($record as $li) {
									$time = strtotime(date('Y-m-d', $li['addtime']));
									$starttime = $time + $deadline * 86400;
									$endtime = $time + $deadline * 86400 + $limit * 86400;
									if(TIMESTAMP < $starttime || TIMESTAMP > $endtime) {
										continue;
									} else {
										$flag = 1;
										$card = $temp;
										break;
									}
								}
							}
							if($flag) {
								break;
							}
						}
					}
				}
				if($flag) {
					if($card['type'] == 'discount') {
						$you = 1;
						$card['fee'] = sprintf("%.2f", ($params['fee'] * ($card['extra'] / 100)));
					} elseif($card['type'] == 'cash') {
						$cash = iunserializer($card['extra']);
						if($params['fee'] >= $cash['least_cost']) {
														$you = 1;
							$card['fee'] = sprintf("%.2f", ($params['fee'] -  $cash['reduce_cost']));
						}
					}
					load()->classs('coupon');
					$acc = new coupon($_W['acid']);
					$card_id = $card['card_id'];
					$time = TIMESTAMP;
					$randstr = random(8);
					$sign = array($card_id, $time, $randstr, $acc->account['key']);
					$signature = $acc->SignatureCard($sign);
					if(is_error($signature)) {
						$you = 0;
					}
				}
			}
		}

		if($pay['card']['switch'] == 3 && $_W['member']['uid']) {
						$cards = array();
			if(!empty($params['module'])) {
				$cards = pdo_fetchall('SELECT a.id,a.couponid,b.type,b.title,b.discount,b.condition,b.starttime,b.endtime FROM ' . tablename('activity_coupon_modules') . ' AS a LEFT JOIN ' . tablename('activity_coupon') . ' AS b ON a.couponid = b.couponid WHERE a.uniacid = :uniacid AND a.module = :modu AND b.condition <= :condition AND b.starttime <= :time AND b.endtime >= :time  ORDER BY a.id DESC', array(':uniacid' => $_W['uniacid'], ':modu' => $params['module'], ':time' => TIMESTAMP, ':condition' => $params['fee']), 'couponid');
				if(!empty($cards)) {
					foreach($cards as $key => &$card) {
						$has = pdo_fetchcolumn('SELECT COUNT(*) FROM ' . tablename('activity_coupon_record') . ' WHERE uid = :uid AND uniacid = :aid AND couponid = :cid AND status = 1' . $condition, array(':uid' => $_W['member']['uid'], ':aid' => $_W['uniacid'], ':cid' => $card['couponid']));
						if($has > 0){
							if($card['type'] == '1') {
								$card['fee'] = sprintf("%.2f", ($params['fee'] * $card['discount']));
								$card['discount_cn'] = sprintf("%.2f", $params['fee'] * (1 - $card['discount']));
							} elseif($card['type'] == '2') {
								$card['fee'] = sprintf("%.2f", ($params['fee'] -  $card['discount']));
								$card['discount_cn'] = $card['discount'];
							}
						} else {
							unset($cards[$key]);
						}
					}
				}
			}
			if(!empty($cards)) {
				$cards_str = json_encode($cards);
			}
			
		}
		$pay['delivery']['switch']=false;
		include $this->template('common/paycenter');
	}
	






	public function payResult($params) {
		global $_W;
		if($params['result']=="success"&&  substr($params['tid'], 0, 1)=="C"){
			//支付成功
			$data=Array(
				"status"=>1,
				"paytype"=>1,
				"price"=>$params['fee'],
				"pay_time"=>time(),
			);
			$where=Array(
				"ordersn"=>substr($params['tid'], 1)
			);
			$pay=  pdo_update("super_card_order", $data, $where);
	
			message("支付成功,正在生成会员卡……",  $this->createMobileUrl("card",array("task"=>'create')),"success");
		}elseif ($params['result']=="success"&&  substr($params['tid'], 0, 1)=="R") {
			//充值
			$sql="select * from ".tablename("core_paylog")." where tid='{$params['tid']}'";
			$order= pdo_fetch($sql);
			$ordersn=substr($params['tid'], 1);
			//判断订单是否支付成功
			$sql="select * from ".tablename("super_pay_order")." where ordersn='{$ordersn}'";
			$orders= pdo_fetch($sql);
			if($orders['status']==1){
				message("订单已经支付，请不要重复支付",$this->createMobileUrl("index"),"success");
				die;
			}
			
			
			//查询用户
			$sql="select * from ".tablename("mc_mapping_fans")." where openid='{$order['openid']}'";
			$member= pdo_fetch($sql);
			
			
			//修改数据
			mc_credit_update($member['uid'], "credit2", $order['card_fee'], array($member['uid'],'会员卡微信充值'));
			
			
			//存储记录
			$cardModel=D("card");
			$card=$cardModel->getdefaultcard($member['openid'],$_W['weid']);
			$setlog=$cardModel->setcreditlog($card['id'],"credit2",$order['card_fee'],array(
						"staff_id"=> 0,
						"shop_id"=>0,
						"weid"=>$_W['weid'],
						"place"=>7
					),"微信充值");
			
			
			
			
			
				$data=Array(
					"createtime"=>time(),
					"openid"=>$order['openid'],
					"money"=>$order['fee'],
					"status"=>1,
					"paytime"=>time(),
					"staff_id"=>0,
					"weid"=>$_W['weid'],
					"ordersn"=>$ordersn,
					"paymoney"=>$order['card_fee'],
					"paystatus"=>4,
					"paysn"=>$order['tid'],
					"content"=>"微信充值",
					"payplace"=>7
				);

				pdo_insert("super_pay_order",$data);
			
			
			
			
			
			//发送信息
			//发送消息
			//发送数据

			$acc = WeAccount::create();
			//支付成功发送模板消息
			//发送给用户
			$post_data=array(
						'first' => array(
							'value' => "您好，恭喜您充值成功",
							"color" => "#4a5077"
						),
						'keyword1' => array(
							'value' => $ordersn,
							"color" => "#4a5077"
						),
						'keyword2' => array(
							'value' => $order['fee'],
							"color" => "#4a5077"
						),
						'keyword3' => array(
								'value' => "自助充值",
								"color" => "#4a5077"
						),
						'keyword4' => array(
								'value' => date("Y年m月d日 H:i:s"),
								"color" => "#4a5077"
						),
						'remark' => array(
							'value' => "微信支付：{$order['card_fee']}。",
							"color" => "#09BB07"
						)
			);
		  $tempcode=$this->config_level['offline_senduser_temp'];
		  $url="";
		  $sendResult=$acc->sendTplNotice($member['openid'],$tempcode,$post_data,$url);
			
		  	//充值满赠
			if($this->config_level["sale_credit2"]==1){
				$sale_key=0;
				foreach($this->config_level["sale_credit2_money"] as $key=>$value){
					if($order['card_fee']>=$key&&$sale_key<$key){
						$sale_key=$key;
					}
				}
				if($sale_key){
					$sale_value=$this->config_level["sale_credit2_money"][$sale_key];
				}
				if($sale_value){
					mc_credit_update($member['uid'], "credit2", $sale_value, array($member['uid'],'会员卡微信充值满赠'));
				}
				
				$setlog=$cardModel->setcreditlog($card['id'],"credit2",$sale_value,array(
						"staff_id"=> 0,
						"shop_id"=>0,
						"weid"=>$_W['weid'],
						"place"=>7
					),"充值满赠");
				
				
				//发送给用户
					$post_data=array(
							'first' => array(
								'value' => "您好，恭喜您符合充值满赠条件",
								"color" => "#4a5077"
							),
							'keyword1' => array(
								'value' => $ordersn,
								"color" => "#4a5077"
							),
							'keyword2' => array(
								'value' => $sale_value,
								"color" => "#4a5077"
							),
							'keyword3' => array(
									'value' => "充值满赠",
									"color" => "#4a5077"
							),
							'keyword4' => array(
									'value' => date("Y年m月d日 H:i:s"),
									"color" => "#4a5077"
							),
							'remark' => array(
								'value' => "赠送：{$sale_value}。",
								"color" => "#09BB07"
							)
				);
				$tempcode=$this->config_level['offline_senduser_temp'];
				$url="";
				$sendResult=$acc->sendTplNotice($member['openid'],$tempcode,$post_data,$url);
				
			}
		  
		  
		  
		  
		  
			message("充值成功",$this->createMobileUrl("index"),"success");
			
			
		}elseif ($params['result']=="success"&&  substr($params['tid'], 0, 1)=="P") {
			$ordersn=substr($params['tid'], 1);
			//判断订单是否支付成功
			$sql="select * from ".tablename("super_pay_order")." where ordersn='{$ordersn}'";
			$order= pdo_fetch($sql);
			if($order['status']==1){
				message("订单已经支付，请不要重复支付",$this->createMobileUrl("index"),"success");
				die;
			}
			
			//判断支付方式
			if($params['type']=="credit"){
				$cardmoney=$params['card_fee'];
				$paymoney=0;
				$paystatus=1;
			}else{
				$cardmoney=$params['fee']-$params['card_fee'];
				$paymoney=$params['card_fee'];
				$paystatus=$cardmoney>0?3:2;
				//扣减余额
				mc_credit_update($_W['member']['uid'], "credit2", -($params['fee']-$params['card_fee']), array($_W['member']['uid'],'线下支付消费余额'));
			
			}
			
			
			
			
			
			//修改支付记录
			$data=Array(
				"paytime"=>time(),
				"status"=>1,
				"cardmoney"=>$cardmoney,
				"paymoney"=>$paymoney,
				"paystatus"=>$paystatus>0?3:2,
				"paysn"=>$params['tid'],
			);
			$where=Array(
				"ordersn"=>substr($params['tid'], 1)
			);
			pdo_update("super_pay_order",$data,$where);
			
			
			//查询商户信息
			$sql="select s.name,sh.name as shop_name,s.openid,s.id,s.sid from ".tablename("super_pay_order")." po
				left join ". tablename("super_staff")." s on s.id=po.staff_id
				left join ". tablename("super_shops")." sh on sh.id=s.sid	
				where po.ordersn='{$ordersn}'";
			$staff= pdo_fetch($sql);
			
			//查询用户信息
			$sql="select * from ".tablename("super_card")." where openid='{$_W['openid']}'";
			$member=pdo_fetch($sql);
			
			//储存记录
			$cardModel=D("card");
			$setlog=$cardModel->setcreditlog($member['id'],"credit2",$params['fee'],array(
				"staff_id"=> $staff['id'],
				"shop_id"=> $staff['sid'],
				"weid"=>$_W['weid'],
				"place"=>6
			),"扫码支付。");
			
			//触发活动
			$activeModel=D("active");
			$activeModel->startActive($member['id'],$params['fee'],1,Array(
							"staff"=>$staff,
							"place"=>6
						));
			
			$acc = WeAccount::create();
			//支付成功发送模板消息
			//发送给用户
			$post_data=array(
                        'first' => array(
                            'value' => "您好，恭喜您支付成功",
                            "color" => "#4a5077"
                        ),
                        'keyword1' => array(
                            'value' => substr($data['paysn'],1),
                            "color" => "#4a5077"
                        ),
                        'keyword2' => array(
                            'value' => $params['fee'],
                            "color" => "#4a5077"
                        ),
						'keyword3' => array(
								'value' => $staff['shop_name'],
								"color" => "#4a5077"
						),
						'keyword4' => array(
								'value' => date("Y年m月d日 H:i:s"),
								"color" => "#4a5077"
						),
                        'remark' => array(
                            'value' => "其中会员卡消费：{$cardmoney}元，微信支付：{$paymoney}元。",
                            "color" => "#09BB07"
                        )
          );
		$tempcode=$this->config_level['offline_senduser_temp'];
		$url="";
		$sendResult=$acc->sendTplNotice($_W['openid'],$tempcode,$post_data,$url);
		
		//发送给店员
		$post_data=array(
                        'first' => array(
                            'value' => "会员{$member['card']}于".date("H:i:s")."成功支付订单",
                            "color" => "#4a5077"
                        ),
                        'keyword1' => array(
                            'value' =>$params['fee'] ,
                            "color" => "#4a5077"
                        ),
                        'keyword2' => array(
                            'value' => substr($data['paysn'],1),
                            "color" => "#4a5077"
                        ),
					
                        'remark' => array(
                            'value' => "会员卡号{$member['card']}：其中会员卡消费：{$cardmoney}元，微信支付：{$paymoney}元。",
                            "color" => "#09BB07"
                        )
          );
		$tempcode=$this->config_level['offline_sendstaff_temp'];
		$url="";
		$sendResult=$acc->sendTplNotice($staff['openid'],$tempcode,$post_data,$url);
		
		
		message("支付成功",  $this->createMobileUrl("index"),"success");
		}
		
	}
	
	

	
}