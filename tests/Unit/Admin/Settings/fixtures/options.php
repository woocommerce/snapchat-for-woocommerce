<?php
$expected_organizations = array(
	array(
		'id'          => '40d6719b-da09-410b-9185-0cc9c0dfed1d',
		'name'        => 'Hooli Inc',
		'ad_accounts' =>
			array(
				array(
					'id'   => '8b8e40af-fc64-455d-925b-ca80f7af6914',
					'name' => 'Hooli Originals',
				),
				array(
					'id'   => '294da428-5f98-4c78-9946-14cf65cff14b',
					'name' => 'Hooli Fakes',
				),
			),
	),
	array(
		'id'          => 'b35f4c9f-a123-4cde-8934-d32b4b91a731',
		'name'        => 'Pied Piper LLC',
		'ad_accounts' =>
			array(
				array(
					'id'   => '9cc671b0-13ab-432e-9914-ffac19a37b2a',
					'name' => 'Pied Piper Ad Account',
				),
			),
	),
	array(
		'id'          => '97e3473e-c313-4f52-b6f9-3fd420b313be',
		'name'        => 'Initech Corp',
		'ad_accounts' =>
			array(
				array(
					'id'   => 'a1b2c3d4-e5f6-7890-abcd-112233445566',
					'name' => 'Initech Main Account',
				),
			),
	),
);

$orgs_sanitized = array_map(
	fn( $org ) => array(
		'id'   => $org['id'],
		'name' => $org['name'],
	),
	$expected_organizations
);

$pixels_sanitized = array(
	array(
		'id'               => '6abc82ca-4a3a-4391-98ba-0317a8471234',
		'name'             => 'Pixel for Woo',
		'pixel_javascript' => '<!-- Snap Pixel Code -->
<script type=\'text/javascript\'>
(function(e,t,n){if(e.snaptr)return;var a=e.snaptr=function()
{a.handleRequest?a.handleRequest.apply(a,arguments):a.queue.push(arguments)};
a.queue=[];var s=\'script\';r=t.createElement(s);r.async=!0;
r.src=n;var u=t.getElementsByTagName(s)[0];
u.parentNode.insertBefore(r,u);})(window,document,
\'https://sc-static.net/scevent.min.js\');

snaptr(\'init\', \'6abc82ca-4a3a-4391-98ba-0317a8471234\', {
\'user_email\': \'__INSERT_USER_EMAIL__\'
});

snaptr(\'track\', \'PAGE_VIEW\');

</script>
<!-- End Snap Pixel Code -->',
	),
	array(
		'id'               => 'e7d0967a-788c-4f32-a4b8-c94711e663de',
		'name'             => 'Pixel for App',
		'pixel_javascript' => '<!-- Snap Pixel Code -->
<script type=\'text/javascript\'>
(function(e,t,n){if(e.snaptr)return;var a=e.snaptr=function()
{a.handleRequest?a.handleRequest.apply(a,arguments):a.queue.push(arguments)};
a.queue=[];var s=\'script\';r=t.createElement(s);r.async=!0;
r.src=n;var u=t.getElementsByTagName(s)[0];
u.parentNode.insertBefore(r,u);})(window,document,
\'https://sc-static.net/scevent.min.js\');

snaptr(\'init\', \'e7d0967a-788c-4f32-a4b8-c94711e663de\', {
\'user_email\': \'__INSERT_USER_EMAIL__\'
});

snaptr(\'track\', \'PAGE_VIEW\');

</script>
<!-- End Snap Pixel Code -->',
	),
	array(
		'id'               => '3c9199dc-9734-4b77-841e-dba642d80c13',
		'name'             => 'Pixel from the Future',
		'pixel_javascript' => '<!-- Snap Pixel Code -->
<script type=\'text/javascript\'>
(function(e,t,n){if(e.snaptr)return;var a=e.snaptr=function()
{a.handleRequest?a.handleRequest.apply(a,arguments):a.queue.push(arguments)};
a.queue=[];var s=\'script\';r=t.createElement(s);r.async=!0;
r.src=n;var u=t.getElementsByTagName(s)[0];
u.parentNode.insertBefore(r,u);})(window,document,
\'https://sc-static.net/scevent.min.js\');

snaptr(\'init\', \'3c9199dc-9734-4b77-841e-dba642d80c13\', {
\'user_email\': \'__INSERT_USER_EMAIL__\'
});

snaptr(\'track\', \'PAGE_VIEW\');

</script>
<!-- End Snap Pixel Code -->',
	),
);

return array(
	'orgs'             => $expected_organizations,
	'orgs_sanitized'   => $orgs_sanitized,
	'pixels_sanitized' => $pixels_sanitized,
);
