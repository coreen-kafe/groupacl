<?php
	$globalConfig = SimpleSAML_Configuration::getInstance();

	if (!array_key_exists('StateId', $_REQUEST)) {
		throw new SimpleSAML_Error_BadRequest(
			'Missing required StateId query parameter.'
		);
	}

	$id = $_REQUEST['StateId'];
	$state = SimpleSAML_Auth_State::loadState($id, 'userAcl');	

        $idp_entityId = $state['saml:sp:IdP'];
        $sp_entityId = $state['SPMetadata']['entityid'];
	$sp_url = $state['useracl:spurl'];
	
        $metadata = SimpleSAML_Metadata_MetaDataStorageHandler::getMetadataHandler();
        $idp_metadata = $metadata->getMetaData($idp_entityId, 'saml20-idp-remote');
        $idp_sso_service = $idp_metadata['SingleSignOnService'][0]['Location'];


	$ref_url = $state['useracl:referurl'];
	$tmp = explode(':', $state['useracl:error']);
	$errMsg = $tmp[0];
	$errAttr = isset($tmp[1]) ? $tmp[1] : '';
	$msg = '';

        $idp_initiated_sso = $idp_sso_service."?spentityid=".$sp_entityId."&RelayState=".$ref_url;

	switch ($errMsg) {
		case 'blockIp':
			$msg = '접근 차단된 IP('.$_SERVER['REMOTE_ADDR'].')입니다.';
		break;
		case 'notAllowGroup':
			$msg = '<p>해당 서비스제공자('. $sp_url . ')는 그룹정보 등 추가적인 이용권한을 필요로 합니다. </p>
				<a href="'.$ref_url . '" target="_blank"> KAFE GRAM(Group and Attribute Management system)</a>에 로그인해 권한을 요청하십시오. <br />
				GRAM 시스템 관리자가 이용권한을 부여하면 요청하신 서비스에 로그인하실 수 있습니다. <br /><br />
				'. $idp_initiated_sso;
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
