# Instrumentation

This a composer module for recording metrics

## Installation

`composer require stickee/instrumentation`

## Configuration

### Basic Usage

To use the basic features, you must create an instrumentation database and record events to it.

```
//$database = new InfluxDb('https+influxdb://username:password@example.com:8086/database_name');
$database = new InfluxDb('https+influxdb://admin:BiP9GYS9j2@influxdb.stickeedev.com:443/test');
$instrument = new \Stickee\Instrumentation\Instrument();
$instrument->add($database);

Instrument::setInstrument($instrument);

Instrument::event('test');
Instrument::event('test2');
