<?php

namespace Otel\Psr22Adapter;

use OpenTelemetry\API\Trace\Span as OtelSpan;
use OpenTelemetry\API\Trace\TracerInterface as OtelTracerInterface;
use OpenTelemetry\API\Trace\TracerProviderInterface;
use OpenTelemetry\Context\Context;
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
        return new Span($this->tracer->spanBuilder($spanName), $this->tracer);
    }

    public function getCurrentTraceId(): string
    {
        return OtelSpan::getCurrent()->getContext()->getTraceId();
    }

    public function getRootSpan(): ?SpanInterface
    {
        return Context::getCurrent()->get(Span::rootSpanKey());
    }

    public function getCurrentSpan(): ?SpanInterface
    {
        return Context::getCurrent()->get(Span::contextKey());
    }
}