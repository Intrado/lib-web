<?php
// This is a simple test page for the formswitcher.

require_once('obj/FormSwitcher.obj.php');

$messagegroupbasicsformdata = array();
$messagegroupbasicsformdata['emailsubject'] = array(
	'control' => array('TextField'),
	'validators' => array('ValRequired'),
	'renderoptions' => array()
);
$messagegroupbasicsform = new Form('messagegroupbasicsform', $messagegroupbasicsformdata);

$emailformdata = array();
$emailformdata['emailsubject'] = array(
	'control' => array('TextField'),
	'validators' => array('ValRequired'),
	'renderoptions' => array()
);
$emailform = new Form('emailform', $emailformdata);

$formstructure = array(
	'messagegroupbasics' => $messagegroupbasicsform,
	'layers' => array(
		'_layout' => 'horizontaltabs',
		'phone' => array(
			'_title' => 'Phone',
			'languages' => array(
				'_layout' => 'verticaltabs',
				'en' => array(
					'_title' => 'English',
					'_layout' => 'verticalsplit',
					'tools' => array(
						'_layout' => 'accordion',
						'audio' => array(),
						'datafields' => array()
					)
				),
				'es' => array(
					'_title'=> 'Spanish',
					'_layout' => 'verticalsplit',
					'tools' => array(
						'_layout' => 'accordion',
						'audio' => array(),
						'datafields' => array(),
						'translation' => array()
					)
				)
			)
		),
		'email' => array(
			'_title' => 'Email',
			'emailheaders' => $emailform,
			'subtypes' => array(
				'_layout' => 'horizontaltabs',
				'html' => array(
					'_title' => 'Html',
					'languages' => array(
						'_layout' => 'verticaltabs',
						'en' => array(
							'_title' => 'English',
							'_layout' => 'verticalsplit',
							'tools' => array(
								'layout' => 'accordion',
								'attachments' => array(),
								'datafields' => array(),
							)
						),
						'es' => array(
							'_layout' => 'verticalsplit',
							'_title'=> 'Spanish',
							'tools' => array(
								'layout' => 'accordion',
								'attachments' => array(),
								'datafields' => array(),
								'translation' => array()
							)
						)
					)
				),
				'plain' => array(
					'_title' => 'Plain',
					'languages' => array(
						'_layout' => 'verticaltabs',
						'en' => array(
							'_title' => 'English',
							'_layout' => 'verticalsplit',
							'tools' => array(
								'layout' => 'accordion',
								'attachments' => array(),
								'datafields' => array(),
							)
						),
						'es' => array(
							'_title'=> 'Spanish',
							'_layout' => 'verticalsplit',
							'tools' => array(
								'layout' => 'accordion',
								'attachments' => array(),
								'datafields' => array(),
								'translation' => array()
							)
						)
					)
				)
			)
		),
		'sms' => array(
		),
		'summary' => array(
		)
	)
);

$formswitcher = new FormSwitcher('messagegroup', $formstructure);

$formswitcher->handleRequest();

?>

<html>
<head>
	<link href="css.php" type="text/css" rel="stylesheet" media="screen, print">
	<script src="script/prototype.js" type="text/javascript"></script>
	<script src="script/scriptaculous.js" type="text/javascript"></script>
	<script src="script/accordion.js" type="text/javascript"></script>
</head>

<body>

<?php

	echo $formswitcher->render();

?>

</body>
</html>