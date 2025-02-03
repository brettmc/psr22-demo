<?php

use OpenTelemetry\Contrib\Zipkin\Exporter;
use OpenTelemetry\SDK\Common\Export\Http\PsrTransportFactory;
use OpenTelemetry\SDK\Trace\SpanProcessor\SimpleSpanProcessor;
use OpenTelemetry\SDK\Trace\TracerProvider;
use Otel\Psr22Adapter\Tracer;

require_once dirname(__DIR__) . '/vendor/autoload.php';

$transport = (new PsrTransportFactory())->create('http://zipkin:9411/api/v2/spans', 'application/json');

$exporter = new Exporter($transport);

$provider = new TracerProvider(
    new SimpleSpanProcessor(
        $exporter
    )
);
$tracer = new Tracer($provider);
$root = $tracer->createSpan('root')->activate();
echo 'Current Trace Id: ' . $tracer->getCurrentTraceId() . PHP_EOL;
$thing = new InstrumentedLibrary\Thing();
$thing->setTracer($tracer);
$thing->doSomething();

$child = $root->createChild('child')->start();
$grandchild = $child->createChild('grandchild')->start();
$grandchild->finish();
$child->finish();

$root->finish();