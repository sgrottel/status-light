<?php
// file guard disallowing direct invocation
if (1 === preg_match('%/?handler_in\.php$%i', $_SERVER['PHP_SELF']))
{
	http_response_code(404);
	die();
}

// check bearer token
$bearer = getBearerToken();
if (!$bearer)
{
	http_response_code(401); // missing or malformed bearer
	die();
}
// TODO: next line is for easy early development -- replace
if ('demotoken' !== $bearer)
{
	http_response_code(403); // access denied bearer
	die();
}

// load payload
$payload = collectPayloadFromRequest(array('s', 'v'), array('d', 'u', 't', 'rt'));

?><html><head><title>Status Light /in</title></head><body><h1>/in</h1><code><pre><?php
print($_SERVER['REQUEST_METHOD'] . "\n");
print("$requestPath\n");
print("\nRequest Payload:\n");
print_r($payload);
print("\n");
?></pre></code></body></html>
