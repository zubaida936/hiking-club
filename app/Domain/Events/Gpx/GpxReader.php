<?php

namespace App\Domain\Events\Gpx;

use SimpleXMLElement;

/**
 * Reads the route out of a GPX file (track points, or route points when the
 * file has no track) and thins it out so the public map stays light.
 */
final class GpxReader
{
    public const MAX_POINTS = 500;

    /**
     * @return list<array{float, float}> [lat, lng] pairs; empty when the file isn't a usable GPX
     */
    public function points(string $xml): array
    {
        // No DTDs: entities can't be expanded or fetched (XXE / billion laughs).
        if (stripos($xml, '<!DOCTYPE') !== false) {
            return [];
        }

        $previous = libxml_use_internal_errors(true);
        $doc = simplexml_load_string($xml, SimpleXMLElement::class, LIBXML_NONET);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        if ($doc === false) {
            return [];
        }

        $points = [];

        // local-name() so files with or without the GPX 1.0/1.1 namespace both work.
        foreach (['trkpt', 'rtept'] as $tag) {
            foreach ($doc->xpath("//*[local-name()='{$tag}']") ?: [] as $node) {
                $lat = (float) $node['lat'];
                $lng = (float) $node['lon'];

                if (abs($lat) <= 90 && abs($lng) <= 180 && ($lat !== 0.0 || $lng !== 0.0)) {
                    $points[] = [round($lat, 5), round($lng, 5)];
                }
            }

            if ($points !== []) {
                break;
            }
        }

        return $this->thin($points);
    }

    /**
     * Keep every n-th point (plus the last one) so at most MAX_POINTS remain.
     *
     * @param  list<array{float, float}>  $points
     * @return list<array{float, float}>
     */
    private function thin(array $points): array
    {
        $count = count($points);

        if ($count <= self::MAX_POINTS) {
            return $points;
        }

        $step = (int) ceil($count / (self::MAX_POINTS - 1));
        $thinned = [];

        for ($i = 0; $i < $count - 1; $i += $step) {
            $thinned[] = $points[$i];
        }

        $thinned[] = $points[$count - 1];

        return $thinned;
    }
}
