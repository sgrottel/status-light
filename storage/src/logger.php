<?php
// file guard disallowing direct invocation
if (1 === preg_match('%/?logger\.php$%i', $_SERVER['PHP_SELF']))
{
	http_response_code(404);
	die();
}

/**
 * Describes log levels.
 */
class LogLevel
{
	const EMERGENCY = 'emergency';
	const ALERT     = 'alert';
	const CRITICAL  = 'critical';
	const ERROR     = 'error';
	const WARNING   = 'warning';
	const NOTICE    = 'notice';
	const INFO      = 'info';
	const DEBUG     = 'debug';
}

/**
 * Simple logger class publishing to the log table in the sql db
 * Implements [PSR-3](https://www.php-fig.org/psr/psr-3/)
 */
class Logger {
	private $sql = null;
	private $tableName = null;
	private $logLength = 1000;
	public function __construct($sql, $tableName, $logLength)
	{
		$this->sql = $sql;
		$this->tableName = $tableName;
		$this->logLength = $logLength;
	}
	public function __destruct()
	{
		$this->sql = null;
	}

	/**
	 * Interpolates context values into the message placeholders.
	 */
	private function interpolate($message, array $context = array())
	{
		// build a replacement array with braces around the context keys
		$replace = array();
		foreach ($context as $key => $val) {
			// check that the value can be cast to string
			if (!is_array($val) && (!is_object($val) || method_exists($val, '__toString'))) {
				$replace['{' . $key . '}'] = $val;
			}
		}

		// interpolate replacement values into the message and return
		return strtr($message, $replace);
	}

	/**
	 * Logs with an arbitrary level.
	 *
	 * @param mixed $level
	 * @param string $message
	 * @param array $context
	 * @return void
	 */
	public function log($level, $message, array $context = array())
	{
		if (is_array($context) && count($context) > 0) {
			$message = $this->interpolate($message, $context);
		}
		switch ($level)
		{
			case LogLevel::EMERGENCY: $l = 0; break;
			case LogLevel::ALERT: $l = 1; break;
			case LogLevel::CRITICAL: $l = 2; break;
			case LogLevel::ERROR: $l = 3; break;
			case LogLevel::WARNING: $l = 4; break;
			case LogLevel::NOTICE: $l = 5; break;
			case LogLevel::INFO: $l = 6; break;
			case LogLevel::DEBUG: $l = 7; break;
			default: $l = 2; $message = "Illegal Level '$level'; $message"; break; // implementation error is critical
		}

		$conn = $this->sql->OpenRw();
		$stmt = $conn->prepare("INSERT INTO `{$this->tableName}` (`level`,`msg`,`src`) VALUES (?,?,?)");
		if ($stmt)
		{
			$source = array_reduce(
				debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 4),
				function($str, $f) {
					if ($str === null) return '';
					return $str . $f['file'] . ':' . $f['line'] . "\n";
				},
				null
			);
			if ($stmt->bind_param('iss', $l, $message, $source))
			{
				$stmt->execute();
			}
		}
		$stmt = $conn->prepare("DELETE FROM `{$this->tableName}` WHERE `i` IN (SELECT `i` from (SELECT `i` FROM `{$this->tableName}` ORDER BY `time` DESC LIMIT ?, 100000) x)");
		if ($stmt)
		{
			if ($stmt->bind_param('i', $this->logLength))
			{
				$stmt->execute();
			}
		}

		die();
	}

	/**
	 * System is unusable.
	 *
	 * @param string $message
	 * @param array $context
	 * @return void
	 */
	public function emergency($message, array $context = array())
	{
		$this->log(LogLevel::EMERGENCY, $message, $context);
	}

	/**
	 * Action must be taken immediately.
	 *
	 * Example: Entire website down, database unavailable, etc. This should
	 * trigger the SMS alerts and wake you up.
	 *
	 * @param string $message
	 * @param array $context
	 * @return void
	 */
	public function alert($message, array $context = array())
	{
		$this->log(LogLevel::ALERT, $message, $context);
	}

	/**
	 * Critical conditions.
	 *
	 * Example: Application component unavailable, unexpected exception.
	 *
	 * @param string $message
	 * @param array $context
	 * @return void
	 */
	public function critical($message, array $context = array())
	{
		$this->log(LogLevel::CRITICAL, $message, $context);
	}

	/**
	 * Runtime errors that do not require immediate action but should typically
	 * be logged and monitored.
	 *
	 * @param string $message
	 * @param array $context
	 * @return void
	 */
	public function error($message, array $context = array())
	{
		$this->log(LogLevel::ERROR, $message, $context);
	}

	/**
	 * Exceptional occurrences that are not errors.
	 *
	 * Example: Use of deprecated APIs, poor use of an API, undesirable things
	 * that are not necessarily wrong.
	 *
	 * @param string $message
	 * @param array $context
	 * @return void
	 */
	public function warning($message, array $context = array())
	{
		$this->log(LogLevel::WARNING, $message, $context);
	}

	/**
	 * Normal but significant events.
	 *
	 * @param string $message
	 * @param array $context
	 * @return void
	 */
	public function notice($message, array $context = array())
	{
		$this->log(LogLevel::NOTICE, $message, $context);
	}

	/**
	 * Interesting events.
	 *
	 * Example: User logs in, SQL logs.
	 *
	 * @param string $message
	 * @param array $context
	 * @return void
	 */
	public function info($message, array $context = array())
	{
		$this->log(LogLevel::INFO, $message, $context);
	}

	/**
	 * Detailed debug information.
	 *
	 * @param string $message
	 * @param array $context
	 * @return void
	 */
	public function debug($message, array $context = array())
	{
		$this->log(LogLevel::DEBUG, $message, $context);
	}
}
?>