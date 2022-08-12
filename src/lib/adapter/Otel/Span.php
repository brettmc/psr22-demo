<?php

namespace Psr22Adapter\Otel;

use OpenTelemetry\API\Trace\Propagation\TraceContextPropagator;
use OpenTelemetry\API\Trace\SpanBuilderInterface;
use OpenTelemetry\API\Trace\SpanInterface as OtelSpanInterface;
use OpenTelemetry\API\Trace\StatusCode;
use OpenTelemetry\Context\ScopeInterface;
use Psr\Tracing\SpanInterface;
use Psr\Tracing\TracerInterface;
use Stringable;

class Span implements SpanInterface
{
    private SpanBuilderInterface $builder;
    private ?OtelSpanInterface $span = null;
    private ?ScopeInterface $scope = null;

    private array $statusMap = [
        SpanInterface::STATUS_UNSET => StatusCode::STATUS_UNSET,
        SpanInterface::STATUS_ERROR => StatusCode::STATUS_ERROR,
        SpanInterface::STATUS_OK    => StatusCode::STATUS_OK,
    ];

    function __construct(SpanBuilderInterface $builder)
    {
        $this->builder = $builder;
    }

    /**
     * @inheritDoc
     */
    public function setAttribute(string $key, string|int|float|bool|Stringable $value): SpanInterface
    {
        return $this->setAttributes([$key => $value]);
    }

    /**
     * @inheritDoc
     */
    public function setAttributes(iterable $attributes): SpanInterface
    {
        if (isset($this->span)) {
            $this->span->setAttributes($attributes);
        } else {
            $this->builder->setAttributes($attributes);
        }
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function activate(): SpanInterface
    {
        $this->scope = $this->span->activate();
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function finish(): void
    {
        $this->span->end();
        $this->scope?->detach();
    }

    public function start(): SpanInterface
    {
        if (!isset($this->span)) {
            $this->span = $this->builder->startSpan();
        }
        return $this;
    }

    public function setStatus(int $status, ?string $description): SpanInterface
    {
        if (isset($this->span) && array_key_exists($status, $this->statusMap)) {
            $this->span->setStatus($this->statusMap[$status], $description);
        }
        return $this;
    }

    public function toTraceContextHeaders(): array
    {
        $carrier = [];
        TraceContextPropagator::getInstance()->inject($carrier);
        return $carrier;
    }
}