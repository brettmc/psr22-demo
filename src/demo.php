<?php

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\HttpFactory;
use OpenTelemetry\Contrib\Zipkin\Exporter;
use OpenTelemetry\SDK\Trace\SpanProcessor\SimpleSpanProcessor;
use OpenTelemetry\SDK\Trace\TracerProvider;

require_once 'vendor/autoload.php';

$exporter = new Exporter(
    'psr-22-example',
    'http://zipkin:9411/api/v2/spans',
    new Client(),
    new HttpFactory(),
    new HttpFactory()
);

$provider = new TracerProvider(
    new SimpleSpanProcessor(
        $exporter
    )
);
$otelTracer = $provider->getTracer('foo');
$psrTracer = new \Psr22Adapter\Otel\Tracer($provider);
echo 'Initial Trace Id: ' . $psrTracer->getCurrentTraceId() . PHP_EOL;
$span = $otelTracer->spanBuilder('root')->startSpan();
$scope = $span->activate();
try {
    echo 'Current Trace Id: ' . $psrTracer->getCurrentTraceId() . PHP_EOL;
    $thing = new \InstrumentedLibrary\Thing();
    $thing->setTracer($psrTracer);
    $thing->doSomething();
} finally {
    $span->end();
    $scope->detach();
}