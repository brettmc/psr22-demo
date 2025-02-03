--TEST--
Root span
--FILE--
<?php
use OpenTelemetry\SDK\Trace\SpanProcessor\NoopSpanProcessor;
use OpenTelemetry\SDK\Trace\TracerProvider;

require_once 'vendor/autoload.php';

$provider = new TracerProvider(
    new NoopSpanProcessor()
);
$tracer = new \Otel\Psr22Adapter\Tracer($provider);

var_dump($tracer->getRootSpan()?->toTraceContextHeaders());
$span = $tracer->createSpan('root')->activate();
var_dump($tracer->getRootSpan()?->toTraceContextHeaders());
$span->finish();
var_dump($tracer->getRootSpan()?->toTraceContextHeaders());

?>
--EXPECTF--
NULL
array(1) {
  ["traceparent"]=>
  string(%d) "00-%s-%s-01"
}
NULL
