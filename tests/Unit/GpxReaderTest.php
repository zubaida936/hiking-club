<?php

use App\Domain\Events\Gpx\GpxReader;

it('reads track points with or without the GPX namespace', function (string $xml) {
    expect((new GpxReader)->points($xml))->toBe([[34.1, 35.1], [34.2, 35.2]]);
})->with([
    'namespaced' => '<gpx xmlns="http://www.topografix.com/GPX/1/1"><trk><trkseg><trkpt lat="34.1" lon="35.1"/><trkpt lat="34.2" lon="35.2"/></trkseg></trk></gpx>',
    'plain' => '<gpx><trk><trkseg><trkpt lat="34.1" lon="35.1"/><trkpt lat="34.2" lon="35.2"/></trkseg></trk></gpx>',
    'route only' => '<gpx><rte><rtept lat="34.1" lon="35.1"/><rtept lat="34.2" lon="35.2"/></rte></gpx>',
]);

it('returns nothing for files it cannot use', function (string $xml) {
    expect((new GpxReader)->points($xml))->toBe([]);
})->with([
    'not xml' => 'hello',
    'no points' => '<gpx><trk/></gpx>',
    'out of range' => '<gpx><trk><trkseg><trkpt lat="134" lon="35"/></trkseg></trk></gpx>',
    'doctype' => '<?xml version="1.0"?><!DOCTYPE gpx [<!ENTITY x SYSTEM "file:///etc/passwd">]><gpx><trk><trkseg><trkpt lat="34" lon="35"><name>&x;</name></trkpt></trkseg></trk></gpx>',
]);

it('thins long tracks but keeps the start and finish', function () {
    $xml = '<gpx><trk><trkseg>'.implode('', array_map(
        fn (int $i) => sprintf('<trkpt lat="%.5f" lon="35"/>', 33 + $i / 10000),
        range(0, 2999),
    )).'</trkseg></trk></gpx>';

    $points = (new GpxReader)->points($xml);

    expect(count($points))->toBeLessThanOrEqual(GpxReader::MAX_POINTS)
        ->and($points[0])->toBe([33.0, 35.0])
        ->and(end($points))->toBe([33.2999, 35.0]);
});
