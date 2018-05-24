Group-based Access Control
==========================

모듈 적용방법
-------------

<pre>
<code>
<?php

$config = array(
    'roles' => array(
        'https://myentityID' => array(
            'allow' => array(
                'attributeFormat' => 'friendlyName',
                'attributes' => array(
                    'isMemberOf' => array('kafe:service:myservice'),
                ),
                'referUrl' => 'https://myhome.org',
            ),
            'deny' => array(
                'attributeFormat' => 'oid',
                'attributes' => array(
                   'isMemberOf' => array('kafe:service:myservice'),
                ),
                'referUrl' => 'https://myhome.org',
            )
        )
    )
);
</code>
</pre>

1. simplesamlphp/modules에서 git clone https://github.com/coreen-kafe/groupacl.git
2. config-templates/config-groupacl.php를 simplesamlphp/modules/config에 복사
3. simplesamlphp/config/config.php 설정
<pre>
<code>
   'authproc.idp' => array(
                9 => 'groupacl:GroupAcl',
</code>
</pre>

변수 설명
---------
* attributeFormat: simplesamlphp 설정이 oid format을 따를 경우 'oid'로 설정; attributes 배열의 isMemberOf를 oid로 변경해 적용함
* isMemberOf: grouper나 comanage에 설정된 값을 따름
* referUrl: grouper나 comanage의 group 권한 요청 URL



