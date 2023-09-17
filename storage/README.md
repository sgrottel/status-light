# Status Light™ - Storage Host
This reference implementation of the Status Storage Host in this project is written in Php.
While this limits it's scalability, it allows for deployment on classical managed web hosts.

You can find [more info in it's dedicated documentation](../doc/status_storage.md).

🚧 TODO: Status Storage Host Overview

## Development & Docker
For local development, it's recommended to use the provided docker setup.

Start: (execute in [docker subdirectory](./docker/))
```ps
docker-compose up -d
```
(`-d` to detach console)

Storage host is then running on: http://localhost:48000

The "myphpadmin" web app connected to the host's data base is then running on: http://localhost:48002

Stop:
```ps
docker-compose down -v
```
(`-v` to remove volumes, which are temporary in this test environment)


🚧 TODO: Development 

🚧 TODO: Test in Docker

🚧 TODO: Test with Online Installation
