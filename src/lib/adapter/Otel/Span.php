<?php

namespace Psr22Adapter\Otel;

use OpenTelemetry\API\Trace\Propagation\TraceContextPropagator;
use OpenTelemetry\API\Trace\SpanBuilderInterface;
use OpenTelemetry\API\Trace\SpanInterface as OtelSpanInterface;
use OpenTelemetry\Context\ScopeInterface;
use Psr\Tracing\SpanInterface;

class Span implements \Psr\Tracing\SpanInterface
{
    private ?OtelSpanInterface $otelSpan = null;
    private SpanBuilderInterface $spanBuilder;
    private ?ScopeInterface $scope = null;

    function __construct(SpanBuilderInterface $spanBuilder)
    {
        $this->spanBuilder = $spanBuilder;
    }

    /**
     * @inheritDoc
     */
    public function setAttribute(string $key, mixed $value): SpanInterface
    {
        $this->spanBuilder->setAttribute($key, $value);
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function setAttributes(iterable $attributes): SpanInterface
    {
        $this->spanBuilder->setAttributes($attributes);
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function startAndActivate(): SpanInterface
    {
        $this->otelSpan = $this->spanBuilder->startSpan();
        $this->scope = $this->otelSpan->activate();
        return $this;
    }

    public function start(): SpanInterface
    {
        $this->otelSpan = $this->spanBuilder->startSpan();
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function finish(): void
    {
        $this->otelSpan?->end();
        $this->scope?->detach();
    }

    /**
     * @inheritDoc
     * @todo should return an array of headers
     */
    public function toTraceparent(): ?string
    {
        $carrier = [];
        TraceContextPropagator::getInstance()->inject($carrier);
        return $carrier['traceparent'] ?? null;
    }
}