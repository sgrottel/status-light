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

🚧 TODO: Development 


### Deprecated

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

There are two database tables: `sl_lines` and `sl_events`.

`sl_lines` represent individual signals, e.g. sensors, on which events come into the system.
Those lines have a unique id, optionally a human readable name and description, and a configuration.
Those configurations include value fallback mechanisms when a line has no active events any more after specified timeouts, and the overall number of events that will be hold as history.
It also holds some default values for events emitted on that line, if those values are not specified during emit, most notably the duration of the event.

`sl_events` is the table of the events emitted on the lines.
Only, the newest emitted event on each line can be active.
Each event has a value, optionally a short desceription and url to more information, an emit datetime, and a deactivate datetime.
 
Use the following script to initialize the database during installation:

```sql
DROP PROCEDURE IF EXISTS `sl_p_events_cleanup`;
DROP FUNCTION IF EXISTS `sl_f_line_by_id`;
DROP TRIGGER IF EXISTS `sl_t_events_insert_auto_end`;
DROP TABLE IF EXISTS `sl_events`;
DROP TABLE IF EXISTS `sl_lines`;

CREATE TABLE IF NOT EXISTS `sl_lines` (
    `i` INT NOT NULL AUTO_INCREMENT,
    `id` TEXT NOT NULL,
    `max_num_events` INT NOT NULL DEFAULT 100,
    `description` TEXT,
    `url` TEXT,
    `default_event_duration_minutes` INT NOT NULL DEFAULT (24*60),
    `after_value` TINYINT UNSIGNED NOT NULL DEFAULT 3,
    `silence_timeout_minutes` INT NOT NULL DEFAULT (6*60),
    `silence_value` TINYINT UNSIGNED NOT NULL DEFAULT 4,
    PRIMARY KEY (`i`)
);

CREATE TABLE IF NOT EXISTS `sl_events` (
    `i` INT NOT NULL AUTO_INCREMENT,
    `line` INT NOT NULL,
    `value` TINYINT UNSIGNED NOT NULL,
    `emit` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `end` TIMESTAMP NOT NULL, -- if omitted, will be set by sl_t_insert_auto_end trigger
    `description` TEXT,
    `url` TEXT,
    PRIMARY KEY (`i`),
    FOREIGN KEY (`line`) REFERENCES `sl_lines`(`i`) ON DELETE CASCADE,
    INDEX(`emit`)
);

DELIMITER &&

CREATE OR REPLACE TRIGGER `sl_t_events_insert_auto_end`
BEFORE INSERT ON `sl_events`
FOR EACH ROW
BEGIN
    IF NEW.`end` IS NULL THEN
        SET NEW.`end` = NOW() + INTERVAL (SELECT `default_event_duration_minutes` FROM `sl_lines` WHERE `i` = NEW.`line`) MINUTE;
    END IF;
END;
&&

CREATE OR REPLACE FUNCTION `sl_f_line_by_id`(IN id TEXT) RETURNS INT
DETERMINISTIC
BEGIN
    DECLARE `line_i` INT;
    SELECT `i` INTO `line_i` FROM `sl_lines` l WHERE l.`id` = `id` LIMIT 1;
    RETURN `line_i`;
END;
&&

DELIMITER ;

CREATE OR REPLACE PROCEDURE `sl_p_events_cleanup`()
DELETE FROM `sl_events`
WHERE i IN (
    WITH SORTED_EVENTS AS (SELECT *, ROW_NUMBER() OVER (PARTITION BY `sl_events`.`line` ORDER BY `sl_events`.`emit` DESC) AS `row_num` FROM `sl_events`)
    SELECT SE.`i` FROM SORTED_EVENTS SE JOIN `sl_lines` ON SE.`line` = `sl_lines`.`i` WHERE SE.`row_num` > `sl_lines`.`max_num_events`
);
```

If you want to use another db prefix than `sl_` rename all occurances in the script above accordingly.


### Development Queries

You can add lines by:

```sql
INSERT INTO `sl_lines`
(`i`, `id`)
VALUES
(1, "DemoLine#1"),
(2, "DemoLine#2");
```

You can emit events by:

```sql
INSERT INTO `sl_events`
(`line`,`value`)
VALUES
(sl_f_line_by_id("DemoLine#1"), 1),
(sl_f_line_by_id("DemoLine#2"), 1);
CALL `sl_p_events_cleanup`();
```


### Developer's Notes

The event cleanup `sl_p_events_cleanup` cannot be run as a trigger on `sl_events` as MariaDB prohibits triggers to change the same table.
This is to avoid recursion.
The solutions are to either
a) run two statements in the same query (see emit event dev example query) or to have both statements in one wrapped stored procedure, or to
b) run the cleanup procedure as a scheduled event, e.g. nightly.


### Dev Scratchboard:

#### Summary Query

Full Query:
```sql
SELECT
    CASE
        WHEN E.`end` IS NULL THEN 0 
        WHEN NOW() <= E.`end` THEN (E.`value`)
        WHEN TIMESTAMPDIFF(MINUTE, E.`end`, NOW()) <= L.`silence_timeout_minutes` THEN (L.`after_value`)
        ELSE (L.`silence_value`)
    END AS `val`,
    L.`id`,
    E.`end` as `event_end`,
    E.`description` as `event_description`,
    E.`url` as `event_url`,
    L.`description` as `line_description`,
    L.`url` as `line_url`
FROM `sl_lines` AS L
LEFT JOIN (
    SELECT *
    FROM `sl_events`
    WHERE (`line`, `emit`) IN (
        SELECT `line`, MAX(`emit`)
        FROM `sl_events`
        GROUP BY `line`
    )
) AS E ON E.`line` = `L`.i;
```

Summary Counters:
```sql
SELECT
    CASE
        WHEN E.`end` IS NULL THEN 0 
        WHEN NOW() <= E.`end` THEN (E.`value`)
        WHEN TIMESTAMPDIFF(MINUTE, E.`end`, NOW()) <= L.`silence_timeout_minutes` THEN (L.`after_value`)
        ELSE (L.`silence_value`)
    END AS `val`,
    COUNT(*) as `count`
FROM `sl_lines` AS L
LEFT JOIN (
    SELECT *
    FROM `sl_events`
    WHERE (`line`, `emit`) IN (
        SELECT `line`, MAX(`emit`)
        FROM `sl_events`
        GROUP BY `line`
    )
) AS E ON E.`line` = `L`.i
GROUP BY `val`
```

```sql
SELECT
    CASE
        WHEN E.`end` IS NULL THEN 0 
        WHEN NOW() <= E.`end` THEN (E.`value`)
        WHEN TIMESTAMPDIFF(MINUTE, E.`end`, NOW()) <= L.`silence_timeout_minutes` THEN (L.`after_value`)
        ELSE (L.`silence_value`)
    END AS `val`,
    L.`id`
FROM `sl_lines` AS L
LEFT JOIN (
    SELECT *
    FROM `sl_events`
    WHERE (`line`, `emit`) IN (
        SELECT `line`, MAX(`emit`)
        FROM `sl_events`
        GROUP BY `line`
    )
) AS E ON E.`line` = `L`.i;

SELECT
    l.`id`,
    CASE
        WHEN NOW() <= t.`end` THEN (t.`value`)
        WHEN TIMESTAMPDIFF(MINUTE, t.`end`, NOW()) <= l.`silence_timeout_minutes` THEN (l.`after_value`)
        ELSE (l.`silence_value`)
    END AS `val`
FROM `sl_events` t
LEFT JOIN `sl_lines` l ON l.`i` = t.`line`
    WHERE t.`emit` = (SELECT MAX(`emit`) FROM `sl_events` WHERE `line`= t.`line`);


SELECT
    CASE
        WHEN NOW() <= t.`end` THEN (t.`value`)
        WHEN TIMESTAMPDIFF(MINUTE, t.`end`, NOW()) <= l.`silence_timeout_minutes` THEN (l.`after_value`)
        ELSE (l.`silence_value`)
    END AS `val`,
    COUNT(*) AS count
FROM `sl_events` t
LEFT JOIN `sl_lines` l ON l.`i` = t.`line`
    WHERE t.`emit` = (SELECT MAX(`emit`) FROM `sl_events` WHERE `line`= t.`line`)
    GROUP BY `val`;


SELECT
    TIMESTAMPDIFF(MINUTE, t.`end`, NOW()) as age,
    CASE
        WHEN NOW() <= t.`end` THEN (t.`value`)
        WHEN TIMESTAMPDIFF(MINUTE, t.`end`, NOW()) <= l.`silence_timeout_minutes` THEN (l.`after_value`)
        ELSE (l.`silence_value`)
    END AS `effective_value`,
    t.*,
    l.`id` AS `line_id`,
    l.`after_value`,
    l.`silence_value`,
    l.`silence_timeout_minutes`
    FROM `sl_events` t
LEFT JOIN `sl_lines` l ON l.`i` = t.`line`
WHERE t.`emit` = (SELECT MAX(`emit`) FROM `sl_events` WHERE `line`= t.`line`);


UPDATE `sl_events` SET `end` = NOW() WHERE `sl_events`.`i` = 8;

```


#### Cleanup

```sql
SELECT line, COUNT(*) as count FROM sl_events GROUP BY line;

WITH SORTED_EVENTS AS (SELECT *, ROW_NUMBER() OVER (PARTITION BY sl_events.line ORDER BY sl_events.emit DESC) AS row_num FROM sl_events)
SELECT SE.i FROM SORTED_EVENTS SE JOIN sl_lines ON SE.line = sl_lines.i WHERE SE.row_num > sl_lines.max_num_events;

CREATE OR REPLACE TRIGGER sl_t_after_insert_events
AFTER INSERT ON sl_events
FOR EACH ROW
DELETE FROM sl_events
WHERE i IN (
    WITH SORTED_EVENTS AS (SELECT *, ROW_NUMBER() OVER (PARTITION BY sl_events.line ORDER BY sl_events.emit DESC) AS row_num FROM sl_events)
    SELECT SE.i FROM SORTED_EVENTS SE JOIN sl_lines ON SE.line = sl_lines.i WHERE SE.row_num > sl_lines.max_num_events
);

```


### Deprecated

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
