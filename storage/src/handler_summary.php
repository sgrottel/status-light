<?php
if (1 === preg_match('%/?handler_summary\.php$%i', $_SERVER['PHP_SELF']))
{
	http_response_code(404);
	die();
}
?><html><head><title>Status Light /summary</title></head><body><h1>/summary</h1><code><pre><?php
print($_SERVER['REQUEST_METHOD'] . "\n");
print("$requestPath\n");
print("\nGET:\n");
print_r($_GET);
print("\nPOST:\n");
print_r($_POST);
print("\n");
?></pre></code></body></html>
