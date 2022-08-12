<?php

namespace Psr22Adapter\Otel;

use OpenTelemetry\API\Trace\AbstractSpan;
use OpenTelemetry\API\Trace\TracerInterface as OtelTracerInterface;
use OpenTelemetry\API\Trace\TracerProviderInterface;
use Psr\Tracing\SpanInterface;
use Psr\Tracing\TracerInterface;

class Tracer implements TracerInterface
{
    private TracerProviderInterface $provider;
    private OtelTracerInterface $tracer;

    public function __construct(TracerProviderInterface $provider)
    {
        $this->provider = $provider;
        $this->tracer = $this->provider->getTracer('psr22.demo');
    }

    /**
     * @inheritDoc
     */
    public function createSpan(string $spanName): SpanInterface
    {
        return new Span($this->tracer->spanBuilder($spanName));
    }

    public function getCurrentTraceId(): string
    {
        return AbstractSpan::getCurrent()->getContext()->getTraceId();
    }
}