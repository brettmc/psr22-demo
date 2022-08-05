# PSR-22 demo
## Overview
A proof-of-concept implementation of PSR-22 (Tracing), including:
* an OpenTelemetry adapter
* an example class which has been instrumented via PSR-22 interfaces. It should emit 2 spans, one parented to the other.

## Usage
`make update`, `make run`, browse to http://localhost:9411/zipkin/
