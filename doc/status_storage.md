# Status Light™ - Storage Host
The Status Storage Host is a web service backend to store and evaluate status signals.
It offers three API levels, to _post_ status signal events, to _query_ status signal info, and to _manage_ all stored data.

## Status Storage and Evaluation
🚧 TODO: Status Storage and Evaluation
* Automatic Status Timeout
* Manual Status Ignore/Overwrite

## API Access
🚧 TODO: API Access

All API routes requires a valid `Bearer` token, if not explicitly stated otherwise.

### Simple Authentication
Some API routes allow for _simple authentication_.

In this case the `Bearer` token is a [JWT](https://jwt.io/introduction).
It's payload is an object with `sub` as only field.
The `sub` is a special access key provided by the management API for a specific purpose, e.g. only able to push data for one specific sensor.

These simple authentication JWTs can be used on noncritical routes, which can either not harm the system, or cannot query sensitive data.

Routs supporting _simple authentication_ will also work with `Bearer` token of full Authentication.


### Full Authentication

🚧 TODO: Document full authentication


## Post API
The Post API provides the easy access point for sensors to push in new information about themselves.

### [GET|POST] https://root/in
Posts a sensor status event into the status storage

Required Parameters:
* `s` [string] _Sensor_ Identifier
* `v` [string[1]] Sensor event _value_

Optional Parameters:
* `d` [string] A short human-readable _description_ of the sensor event, usually a reason for the state
* `u` [string] Web _url_ to access additional information to this sensor event
* `t` [string] A _time stamp_ for this sensor event; if missing, _now_ is assumed;<br>
  Recommendation: include `rt` when specifying `t`.
* `rt` [string] A _reference time stamp_ representing _now_; providing this value accounts for time zone differences, clock differences and clock drifts between the sensor source system and the storage system.<br>
  Only used together with `t`.

Valid values for `v` are:
* '0' or 'k' -- ⚫ Black  -- usually meaning there is no information
* '1' or 'n' -- ⚪ Grey  -- usually meaning there is unspecific or neutral information available
* '2' or 'g' -- 🟢 Green  -- usually meaning the source is all ok 
* '3' or 'y' -- 🟡 Yellow  -- usually meaning the source is in an unchecked warning state
* '4' or 'r' -- 🔴 Red  -- usually meaning the source is in an error state

All other values for `v` result in an error.

The parameters `t` and `rt` must be formatted following [ISO_8601](http://en.wikipedia.org/wiki/ISO_8601).

When using this route with `GET`, specify all values as url parameters.

When using this route with `POST`, specify all values in the request body.
* Recommendation: use a json body and specify `content-type: application/json` 
* Alternative: use a form-like body and, depending on your upload format, specify either:
  - `content-type: application/x-www-form-urlencoded`
  - `content-type: multipart/form-data`

This route is throttled and might reject requests coming in to quickly one after the other.

Response Codes:
* `200` or `204` -- sensor event was successfully stored
* `400`
  * if `s` or `v` are missing from the request
  * if the value of `v` is unsupported
  * if the value of any parameter is malformed
* `401` -- if the `Bearer` token is missing or malformed
* `403` -- if the `Bearer` token is valid, but the respective account is not allowed to post events for the sensor
* `429` -- if processing the request is not possible due to the rate limit throttling.
* `500` -- if the storage backend failed to store the sensor event for any reason

Whether the success is indicated by `200` or `204` is up to the configuration and implementation of the storage backend.

This route allows for _simple authentication_ with the subject being the specific _signal pusher_.


🚧 TODO: more Push API


## Query API
The Query API provides summary and details to the stored status information.

### [GET] https://root/summary
The `summary` route returns a json object summarizing all relevant status event signals in simple counters.

🚧 TODO: summary route parameters and output

This route allows for _simple authentication_ with the subject being the _summary querier_.


🚧 TODO: more Query API


## Management API
🚧 TODO: more Management API

## Deployment & Installation
You can find details on [installation, configuration, and deployment of the storage host in it's dedicated install.md documentation](../storage/install.md).

## Development & Test
You can find details for development and test in the [dedicated development documentation of the storage host](../storage/README.md).
