--TEST--
Test that can get children from parent span and parent from child span
--FILE--
<?php
use OpenTelemetry\SDK\Trace\SpanProcessor\SimpleSpanProcessor;
use OpenTelemetry\SDK\Trace\SpanProcessor\NoopSpanProcessor;
use OpenTelemetry\SDK\Trace\TracerProvider;

require_once 'vendor/autoload.php';

$provider = new TracerProvider(
    new NoopSpanProcessor()
);
$tracer = new \Otel\Psr22Adapter\Tracer($provider);

$root = $tracer->createSpan('root')->setAttribute('role', 'root')->start();
$child = $root->createChild('child')->setAttribute('role', 'child')->start();
$grandson = $child->createChild('grandson')->setAttribute('role', 'grandchild')->setAttribute('gender', 'male')->start();
$granddaughter = $child->createChild('granddaughter')->setAttribute('role', 'grandchild')->setAttribute('gender', 'female')->start();
echo "children of child:\n";
foreach ($child->getChildren() as $span) {
    var_dump($span->getAttributes());
}
echo "parent of grandson:\n";
var_dump($grandson->getParent()->getAttributes());
echo "parent of granddaughter:\n";
var_dump($granddaughter->getParent()->getAttributes());
echo "parent of child:\n";
var_dump($child->getParent()->getAttributes());
echo "children of root:\n";
foreach ($root->getChildren() as $span) {
    var_dump($span->getAttributes());
}
$granddaughter->finish();
$grandson->finish();
$child->finish();
$root->finish();
?>
--EXPECTF--
children of child:
array(2) {
  ["role"]=>
  string(10) "grandchild"
  ["gender"]=>
  string(4) "male"
}
array(2) {
  ["role"]=>
  string(10) "grandchild"
  ["gender"]=>
  string(6) "female"
}
parent of grandson:
array(1) {
  ["role"]=>
  string(5) "child"
}
parent of granddaughter:
array(1) {
  ["role"]=>
  string(5) "child"
}
parent of child:
array(1) {
  ["role"]=>
  string(4) "root"
}
children of root:
array(1) {
  ["role"]=>
  string(5) "child"
}
