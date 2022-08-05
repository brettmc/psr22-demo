<?php

namespace InstrumentedLibrary;

use Psr\Tracing\TracerAwareInterface;
use Psr\Tracing\TracerAwareTrait;

class Thing implements TracerAwareInterface
{
    use TracerAwareTrait;

    public function doSomething(): void
    {
        $span = $this->tracer->startSpan('something');
        $span->setAttribute('foo', 'bar');
        $span->startAndActivate();
        $this->doSomethingElse();
        $span->finish();
    }

    private function doSomethingElse(): void
    {
        $span = $this->tracer->startSpan('something.else');
        $span->start();
        $span->finish();
    }
}