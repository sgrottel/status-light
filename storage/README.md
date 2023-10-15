# Status Light™ - Storage Host
This reference implementation of the Status Storage Host in this project is written in Php.
While this limits it's scalability, it allows for deployment on classical managed web hosts.

You can find [more info in it's dedicated documentation](../doc/status_storage.md).

🚧 TODO: Status Storage Host Overview

## Development & Docker
For local development, it's recommended to use the provided docker setup.

Start: (execute in [docker subdirectory](./docker/))
```
docker-compose up -d
```
(`-d` to detach console)

Storage host is then running on: http://localhost:48000

The "myphpadmin" web app connected to the host's data base is then running on: http://localhost:48002

Stop:
```
docker-compose down -v
```
(`-v` to remove volumes, which are temporary in this test environment)


🚧 TODO: Development 

## Test calls
```pwsh
$data = (@{s='demosensor';v='y';d='Just a demo';u='https://www.sgrottel.de';t=(Get-Date -AsUTC -Format s);rt=(Get-Date -AsUTC -Format s)} | ConvertTo-Json)

$resp = $null; $resp = (Invoke-Webrequest -Method POST -Uri http://localhost:48000/in -UseBasicParsing -Body $data -ContentType 'application/json' -Headers @{Authorization='Bearer demotoken'}); $resp.RawContent

$resp = $null; $resp = (Invoke-WebRequest "http://localhost:48000/in?s=FirstDemo&v=g" -Headers @{Authorization='Bearer demotoken'}); $resp.RawContent

foreach ($i in 1..10) { $v = Get-Random 5; $s = 'sensor-' + (Get-Random 3); $r = Invoke-WebRequest "http://localhost:48000/in?s=$s&v=$v" -Headers @{Authorization="Bearer demotoken"}; Start-Sleep 1; }

foreach ($i in 1..100) { $v = Get-Random 5; $s = 'sensor-' + (Get-Random 3); $r = Invoke-WebRequest "http://localhost:48000/in?s=$s&v=$v" -Headers @{Authorization="Bearer demotoken"}; Start-Sleep -Milliseconds (100 + (Get-Random 900)); }
```

🚧 TODO: Test in Docker

🚧 TODO: Test with Online Installation

## Data Base
```sql
DROP VIEW IF EXISTS `sl_newest_events`;
DROP TABLE IF EXISTS `sl_events`;
DROP TABLE IF EXISTS `sl_signals`;

CREATE TABLE IF NOT EXISTS `sl_signals` (
	`i` INT NOT NULL AUTO_INCREMENT,
	`id` TEXT NOT NULL,
	`max_num_events` INT NOT NULL,
	`comment` TEXT,
	`fallback_1_value` TINYINT UNSIGNED NOT NULL DEFAULT 3,
	`fallback_1_timeout` INT NOT NULL DEFAULT 0,
	`fallback_2_value` TINYINT UNSIGNED NOT NULL DEFAULT 4,
	`fallback_2_timeout` INT NOT NULL DEFAULT 0,
	`override_value` TINYINT UNSIGNED NOT NULL DEFAULT 0,
	`override_start_time` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
	`override_end_time` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
	`override_auto_reset` BOOLEAN NOT NULL DEFAULT FALSE,
	PRIMARY KEY (`i`)
);

CREATE TABLE IF NOT EXISTS `sl_events` (
	`i` INT NOT NULL AUTO_INCREMENT,
	`signal` INT NOT NULL,
	`value` TINYINT UNSIGNED NOT NULL,
	`desc` TEXT,
	`url` TEXT,
	`time` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
	PRIMARY KEY (`i`),
	FOREIGN KEY (`signal`) REFERENCES `sl_signals`(`i`) ON DELETE CASCADE,
	INDEX (`time`)
);

CREATE OR REPLACE VIEW `sl_newest_events` AS
SELECT * FROM `sl_events` WHERE `i` IN (SELECT MAX(`i`) FROM `sl_events` WHERE (`signal`,`time`) IN (SELECT `signal`,MAX(`time`) FROM `sl_events` GROUP BY `signal`) GROUP BY `signal`);


DROP TABLE IF EXISTS `sl_log`;

CREATE TABLE IF NOT EXISTS `sl_log` (
	`i` INT NOT NULL AUTO_INCREMENT,
	`time` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
	`level` TINYINT UNSIGNED NOT NULL,
	`msg` TEXT NOT NULL,
	`src` TEXT NOT NULL,
	PRIMARY KEY (`i`)
);


INSERT INTO `sl_signals`
(`i`, `id`, `max_num_events`)
VALUES
(1, "FirstDemo", 10),
(2, "SecondDemo", 5);

INSERT INTO `sl_events`
(`signal`,`value`)
VALUES
(1, 4),
(2, 2);
```

```sql
INSERT INTO `sl_events`
(`signal`,`value`)
VALUES
(1, 2),
(2, 3);
```

The newest events for all signals:
```sql
SELECT `signal`,MAX(`i`) AS `newest` FROM `sl_events` WHERE (`signal`,`time`) in (SELECT `signal`,MAX(`time`) FROM `sl_events` GROUP BY `signal`) GROUP BY `signal`;

SELECT * FROM `sl_signals` LEFT JOIN (SELECT `signal`,`value`,`time`,(CURRENT_TIMESTAMP - `time`) AS `age` FROM `sl_events` WHERE `i` IN (SELECT MAX(`i`) AS `newest` FROM `sl_events` WHERE (`signal`,`time`) IN (SELECT `signal`,MAX(`time`) FROM `sl_events` GROUP BY `signal`) GROUP BY `signal`)) AS `latest_events` ON `sl_signals`.`i` = `latest_events`.`signal`;

SELECT
    `sl_signals`.`id`,
    `sl_signals`.`comment`,
    `sl_signals`.`fallback_1_value`,
    `sl_signals`.`fallback_1_timeout`,
    `sl_signals`.`fallback_2_value`,
    `sl_signals`.`fallback_2_timeout`,
    `sl_signals`.`override_value`,
    `sl_signals`.`override_start_time`,
    `sl_signals`.`override_end_time`,
    `sl_signals`.`override_auto_reset`,
    `sl_newest_events`.`value`,
    `sl_newest_events`.`desc`,
    `sl_newest_events`.`url`,
    `sl_newest_events`.`time`,
    (CURRENT_TIMESTAMP - `sl_newest_events`.`time`) AS `age`
FROM
    `sl_signals`
LEFT JOIN `sl_newest_events` ON `sl_signals`.`i` = `sl_newest_events`.`signal`;
```
