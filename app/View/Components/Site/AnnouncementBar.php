<?php

namespace App\View\Components\Site;

use App\Models\Event;
use Illuminate\View\Component;
use Illuminate\View\View;

/**
 * Top bar announcing the next upcoming event on every public page.
 */
class AnnouncementBar extends Component
{
    public readonly ?Event $event;

    public function __construct()
    {
        $this->event = Event::nextUpcoming();
    }

    public function shouldRender(): bool
    {
        return $this->event !== null;
    }

    public function when(): string
    {
        $date = $this->event?->starts_on;

        return match (true) {
            $date === null => '',
            $date->isToday() => 'Today',
            $date->isTomorrow() => 'Tomorrow',
            default => $date->format('D j M'),
        };
    }

    /**
     * The event's own page.
     */
    public function url(): string
    {
        return route('events.show', $this->event);
    }

    public function render(): View
    {
        return view('components.site.announcement-bar');
    }
}
