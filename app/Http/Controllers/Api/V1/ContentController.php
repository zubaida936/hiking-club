<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Contact\Actions\SendContactMessage;
use App\Domain\Content\HomepageHero;
use App\Domain\Events\Enums\DifficultyLevel;
use App\Domain\Loyalty\Leaderboard;
use App\Domain\Newsletter\Actions\Subscribe;
use App\Domain\Registrations\Enums\Attendance;
use App\Http\Controllers\Controller;
use App\Http\Requests\Site\ContactRequest;
use App\Http\Requests\Site\SubscribeRequest;
use App\Http\Resources\Api\V1\EventResource;
use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\Hiker;
use App\Models\ProductCategory;
use App\Models\TeamMember;
use App\Support\SocialLinks;
use App\Support\WhatsApp;
use Illuminate\Http\JsonResponse;

/**
 * The website's content pages for the app: home, about, equipment guide, contact and the newsletter.
 * Texts come from the same places as the website (CMS, config/site.php, config/equipment.php).
 */
class ContentController extends Controller
{
    /**
     * Home.
     *
     * Homepage slides (CMS → Homepage slides, or the placeholders while none is shown), the next hike,
     * and the top 10 hikers. Slide `url`s are website links: the app can open the matching screen.
     *
     * @unauthenticated
     */
    public function home(HomepageHero $hero, Leaderboard $leaderboard): JsonResponse
    {
        $next = Event::nextUpcoming()?->loadSeatCount();

        // A loop with casts (not the hero's plain arrays as they are), so the API docs type every slide field.
        $slides = [];
        foreach ($hero->slides() as $slide) {
            $slides[] = [
                'eyebrow' => $slide['eyebrow'] === null ? null : (string) $slide['eyebrow'],
                'heading' => (string) $slide['heading'],
                'text' => $slide['text'] === null ? null : (string) $slide['text'],
                'cta' => $slide['cta'] === null ? null : (string) $slide['cta'],
                'url' => $slide['url'] === null ? null : (string) $slide['url'],
                'image' => $slide['image'] === null ? null : (string) $slide['image'],
            ];
        }

        return response()->json(['data' => [
            'slides' => $slides,
            'next_event' => $next ? new EventResource($next) : null,
            'top_hikers' => $leaderboard->top(10)->values()->map(fn (Hiker $hiker, int $index) => [
                'position' => $index + 1,
                'name' => $hiker->publicName(),
                'rank' => ['key' => $hiker->tier()->value, 'label' => $hiker->tier()->label()],
                'points' => $hiker->totalPoints(),
            ])->all(),
        ]]);
    }

    /**
     * About.
     *
     * The club's story, what it offers, testimonials and team (CMS → Team), and club numbers.
     *
     * @unauthenticated
     *
     * @return array{data: array<string, mixed>}
     */
    public function about(): array
    {
        $about = (array) config('site.about_page');

        return ['data' => [
            'intro' => (string) config('site.about'),
            'founded' => (int) $about['founded'],
            'story' => array_values((array) $about['story']),
            'offers' => array_map(fn (array $offer) => ['icon' => $offer[0], 'title' => $offer[1], 'text' => $offer[2]], (array) $about['offers']),
            'testimonials' => array_map(fn (array $quote) => ['name' => $quote[0], 'text' => $quote[1]], (array) $about['testimonials']),
            'google_reviews_url' => $about['google_reviews_url'] ?: null,
            'team' => TeamMember::shown()->get()->map(fn (TeamMember $member) => [
                'name' => $member->name,
                'role' => $member->role,
                'bio' => $member->bio,
                'photo_url' => $member->photoUrl(),
            ])->values()->all(),
            'stats' => [
                'hikes' => Event::published()->past()->count(),
                'hikers' => EventRegistration::where('attendance', Attendance::Attended)->distinct()->count('hiker_id'),
            ],
        ]];
    }

    /**
     * Equipment guide.
     *
     * Checklists per adventure (hiking, snowshoeing, rafting, camping). An item with `from_level` is needed
     * from that difficulty level up (null: always). `shop_category` is set only when the shop has products
     * in that category (GET /shop/products?category=...).
     *
     * @unauthenticated
     */
    public function equipment(): JsonResponse
    {
        $shopCategories = ProductCategory::query()->whereHas('products', fn ($q) => $q->where('is_active', true))->pluck('slug')->all();

        // Loops with casts (not array_map / collect()->map over config arrays) so the API docs type every field.
        $levels = [];
        foreach (DifficultyLevel::cases() as $level) {
            $levels[] = ['level' => (int) $level->value, 'label' => $level->label()];
        }

        $adventures = [];
        foreach ((array) config('equipment.adventures') as $key => $adventure) {
            $items = [];
            foreach ((array) $adventure['items'] as $item) {
                $items[] = [
                    'name' => (string) $item[0],
                    'tip' => (string) $item[1],
                    // Needed from this difficulty level up; null: always.
                    'from_level' => $item[2] === null ? null : (int) $item[2],
                    // Set only when the shop has products in that category.
                    'shop_category' => in_array($item[3], $shopCategories, true) ? (string) $item[3] : null,
                ];
            }
            $adventures[] = [
                'key' => (string) $key,
                'label' => (string) $adventure['label'],
                'icon' => (string) $adventure['icon'],
                'intro' => (string) $adventure['intro'],
                'items' => $items,
            ];
        }

        return response()->json(['data' => ['levels' => $levels, 'adventures' => $adventures]]);
    }

    /**
     * Contact details.
     *
     * @unauthenticated
     *
     * @return array{data: array<string, mixed>}
     */
    public function contact(): array
    {
        $contact = (array) config('site.contact');

        // A loop, not collect()->map() over a plain array (that can untype other endpoints in the API docs).
        $social = [];
        foreach (SocialLinks::all() as $network => $link) {
            $social[] = ['network' => (string) $network, 'label' => (string) $link[0], 'url' => (string) $link[1]];
        }

        return ['data' => [
            'address' => $contact['address'],
            'phone' => $contact['phone'],
            'email' => $contact['email'],
            // null when the club has no WhatsApp number set.
            'whatsapp_url' => WhatsApp::link((string) $contact['whatsapp_message']),
            'maps_url' => $contact['maps_url'] ?: 'https://www.google.com/maps/search/?api=1&query='.rawurlencode((string) $contact['address']),
            'social' => $social,
        ]];
    }

    /**
     * Send a contact message.
     *
     * Same fields and rules as the website form; the message is emailed to the club.
     *
     * @unauthenticated
     */
    public function sendContact(ContactRequest $request, SendContactMessage $action): JsonResponse
    {
        $action->handle($request->toData());

        return response()->json(['message' => 'Thank you! Your message is on its way. We usually answer within a day.'], 202);
    }

    /**
     * Subscribe to new hike alerts.
     *
     * "Next Destination Alerts" with an email (confirmed by a link in the email) or a phone number.
     * The answer is the same whether or not the visitor was already subscribed.
     *
     * @unauthenticated
     */
    public function subscribe(SubscribeRequest $request, Subscribe $action): JsonResponse
    {
        $data = $request->toData();
        $action->handle($data);

        return response()->json(['message' => $data->email !== null
            ? 'Almost there! Check your inbox and click the link to confirm your email.'
            : "Thanks! You're on the list for Next Destination Alerts."], 202);
    }
}
