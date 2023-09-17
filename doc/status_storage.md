# Status Light™ Storage Host
The Status Storage Host is a web service backend to store and evaluate status signals.
It offers three API levels, to _post_ status signal events, to _query_ status signal info, and to _manage_ all stored data.

## Status Storage and Evaluation
🚧 TODO: Status Storage and Evaluation
* Automatic Status Timeout
* Manual Status Ignore/Overwrite

## API Access
🚧 TODO: API Access

All API routes requires a valid `bearer` token, if not explicitly stated otherwise.


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

When using this route with `GET`, specify all values as url parameters.

When using this route with `POST`, specify all values in the request body.
* Recommendation: use a json body.
* Alternative a FORM-Data body is possible (?)

Response Codes:
* `200` or `204` -- sensor event was successfully stored
* `400`
  * if `s` or `v` are missing from the request
  * if the value of `v` is unsupported
  * if the value of any parameter is malformed
* `401` -- if the `bearer` token is missing or malformed
* `403` -- if the `bearer` token is valid, but the respective account is not allowed to post events for the sensor
* `500` -- if the storage backend failed to store the sensor event for any reason

Whether the success is indicated by `200` or `204` is up to the configuration and implementation of the storage backend.


### [POST] https://root/info/in?

🚧 TODO: Push API


## Query API
🚧 TODO: Query API

## Management API
🚧 TODO: Management API

## Installation
🚧 TODO: Installation
* Prerequisites
* Authorization Module

## Development and Test
🚧 TODO: Development and Test
