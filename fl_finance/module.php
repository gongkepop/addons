<?php
/**
 * 微社区-便民服务模块定义
 *
 * @author 疯狼工作组
 * @url http://bbs.we7.cc/
 */
defined('IN_IA') or exit('Access Denied');

class Fl_financeModule extends WeModule {
	public function welcomeDisplay(){
		message("",  $this->createWebUrl("index"));
	}
	public function fieldsFormDisplay($rid = 0) {
		//要嵌入规则编辑页的自定义内容，这里 $rid 为对应的规则编号，新增时为 0
	}

	public function fieldsFormValidate($rid = 0) {
		//规则编辑保存时，要进行的数据验证，返回空串表示验证无误，返回其他字符串将呈现为错误提示。这里 $rid 为对应的规则编号，新增时为 0
		return '';
	}

	public function fieldsFormSubmit($rid) {
		//规则验证无误保存入库时执行，这里应该进行自定义字段的保存。这里 $rid 为对应的规则编号
	}

	public function ruleDeleted($rid) {
		//删除规则时调用，这里 $rid 为对应的规则编号
	}

	public function settingsDisplay($settings) {
		global $_W, $_GPC;
		//点击模块设置时将调用此方法呈现模块设置页面，$settings 为模块设置参数, 结构为数组。这个参数系统针对不同公众账号独立保存。
		//在此呈现页面中自行处理post请求并保存设置参数（通过使用$this->saveSettings()来实现）
		if(checksubmit()) {
			//字段验证, 并获得正确的数据$dat
			$this->saveSetting($_GPC);
			message("保存成功");die;
		}
		//获取数据
		$sql="select * from ".tablename("fl_wsq_config")." where weid={$_W['weid']}";
		$data=  pdo_fetch($sql);
		//这里来展示设置项表单
		include $this->template('setting');
	}
	
	
	public function saveSetting($settings) {
		global $_W;
		//查询原来数据
		$sql="select * from ".tablename("fl_wsq_config")." where weid={$_W['weid']}";
		$old=  pdo_fetch($sql);
		$data=Array(
			"show_title"=>$settings['show_title']
		);
		if($old){
			$where=Array(
				"id"=>$old['id']
			);
			$rs=pdo_update("fl_wsq_config", $data, $where);
		}else{
			$data['weid']=$_W['weid'];
			$rs=pdo_insert("fl_wsq_config", $data);
		}
		
		return $rs;
		
	}

}