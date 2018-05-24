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
