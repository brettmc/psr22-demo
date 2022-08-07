<?php

namespace Psr22Adapter\Otel;

use OpenTelemetry\API\Trace\Propagation\TraceContextPropagator;
use OpenTelemetry\API\Trace\SpanBuilderInterface;
use OpenTelemetry\API\Trace\SpanInterface as OtelSpanInterface;
use OpenTelemetry\Context\ScopeInterface;
use Psr\Tracing\SpanInterface;

class Span implements \Psr\Tracing\SpanInterface
{
    private OtelSpanInterface $otelSpan;
    private ?ScopeInterface $scope = null;

    function __construct(OtelSpanInterface $span)
    {
        $this->otelSpan = $span;
    }

    /**
     * @inheritDoc
     */
    public function setAttribute(string $key, mixed $value): SpanInterface
    {
        $this->otelSpan->setAttribute($key, $value);
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function setAttributes(iterable $attributes): SpanInterface
    {
        $this->otelSpan->setAttributes($attributes);
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function activate(): SpanInterface
    {
        $this->scope = $this->otelSpan->activate();
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function finish(): void
    {
        $this->otelSpan->end();
        $this->scope?->detach();
    }

    /**
     * @inheritDoc
     * @todo should return an array of headers, and have a more generic name...
     */
    public function toTraceparent(): ?string
    {
        $carrier = [];
        TraceContextPropagator::getInstance()->inject($carrier);
        return $carrier['traceparent'] ?? null;
    }
}