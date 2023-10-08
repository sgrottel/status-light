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
	elseif (301 === $mode)
	{
		header('HTTP/1.1 301 Moved Permanently'); // might change METHOD or BODY
		header('STATUS: 301 Moved Permanently');
	}
	elseif (302 === $mode)
	{
		header('HTTP/1.1 302 Found'); // moved temporarily, might change METHOD or BODY
		header('STATUS: 302 Found');
	}
	elseif (307 === $mode)
	{
		header('HTTP/1.1 307 Temporary Redirect'); // reuse same METHOD and BODY
		header('STATUS: 307 Temporary Redirect');
	}
	elseif (308 === $mode)
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

// Get header Authorization
// https://stackoverflow.com/a/40582472/552373
function getAuthorizationHeader()
{
    $headers = null;
    if (isset($_SERVER['Authorization']))
	{
        $headers = trim($_SERVER['Authorization']);
    }
    elseif (isset($_SERVER['HTTP_AUTHORIZATION']))
	{
		//Nginx or fast CGI
        $headers = trim($_SERVER['HTTP_AUTHORIZATION']);
    }
	elseif (function_exists('apache_request_headers'))
	{
        $requestHeaders = apache_request_headers();
        // Server-side fix for bug in old Android versions (a nice side-effect of this fix means we don't care about capitalization for Authorization)
        $requestHeaders = array_combine(array_map('ucwords', array_keys($requestHeaders)), array_values($requestHeaders));
        if (isset($requestHeaders['Authorization']))
		{
            $headers = trim($requestHeaders['Authorization']);
        }
    }
    return $headers;
}

// Get access token from header
// https://stackoverflow.com/a/40582472/552373
function getBearerToken()
{
    $headers = getAuthorizationHeader();
    // HEADER: Get the access token from the header
    if (!empty($headers))
	{
        if (preg_match('/Bearer\s(\S+)/', $headers, $matches))
		{
            return $matches[1];
        }
    }
    return null;
}

// Collects required and optional payload data from the specified array
// private function, do not call directly.
// ref params for performance reasons. Wont be changed.
function collectPayloadFromArray(&$required, &$optional, &$data)
{
	$payload = array();

	foreach ($required as $key)
	{
		if (array_key_exists($key, $data))
		{
			$payload[$key] = $data[$key];
		}
		else
		{
			http_response_code(400); // required data missing
			die();
		}
	}

	foreach ($data as $key => $value)
	{
		if (in_array($key, $required, true))
		{
			continue;
		}
		if (in_array($key, $optional, true))
		{
			$payload[$key] = $value;
		}
		else
		{
			http_response_code(400); // unexpected data
			die();
		}
	}

	return $payload;
}

// Collects required and optional payload data from the request input data
// will 'die' on missing payload, malformed payload, missing required fields, or unknown fields.
// Does not (cannot) validate field values.
function collectPayloadFromRequest($required, $optional)
{
	// Extend with other request types on demand, e.g. PATCH
	if ('POST' === $_SERVER['REQUEST_METHOD'])
	{
		$contentType = $_SERVER["CONTENT_TYPE"];
		if (0 === strcasecmp($contentType, 'application/x-www-form-urlencoded'))
		{
			return collectPayloadFromArray($required, $optional, $_POST);
		}
		elseif (0 === strcasecmp($contentType, 'application/json'))
		{
			$data = @file_get_contents('php://input');
			if (false === $data)
			{
				http_response_code(400); // failed to read request body
				die();
			}
			$data = @json_decode($data, true);
			if (false === $data || null === $data)
			{
				http_response_code(400); // failed to decode request body as json
				die();
			}
			if (!is_array($data))
			{
				http_response_code(400); // json decode of request body returned unexpected non-array type
				die();
			}
			return collectPayloadFromArray($required, $optional, $data);
		}
		else
		{
			http_response_code(400); // request with unknown/unexpected content type
			die();
		}
	}
	elseif ('GET' === $_SERVER['REQUEST_METHOD'])
	{
		return collectPayloadFromArray($required, $optional, $_GET);
	}
	else
	{
		http_response_code(400); // request malformed
		die();
	}
}

// ensure https
if (($_SERVER['SERVER_NAME'] !== 'localhost')
	&& ($_SERVER['SERVER_NAME'] !== 'apphttpd'))
{
	// only if not a developer's host name
	if (
		(!empty($_SERVER['REQUEST_SCHEME']) && strcasecmp($_SERVER['REQUEST_SCHEME'], 'https') === 0)
		|| (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && 'https' == $_SERVER['HTTP_X_FORWARDED_PROTO'])
		|| (!empty($_SERVER['HTTP_X_FORWARDED_SSL']) && 'on' == $_SERVER['HTTP_X_FORWARDED_SSL'])
		|| (!empty($_SERVER['HTTPS']) && 'off' !== $_SERVER['HTTPS'])
		|| 443 == $_SERVER['SERVER_PORT']
		)
	{
		// https -- good!
	}
	else
	{
		// http -- not good!
		httpMoveAndExit('https://' . $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI'], 308);
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