<?php
	$globalConfig = SimpleSAML_Configuration::getInstance();

	if (!array_key_exists('StateId', $_REQUEST)) {
		throw new SimpleSAML_Error_BadRequest(
			'Missing required StateId query parameter.'
		);
	}

	$id = $_REQUEST['StateId'];
	$state = SimpleSAML_Auth_State::loadState($id, 'userAcl');	
	$sp_url = $state['useracl:spurl'];
	$ref_url = $state['useracl:referurl'];
	$tmp = explode(':', $state['useracl:error']);
	$errMsg = $tmp[0];
	$errAttr = isset($tmp[1]) ? $tmp[1] : '';
	$msg = '';
	switch ($errMsg) {
		case 'blockIp':
			$msg = '접근 차단된 IP('.$_SERVER['REMOTE_ADDR'].')입니다.';
		break;
		case 'notAllowGroup':
			$msg = '<p>해당 서비스제공자('. $sp_url . ')는 그룹정보를 필요로 합니다. </p>
				<a href="'. $ref_url . '" target="_blank"> KAFE 그룹관리 시스템</a>을 통해 그룹 권한을 획득할 수 있습니다. <br />
				그룹 권한의 획득을 위해 시스템(그룹) 관리자의 승인이 필요합니다.';
		break;
		case 'notAllowUser':
			$msg = '허용되지 않는 사용자 속성('.$errAttr.')입니다.';
		break;
		case 'denyUser':
			$msg = '접근 차단된 사용자 속성('.$errAttr.')입니다.';
		break;
		case 'errorConfig':
			$msg = '해당 SP의 allow 정책과 deny 정책이 동시에 설정되어 있습니다.';
		break;
	}

	$t = new SimpleSAML_XHTML_Template($globalConfig, 'groupacl:error.php');
	$t->data['msg'] = $msg;
	$t->data['id'] = $id;
	$t->data['sp_url'] = $sp_url;
	$t->show();
?>
