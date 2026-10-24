<?php

namespace Database\Seeders;

use App\Models\Article;
use App\Models\HeroSlide;
use App\Models\TeamMember;
use Illuminate\Database\Seeder;

/**
 * Sample CMS content (no photos) for local development: the placeholder hero
 * slides as editable slides, a small team and two guide articles. Each part is
 * skipped when it already has rows. Not run in production (see DatabaseSeeder).
 */
class DemoContentSeeder extends Seeder
{
    public function run(): void
    {
        if (! HeroSlide::exists()) {
            foreach (array_values((array) config('site.hero')) as $i => $slide) {
                HeroSlide::create([
                    'eyebrow' => $slide['season'],
                    'heading' => $slide['heading'],
                    'text' => $slide['text'],
                    'cta_label' => $slide['cta'],
                    'cta_url' => '/events',
                    'position' => $i + 1,
                ]);
            }
        }

        if (! TeamMember::exists()) {
            foreach ([
                ['Rami Khoury', 'Founder & lead guide', 'Has walked every section of the Lebanon Mountain Trail.'],
                ['Maya Haddad', 'Guide & first aider', 'Wilderness first responder; leads the Foundation hikes.'],
                ['Karim Saad', 'Logistics & transport', 'Gets everyone to the trailhead on time.'],
            ] as $i => [$name, $role, $bio]) {
                TeamMember::create(['name' => $name, 'role' => $role, 'bio' => $bio, 'position' => $i + 1]);
            }
        }

        if (! Article::exists()) {
            Article::create([
                'title' => 'How much water to carry on a day hike',
                'slug' => 'how-much-water-to-carry-on-a-day-hike',
                'excerpt' => 'A simple rule of thumb, adjusted for heat, climbing and the season.',
                'body' => "Plan on **half a litre per hour** of walking, more in summer or on steep climbs.\n\n## Before you leave\n\n- Drink a full glass before the hike\n- Check if the route has a spring\n\n## On the trail\n\nSip often instead of drinking a lot at once.",
                'author_name' => 'Maya Haddad',
                'published_at' => now()->subDays(3),
            ]);
            Article::create([
                'title' => 'Elevation vs distance: reading a hike',
                'slug' => 'elevation-vs-distance-reading-a-hike',
                'body' => "A 10 km walk with 1,000 m of climbing is much harder than 15 km on the flat.\n\nOur difficulty levels combine both.",
                'author_name' => 'Rami Khoury',
                'published_at' => null,
            ]);
        }
    }
}
