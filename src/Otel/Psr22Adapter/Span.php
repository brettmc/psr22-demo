<?php

namespace Otel\Psr22Adapter;

use OpenTelemetry\API\Trace\Propagation\TraceContextPropagator;
use OpenTelemetry\API\Trace\SpanBuilderInterface;
use OpenTelemetry\API\Trace\SpanInterface as OtelSpanInterface;
use OpenTelemetry\API\Trace\StatusCode;
use OpenTelemetry\API\Trace\TracerInterface;
use OpenTelemetry\Context\Context;
use OpenTelemetry\Context\ContextKeyInterface;
use OpenTelemetry\Context\ScopeInterface;
use Psr\Tracing\SpanInterface;
use Stringable;
use Throwable;

class Span implements SpanInterface
{
    private SpanBuilderInterface $builder;
    private TracerInterface $tracer;
    private array $children = [];
    private ?SpanInterface $parent = null;
    private ?OtelSpanInterface $span = null;
    private ?ScopeInterface $scope = null;
    private ?ScopeInterface $myScope = null;
    private array $attributes = [];

    private array $statusMap = [
        SpanInterface::STATUS_UNSET => StatusCode::STATUS_UNSET,
        SpanInterface::STATUS_ERROR => StatusCode::STATUS_ERROR,
        SpanInterface::STATUS_OK    => StatusCode::STATUS_OK,
    ];

    /**
     * @internal
     */
    function __construct(SpanBuilderInterface $builder, TracerInterface $tracer, ?SpanInterface $parent = null)
    {
        $this->builder = $builder;
        $this->tracer = $tracer;
        $this->parent = $parent;
    }

    /**
     * @inheritDoc
     */
    public function setAttribute(string $key, string|int|float|bool|Stringable|null $value): SpanInterface
    {
        if (isset($this->span)) {
            $this->span->setAttribute($key, $value);
        } else {
            $this->builder->setAttribute($key, $value);
        }
        $this->attributes[$key] = $value;
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function setAttributes(iterable $attributes): SpanInterface
    {
        foreach ($attributes as $key => $value) {
            $this->setAttribute($key, $value);
        }
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function activate(): SpanInterface
    {
        !isset($this->span) && $this->start();
        $ctx = Context::getCurrent()->with(self::contextKey(), $this);
        if ($this->parent === null) {
            $ctx = $ctx->with(self::rootSpanKey(), $this);
        }
        $this->myScope = $ctx->activate();
        $this->scope = $this->span->activate();

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function finish(): void
    {
        $this->span?->end();
        $this->scope?->detach();
        $this->myScope?->detach();
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
        $this->start();
        $scope = $this->span->activate();
        try {
            TraceContextPropagator::getInstance()->inject($carrier);
        } finally {
            $scope->detach();
        }

        return $carrier;
    }

    public function getAttribute(string $key): null|string|int|float|bool|Stringable
    {
        return $this->attributes[$key] ?? null;
    }

    public function getAttributes(): iterable
    {
        return $this->attributes;
    }

    public function addException(Throwable $t): SpanInterface
    {
        $this->span->recordException($t);
        return $this;
    }

    public function createChild(string $spanName): SpanInterface
    {
        $this->start();
        $scope = $this->span->activate();
        try {
            $builder = $this->tracer->spanBuilder($spanName);
        } finally {
            $scope->detach();
        }
        $child = new Span($builder, $this->tracer, $this);
        $this->children[] = $child;

        return $child;
    }

    public function getParent(): ?SpanInterface //todo allow for null
    {
        return $this->parent;
    }

    public function getChildren(): array
    {
        return $this->children;
    }

    public static function contextKey(): ContextKeyInterface
    {
        static $key;
        $key ??= Context::createKey(self::class);

        return $key;
    }

    /**
     * @internal
     */
    public static function rootSpanKey(): ContextKeyInterface
    {
        static $rootKey;
        $rootKey ??= Context::createKey(sprintf('%s__ROOT', self::class));

        return $rootKey;
    }
}