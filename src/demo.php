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

$tracer = new \Psr22Adapter\Otel\Tracer($provider);

$thing = new \InstrumentedLibrary\Thing();
$thing->setTracer($tracer);
$thing->doSomething();
