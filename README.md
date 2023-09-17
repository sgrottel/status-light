# Status Light™
Status Light™ is a collection of tools and concepts for light-weight summary displays of multiple status signals, similar to status lights.

[![LICENSE](https://img.shields.io/github/license/sgrottel/status-light)](./LICENSE)

It's goal is to provide a simple and easy overview, like "All is Ok," or "There is something wrong."
It's not aiming to provide detailed status information.
It can defer to other sources for that.

## Status Signals
A status signal is information of one source about it's status.
A source typically is a service, a machine, a device, or a sensor.

Status is defined as:

0. ⚫ Black  -- usually meaning there is no information
1. ⚪ Grey  -- usually meaning there is unspecific or neutral information available
2. 🟢 Green  -- usually meaning the source is all ok 
3. 🟡 Yellow  -- usually meaning the source is in an unchecked warning state
4. 🔴 Red  -- usually meaning the source is in an error state

Meanings of the different colors might vary for different sources.

Status signals information is assumed to be pushed into the service's storage by the sources.
This project's implementations usually will not pull and collect information.

When evaluating a signal's status, the time the last status signal event was pushed into the storage is important as well.
Missing status signal events can change the reported status from the last pushed status, e.g. when a device is not longer sending in status, it's signal can change to yellow (a warning) or red (an error), to call for attention and investigation.

## Status Storage Host
The [Status Storage Host](./storage/README.md) is a web service backend to store and evaluate status signals.
It offers three API access levels, to _post_ status signal events, to _query_ status signal info, and to _manage_ all stored data.
You can find [more info in it's dedicated documentation](./doc/status_storage.md).

The reference implementation of the Status Storage Host in this project is written in Php.
While this limits it's scalability, it allows for deployment on classical managed web hosts.

🚧 TODO: Status Storage Host Overview

## Status Storage Management App
🚧 TODO: Status Storage Management App Overview

## Example Status Signal
🚧 TODO: Example Status Signal Overview

## Status Overview Example
🚧 TODO: Status Overview Example Overview

## Alternatives
This project is specifically aiming for the minimal display use case in small to medium-scale scenarios.
It might not be what you are looking for.
There are many alternatives to consider:

* [InfluxDB](https://www.influxdata.com/)
* [SolarWinds Loggly](https://www.loggly.com/)
* [Microsoft Azure Logs](https://docs.microsoft.com/en-us/azure/azure-monitor/logs/data-platform-logs)
* [Amazon CloudWatch Logs](https://docs.aws.amazon.com/AmazonCloudWatch/latest/logs)
* etc.

## Open Source License
All content of this project is provided freely as open source under the [terms of the Apache License v2](./LICENSE):
> Copyright SGrottel (https://sgrottel.de)
>
> Licensed under the Apache License, Version 2.0 (the "License");
> you may not use this file except in compliance with the License.
> You may obtain a copy of the License at
>
> http://www.apache.org/licenses/LICENSE-2.0
>
> Unless required by applicable law or agreed to in writing, software
> distributed under the License is distributed on an "AS IS" BASIS,
> WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
> See the License for the specific language governing permissions and
> limitations under the License.
