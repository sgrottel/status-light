<?php
// Utility
function httpMoveAndExit($location, $mode = 303)
{
	if (headers_sent())
	{
		// we already are in page mode. Header redirect is no longer possible.
		// Instead we inject a javascript code:
		$uri = "{$_SERVER['REQUEST_SCHEME']}://{$_SERVER['HTTP_HOST']}$location";
		echo "<script>window.location.replace('$uri');</script>\n";
		echo "<p>Redirecting to: <a href='$uri'>$uri</a></p>\n";
	}
	elseif ($mode === 301)
	{
		header('HTTP/1.1 301 Moved Permanently'); // might change METHOD or BODY
		header('STATUS: 301 Moved Permanently');
	}
	elseif ($mode === 302)
	{
		header('HTTP/1.1 302 Found'); // moved temporarily, might change METHOD or BODY
		header('STATUS: 302 Found');
	}
	elseif ($mode === 307)
	{
		header('HTTP/1.1 307 Temporary Redirect'); // reuse same METHOD and BODY
		header('STATUS: 307 Temporary Redirect');
	}
	elseif ($mode === 308)
	{
		header('HTTP/1.1 308 Permanent Redirect'); // reuse same METHOD and BODY
		header('STATUS: 308 Permanent Redirect');
	}
	else
	{
		header('HTTP/1.1 303 See Other'); // always switch to GET
		header('STATUS: 303 See Other');
	}
	header("Location: $location");
	exit();
}

// ensure https
if (($_SERVER['SERVER_NAME'] !== 'localhost') && ($_SERVER['SERVER_NAME'] !== 'apphttpd'))
{
	// only if not a developer's host name
	if ( (! empty($_SERVER['REQUEST_SCHEME']) && $_SERVER['REQUEST_SCHEME'] == 'https') ||
	(! empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') ||
	(! empty($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == '443') ) {
		// https -- good!
	} else {
		// http -- not good!
		httpMoveAndExit($_SERVER['REQUEST_URI'], 308);
	}
}

// always a dynamic page
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

$requestPath = strtok($_SERVER['REQUEST_URI'], '?');

if (0 === strcasecmp($requestPath, '/in'))
{
	include(__DIR__.'/handler_in.php');
	exit();
}
elseif (0 === strcasecmp($requestPath, '/summary'))
{
	include(__DIR__.'/handler_summary.php');
	exit();
}
else
{
	// unknown/unsupported route
	http_response_code(404);
	exit();
}
?>