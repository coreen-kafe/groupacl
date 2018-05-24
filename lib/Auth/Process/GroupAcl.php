<?php

/**
 * groupACL Authentication Processing filter
 *
 *
 *	'roles' => array(
 *		'https://myentityID' => array(
 *			'allow' => array(
 *				'attributeFormat' => 'friendlyName',
 * 			  	'attributes' => array(
 *					'isMemberOf => array('kreonet:service:mycloud'),
 *				),
 * 				'referUrl' => 'https://myhome.org',
 *
 *			),
 *			'deny' => array(
 *				'attributeFormat' => 'oid',
 *				'attributes' => array(
 *					'isMemberOf => array('kreonet:service:mycloud'),
 *				),
 * 				'referUrl' => 'https://myhome.org',
 *			)
 *		)
 *	)
 *
 *
 *
 *
 *
 * @package simpleSAMLphp
 */
class sspmod_groupacl_Auth_Process_GroupAcl extends SimpleSAML_Auth_ProcessingFilter
{
	/** @var 접근제어 규칙 */
	private $roles;

	/** @var name2oid */
	private $attrs;

	/** @var state */
	private $state;

    public function __construct($config, $reserved) 
    {
		parent::__construct($config, $reserved);
		$this->roles = array();
		$this->attrs = array();
		$this->state = array();
    }

	/**
         * 그룹 체크
         *
         * @param array  $roles    접근제어 규칙.
         * @param array  $user     사용자속성.
         * @param string $key_type 사용자속성 비교방법.
         * @param string &$attr    체킹 속성 키.
         */
        private function group_attr_match($roles, $user, $key_type, &$attr = '')
        {
                foreach ($roles as $r => $v) {
                        $r = ($key_type === 'oid') ? $this->attrs[$r] : $r; // 사용자 키 형태
                        $role_val = is_array($v) ? $v : array($v); // 무조건 배열로 만들기
                        $user_val = isset($user[$r]) ? $user[$r] : ''; // 사용자 속성

                        foreach ($user_val as $uv) {
                                if (in_array($uv, $role_val)) {
                                        $attr = $uv;
                                        return true;
                                }
                        }
                }
                $attr = $uv;
                return false;
        }


	/**
	 * 자격여부를 확인하여 미자격시 에러페이지 이동
	 *
	 * @param boolean $isEligible 자격여부.
	 * @param string  &$error     에러코드.
	 */
	private function enter($isEligible = true, $error = '', $refer = '')
	{
		//SimpleSAML_Logger::notice('ROLE:: '.json_encode($this->roles));

            if ($isEligible === false) {
			//SimpleSAML_Logger::notice(json_encode($state));
            	$url = SimpleSAML_Module::getModuleURL('groupacl/error.php');
		$this->state['useracl:error'] = $error;

		$sp_url_tmp = parse_url($this->state['SPMetadata']['AssertionConsumerService'][0]['Location']);
		$sp_url = $sp_url_tmp['scheme'].'://'.$sp_url_tmp['host'];
		$this->state['useracl:spurl'] = $sp_url;

	        $this->state['useracl:referurl'] = $refer;

		$id  = SimpleSAML_Auth_State::saveState($this->state, 'userAcl');
		SimpleSAML_Utilities::redirectTrustedURL($url, array('StateId' => $id));
            }

		return;
	}

	/**
	 * Loads and merges in a file with a attribute map.
	 *
	 * @param string $fileName  Name of attribute map file. Expected to be in the attributenamemapdir.
	 */
	private function loadMapFile($fileName) {
		$config = SimpleSAML_Configuration::getInstance();
		$filePath = $config->getPathValue('attributenamemapdir', 'attributemap/') . $fileName . '.php';

		if(!file_exists($filePath)) {
			throw new Exception('Could not find attributemap file: ' . $filePath);
		}

		$attributemap = NULL;
		include($filePath);
		if(!is_array($attributemap)) {
			throw new Exception('Attribute map file "' . $filePath . '" didn\'t define an attribute map.');
		}

		$this->attrs = $attributemap;
	}

    /**
     * Apply filter.
     *
     * @param array &$request the current request
     */
    public function process(&$state)
    {
        assert('is_array($state)');
        assert('array_key_exists("UserID", $state)');
        assert('array_key_exists("Destination", $state)');
        assert('array_key_exists("entityid", $state["Destination"])');
        assert('array_key_exists("metadata-set", $state["Destination"])');		
        assert('array_key_exists("entityid", $state["Source"])');
        assert('array_key_exists("metadata-set", $state["Source"])');

	$config = SimpleSAML_Configuration::getOptionalConfig('config-groupacl.php');
	$this->roles = $config->getArray('roles');
	$this->loadMapFile('name2oid');
	
	$this->state = $state;
        $spEntityId = $state['Destination']['entityid'];
        $idpEntityId = $state['Source']['entityid'];
        $attributes = $state['Attributes'];

	// entityID가 목록에 없는 경우, 모든 서비스 제공자에 대해 access allow
	if (!empty($this->roles[$spEntityId])) {
	
	    $current_role = $this->roles[$spEntityId]; // 선택된 규칙			

	    // entityID에 대해 allow만 있는 경우, allow된 규칙을 갖는 사용자만 access allow		
	    if (empty($current_role['deny']) && (!empty($current_role['allow']))) {
		$check_role = $current_role['allow']['attributes'];
		$check_role_type = $current_role['allow']['attributeFormat'];
		$reference_url = $current_role['allow']['referUrl'];

		if ($this->group_attr_match($check_role, $attributes, $check_role_type, $attr) === false) {
		    $this->enter(false, 'notAllowGroup:'.$attr, $reference_url);
		}
	    }

	    // entityID에 대해 deny만 있는 경우, deny된 규칙을 갖는 사용자만 access deny
	    if (empty($current_role['allow']) && (!empty($current_role['deny']))) {

		$check_role = $current_role['deny']['attributes'];
		$check_role_type = $current_role['deny']['attributeFormat'];


		if ($this->group_attr_match($check_role, $attributes, $check_role_type, $attr) === true) {
		    $this->enter(false, 'denyUser:'.$attr);
		}
	    }

	    // entityID에 대해 allow와 deny가 함께 있는 경우, 오류
	    if ((!empty($current_role['deny'])) && (!empty($current_role['allow']))) {
		$this->enter(false, 'errorConfig');
	    }

	}

	$this->enter();
    }
}
