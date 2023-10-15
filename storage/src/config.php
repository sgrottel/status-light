<?php if (1 === preg_match('%/?config\.php$%i', $_SERVER['PHP_SELF'])) { http_response_code(404); die(); }
return (object)array(
	'db' => (object)array(
		'host' => 'mysql',
		'user_ro' => 'statuslightdbuser',
		'pass_ro' => 'statuslightpassword',
		'user_rw' => 'statuslightdbuser',
		'pass_rw' => 'statuslightpassword',
		'name' => 'statuslightdb',
		'prefix' => 'sl_'
	),
	'pwsalt' => 'limegreen',
	'signal_default' => (object)array(
		'max_num_events' => 100
	),
	'log' => (object)array(
		'table' => 'log',
		'length' => 1000
	)
);
?>