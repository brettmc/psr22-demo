<?php

namespace Unit;

use OpenTelemetry\SDK\Trace\ImmutableSpan;
use OpenTelemetry\SDK\Trace\SpanExporter\InMemoryExporter;
use OpenTelemetry\SDK\Trace\SpanProcessor\SimpleSpanProcessor;
use OpenTelemetry\SDK\Trace\TracerProvider;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Tracing\SpanInterface;
use Psr\Tracing\TracerInterface;
use Otel\Psr22Adapter\Span;
use Otel\Psr22Adapter\Tracer;

#[CoversClass(Tracer::class)]
#[CoversClass(Span::class)]
class TracerTest extends TestCase
{
    private const EMPTY_TRACE_ID = '00000000000000000000000000000000';
    private InMemoryExporter $exporter;
    private TracerInterface $tracer;
    public function setUp(): void
    {
        $this->exporter = new InMemoryExporter();
        $this->tracer = new Tracer(TracerProvider::builder()->addSpanProcessor(new SimpleSpanProcessor($this->exporter))->build());
        parent::setUp();
    }

    public function test_initial_state(): void
    {
        $this->assertNull($this->tracer->getRootSpan());
        $this->assertNull($this->tracer->getCurrentSpan());
        $this->assertSame(self::EMPTY_TRACE_ID, $this->tracer->getCurrentTraceId());
    }

    public function test_active_span(): void
    {
        $root = $this->tracer->createSpan('root')->activate();
        $this->assertSame($root, $this->tracer->getCurrentSpan());
        $this->assertSame($root, $this->tracer->getRootSpan());
        $this->assertCount(0, $this->exporter->getSpans());
        $root->finish();
        $this->assertCount(1, $this->exporter->getSpans());
        $this->assertNull($this->tracer->getCurrentSpan());
        $this->assertNull($this->tracer->getRootSpan());
    }

    public function test_parent_child(): void
    {
        $parent = $this->tracer->createSpan('parent')->start();
        $child = $parent->createChild('child')->start();
        $child->finish();
        $parent->finish();
        $this->assertCount(2, $this->exporter->getSpans());

        $this->assertSame($parent, $child->getParent());
        $this->assertSame([$child], $parent->getChildren());
    }

    #[DataProvider('status_provider')]
    public function test_set_status(int $status, string $expected): void
    {
        $span = $this->tracer->createSpan('span')->start();
        $span->setStatus($status, 'foo')->start()->finish();
        /* @var ImmutableSpan $span */
        $span = $this->exporter->getSpans()[0];
        $this->assertSame($expected, $span->getStatus()->getCode());
    }

    public static function status_provider(): array
    {
        return [
            [SpanInterface::STATUS_UNSET, 'Unset'],
            [SpanInterface::STATUS_OK, 'Ok'],
            [SpanInterface::STATUS_ERROR, 'Error'],
        ];
    }

    public function test_attributes(): void
    {
        $expected = [
            'one' => 1,
            'two' => 2,
            'three' => 3,
            'four' => 4,
        ];
        $span = $this->tracer->createSpan('span');
        $span->setAttribute('one', 1);
        $span->setAttributes(['two' => 2, 'three' => 3]);
        $span->start();
        $span->setAttribute('four', 4);
        $this->assertSame($expected, $span->getAttributes());
        $span->finish();
        $this->assertSame($expected, $this->exporter->getSpans()[0]->getAttributes()->toArray());
    }

    public function test_exception(): void
    {
        $span = $this->tracer->createSpan('span');
        $e = new \RuntimeException('kaboom');
        $span->start()->addException($e);
        $span->finish();
        /* @var ImmutableSpan $span */
        $span = $this->exporter->getSpans()[0];
        $this->assertCount(1, $span->getEvents());
        $event = $span->getEvents()[0];
        $this->assertSame('exception', $event->getName());
        $this->assertSame('RuntimeException', $event->getAttributes()->get('exception.type'));
        $this->assertSame('kaboom', $event->getAttributes()->get('exception.message'));
        $this->assertNotNull($event->getAttributes()->get('exception.stacktrace'));
    }

    public function test_get_current_trace_id(): void
    {
        $span = $this->tracer->createSpan('span');
        $this->assertSame(self::EMPTY_TRACE_ID, $this->tracer->getCurrentTraceId());
        $span->activate();
        $this->assertNotEquals(self::EMPTY_TRACE_ID, $this->tracer->getCurrentTraceId());
        $span->finish();
        $this->assertSame(self::EMPTY_TRACE_ID, $this->tracer->getCurrentTraceId());
    }

    public function test_create_child_when_parent_not_started(): void
    {
        $span = $this->tracer->createSpan('span');
        $child = $span->createChild('child');
        $child->finish();
        $span->finish();
        $this->assertCount(1, $this->exporter->getSpans(), 'create child forced span start');
    }
}