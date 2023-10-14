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
```

🚧 TODO: Test in Docker

🚧 TODO: Test with Online Installation

## Data Base
```sql
DROP TABLE IF EXISTS `sl_events`;
DROP TABLE IF EXISTS `sl_signals`;

CREATE TABLE IF NOT EXISTS `sl_signals` (
    `i` INT NOT NULL AUTO_INCREMENT,
    `id` TEXT NOT NULL,
	`max_num_events` INT NOT NULL,
    PRIMARY KEY (`i`)
);

CREATE TABLE IF NOT EXISTS `sl_events` (
    `i` INT NOT NULL AUTO_INCREMENT,
	`signal` INT NOT NULL,
    `value` TINYINT UNSIGNED NOT NULL,
    `desc` TEXT,
    `url` TEXT,
    `time` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`i`),
    FOREIGN KEY (`signal`) REFERENCES `sl_signals`(`i`) ON DELETE CASCADE
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
