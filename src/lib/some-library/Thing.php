<?php

namespace InstrumentedLibrary;

use Psr\Tracing\TracerAwareInterface;
use Psr\Tracing\TracerAwareTrait;

class Thing implements TracerAwareInterface
{
    use TracerAwareTrait;

    public function doSomething(): void
    {
        $span = $this->tracer
            ->createSpan('something')
            ->setAttribute('foo', 'bar')
            ->start()
            ->activate();
        try {
            echo 'Trace Context Headers: '. json_encode($span->toTraceContextHeaders()) . PHP_EOL;
            $this->doSomethingElse();
        } finally {
            $span->finish();
        }
    }

    private function doSomethingElse(): void
    {
        $span = $this->tracer->createSpan('something.else')->start();
        //do some work
        $span->finish();
    }
}