<?php
if (1 === preg_match('%/?handler_results\.php$%i', $_SERVER['PHP_SELF']))
{
	http_response_code(404);
	die();
}

// *** BEGIN TEMPORARY BASIC AUTHENTICATION
// TODO: change me
// Check if the authorization header exists
if (!isset($_SERVER['PHP_AUTH_USER']) || !isset($_SERVER['PHP_AUTH_PW']))
 {
	header('WWW-Authenticate: Basic realm="StatusLight"');
	header('HTTP/1.0 401 Unauthorized');
	echo 'Authentication required!';
	die();
}
$username = $_SERVER['PHP_AUTH_USER'];
$password = $_SERVER['PHP_AUTH_PW'];
if ($username === 'user' && $password === 'test')
{
	// ok!
}
else
{
	header('HTTP/1.0 403 Forbidden');
	die();
}
// *** END TEMPORARY BASIC AUTHENTICATION

$prefix = $sql->GetPrefix();
$conn = $sql->OpenRo();
if (!$conn)
{
	http_response_code(500); // data base connection error
	error_log('data base connection error');
	die();
}

// I don't like the way prefix is added here...
$stmt = $conn->prepare("
SELECT
    CASE
        WHEN E.`end` IS NULL THEN 0 
        WHEN NOW() <= E.`end` THEN (E.`value`)
        WHEN TIMESTAMPDIFF(MINUTE, E.`end`, NOW()) <= L.`silence_timeout_minutes` THEN (L.`after_value`)
        ELSE (L.`silence_value`)
    END AS `val`,
    L.`id`,
    E.`emit` as `event_emit`,
    E.`end` as `event_end`,
    E.`description` as `event_description`,
    E.`url` as `event_url`,
    L.`description` as `line_description`,
    L.`url` as `line_url`
FROM `{$prefix}lines` AS L
LEFT JOIN (
    SELECT *
    FROM `{$prefix}events`
    WHERE (`line`, `emit`) IN (
        SELECT `line`, MAX(`emit`)
        FROM `{$prefix}events`
        GROUP BY `line`
    )
) AS E ON E.`line` = `L`.i;
");

if (!$stmt->execute())
{
	$stmt->close();
	$sql->Close();
	http_response_code(500);
	error_log('failed to execute statement');
	die();
}
$res = $stmt->get_result();
if ($res)
{
	$data = $res->fetch_all();

	$data = array_map(
		function ($row) {
			$o = [
				'id' => $row[1],
				'val' => intval($row[0]),
				'emit' => $row[2]
			];
			if ($row[3])
			{
				$o['end'] = $row[3];
			}
			if ($row[4])
			{
				$o['event_description'] = $row[4];
			}
			if ($row[5])
			{
				$o['event_url'] = $row[5];
			}
			if ($row[6])
			{
				$o['line_description'] = $row[6];
			}
			if ($row[7])
			{
				$o['line_url'] = $row[7];
			}
			return (object)$o;
		},
		$data);

	header('Content-Type: application/json; charset=utf-8');
	print(json_encode($data));

	$res->close();
}
else
{
	$data = [];
}
$stmt->close();

$sql->Close();

?>