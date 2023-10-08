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
$data = (@{s='demosensor';v='y';d='Just a demo';u='https://www.sgrottel.de';t=(Get-Date -AsUTC -Format r);rt=(Get-Date -AsUTC -Format r)} | ConvertTo-Json)

$resp = (Invoke-Webrequest -Method POST -Uri http://localhost:48000/in -UseBasicParsing -Body $data -ContentType 'application/json' -Headers @{Authorization='Bearer demotoken'}); $resp.RawContent
```

🚧 TODO: Test in Docker

🚧 TODO: Test with Online Installation
