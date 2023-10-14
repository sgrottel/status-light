<?php
// file guard disallowing direct invocation
if (1 === preg_match('%/?handler_in\.php$%i', $_SERVER['PHP_SELF']))
{
	http_response_code(404);
	die();
}

// early init
$config = @include(__DIR__.'/config.php');
if (!$config)
{
	http_response_code(500); // load config error
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
switch ($payload['v'])
{
	case '0':
	case 'k':
		$payload['v'] = 0;
		break;
	case '1':
	case 'n':
		$payload['v'] = 1;
		break;
	case '2':
	case 'g':
		$payload['v'] = 2;
		break;
	case '3':
	case 'y':
		$payload['v'] = 3;
		break;
	case '4':
	case 'r':
		$payload['v'] = 4;
		break;
	default:
		http_response_code(400); // illegal value
		die();
}
if (isset($payload['t']) && $payload['t'] !== null)
{
	$t = date_create_immutable_from_format("Y-m-d\\TH:i:sO", $payload['t']);
	if ($t === false)
	{
		$t = date_create_immutable_from_format("Y-m-d\\TH:i:s", $payload['t']);
	}
	if ($t === false)
	{
		http_response_code(400); // illegal date time format
		die();
	}
	$payload['t'] = $t;
}
if (isset($payload['rt']) && $payload['rt'] !== null)
{
	$t = date_create_immutable_from_format("Y-m-d\\TH:i:sO", $payload['rt']);
	if ($t === false)
	{
		$t = date_create_immutable_from_format("Y-m-d\\TH:i:s", $payload['rt']);
	}
	if ($t === false)
	{
		http_response_code(400); // illegal date time format
		die();
	}
	$payload['rt'] = $t;
}

require_once __DIR__.'/sqlServer.php';
$sql = new SqlConnection($config->db);

$prefix = $sql->GetPrefix();
if (!is_string($prefix))
{
	http_response_code(500); // config error, prefix string type
	die();
}
$conn = $sql->OpenRo();
if (!$conn)
{
	http_response_code(500); // data base connection error
	die();
}

// identify signal
$signalId = null;
$signalMaxNumEvents = 0;

$stmt = $conn->prepare("SELECT `i`, `max_num_events` FROM `{$prefix}signals` WHERE `id`=?");
if (!$stmt)
{
	http_response_code(500); // failed to prepare statement, likely DB issue.
	exit;
}
if (!$stmt->bind_param('s', $payload['s']))
{
	$stmt->close();
	http_response_code(500); // failed to bind parameter; type issue?
	exit;
}
if (!$stmt->execute())
{
	$stmt->close();
	http_response_code(500); // failed to bind parameter; type issue?
	exit;
}
$res = $stmt->get_result();
if ($res)
{
	$data = $res->fetch_all();
	if (is_array($data) && count($data) === 1 && is_array($data[0]) && count($data[0]) === 2)
	{
		$signalId = $data[0][0];
		$signalMaxNumEvents = $data[0][1];
	}
	$res->close();
}
$stmt->close();

if ($signalId === null)
{
	// new, unknown signal. Add to DB
	$conn = $sql->OpenRw();
	if (!$conn)
	{
		http_response_code(500); // data base connection error
		die();
	}
	
	$stmt = $conn->prepare("INSERT INTO `{$prefix}signals` (`id`, `max_num_events`) VALUES (?, ?)");
	if (!$stmt)
	{
		http_response_code(500); // failed to prepare statement, likely DB issue.
		exit;
	}
	if (!$stmt->bind_param('si', $payload['s'], $config->signal_default->max_num_events))
	{
		$stmt->close();
		http_response_code(500); // failed to bind parameter; type issue?
		exit;
	}
	if (!$stmt->execute())
	{
		$stmt->close();
		http_response_code(500); // failed to bind parameter; type issue?
		exit;
	}
	if ($stmt->errno !== 0 || $stmt->affected_rows !== 1)
	{
		$stmt->close();
		http_response_code(500); // failed add signal to DB
		exit;
	}

	$signalId = $stmt->insert_id;
	$signalMaxNumEvents = $config->signal_default->max_num_events;

	$stmt->close();

}

// Add signal event
$insertSuccess = false;
$conn = $sql->OpenRw();
if (!$conn)
{
	http_response_code(500); // data base connection error
	die();
}

$varStr = '`signal`,`value`';
$varSetStr = '?,?';
$bindStr = 'ii';
$bindVars = array(&$signalId, &$payload['v']);
$setTimeValue = null;
if (isset($payload['d']) && $payload['d'] !== null)
{
	$varStr .= ',`desc`';
	$varSetStr .= ',?';
	$bindStr .= 's';
	$bindVars[] = &$payload['d'];
}
if (isset($payload['u']) && $payload['u'] !== null)
{
	$varStr .= ',`url`';
	$varSetStr .= ',?';
	$bindStr .= 's';
	$bindVars[] = &$payload['u'];
}
if (isset($payload['t']) && $payload['t'] !== null)
{
	$varStr .= ',`time`';
	if (isset($payload['rt']) && $payload['rt'] !== null)
	{
		$varSetStr .= ',DATE_ADD(CURRENT_TIMESTAMP, INTERVAL ? SECOND)';
		$setTimeValue = ($payload['t']->getTimestamp() - $payload['rt']->getTimestamp());
		$bindStr .= 'i';
	}
	else
	{
		$varSetStr .= ',?';
		$setTimeValue = $payload['t']->format('Y-m-d H:i:s');
		$bindStr .= 's';
	}
	$bindVars[] = &$setTimeValue;
}

$stmt = $conn->prepare("INSERT INTO `{$prefix}events` ({$varStr}) VALUES ({$varSetStr})");
if (!$stmt)
{
	http_response_code(500); // failed to prepare statement, likely DB issue.
	print("prepare\n");
	exit;
}
if (!$stmt->bind_param($bindStr, ...$bindVars))
{
	$stmt->close();
	http_response_code(500); // failed to bind parameter; type issue?
	print("bind\n");
	exit;
}
if (!$stmt->execute())
{
	$stmt->close();
	http_response_code(500); // failed to bind parameter; type issue?
	print("exec\n");
	exit;
}
$insertSuccess = ($stmt->affected_rows === 1);
$stmt->close();

// Signal event table housekeeping
// If the statement fails, only log warnings, but don't fail the API
$stmt = $conn->prepare("DELETE FROM `{$prefix}events` WHERE `i` IN (SELECT `i` from (SELECT `i` FROM `{$prefix}events` WHERE `signal`=? ORDER BY `time` DESC LIMIT ?, 100000) x)");
if ($stmt)
{
	if ($stmt->bind_param('ii', $signalId, $signalMaxNumEvents))
	{
		$stmt->execute();
	}
	$stmt->close();
}

// All done.
$sql->Close();

if ($insertSuccess === true)
{
	http_response_code(200);
}
else
{
	http_response_code(500); // Something did not work
}
exit();
?>