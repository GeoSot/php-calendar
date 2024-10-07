<?php

declare(strict_types=1);

namespace benhall14\phpCalendar;

use BadMethodCallException;
use DateTimeInterface;
use Carbon\Carbon;
use Carbon\CarbonInterval;

/**
 * Simple PHP Calendar Class.
 *
 * @copyright  Copyright (c) Benjamin Hall
 * @license https://github.com/benhall14/php-calendar
 *
 * @version 1.2
 *
 * @author Benjamin Hall <https://conobe.co.uk>
 *
 * @method $this hideSundays()
 * @method $this hideMondays()
 * @method $this hideTuesdays()
 * @method $this hideWednesdays()
 * @method $this hideThursdays()
 * @method $this hideFridays()
 * @method $this hideSaturdays()
 */
class Calendar
{
    private string $locale = 'en_US';

    /**
     * Calendar Type.
     */
    private string $type = 'month';

    /**
     * Time Interval used in the week view.
     * Default is set to 30 minutes.
     */
    private int $time_interval = 30;

    /**
     * The Week View Starting Time.
     * Leave at 00:00 for a full 24-hour calendar.
     */
    private string $start_time = '00:00';

    /**
     * The Week View end time.
     * Leave at 00:00 for a full 24-hour calendar.
     */
    private string $end_time = '00:00';

    /**
     * The Day Format.
     */
    private string $day_format = 'initials';

    /**
     * Start day of week. Default = 0 (Sunday).
     */
    private int $starting_day = 0;

    /**
     * Table classes that should be injected into the table header.
     */
    private string $table_classes = '';

    /**
     * Hide all days from the calendar view.
     *
     * @var list<string>
     */
    private array $hiddenDays = [];

    public function setLocale(string $locale): static
    {
        $this->locale = $locale;

        return $this;
    }

    /**
     * @param array<string,mixed> $args
     *
     * @return $this
     */
    public function __call(string $method, array $args): static
    {
        if (str_starts_with($method, 'hide')) {
            $this->hiddenDays[] = rtrim(ltrim($method, 'hide'), 's');

            return $this;
        }

        throw new BadMethodCallException(sprintf('Method "%s" does not exist.', $method));
    }

    /**
     * Sets the day format flag to return initial day names. This is the default behaviour.
     */
    public function useInitialDayNames(): static
    {
        $this->day_format = 'initials';

        return $this;
    }

    /**
     * Sets the day format flag to return full day names instead of initials by default.
     */
    public function useFullDayNames(): static
    {
        $this->day_format = 'full';

        return $this;
    }

    /**
     * Changes the weekly start date to Sunday.
     */
    public function useSundayStartingDate(): static
    {
        $this->starting_day = 0;

        return $this;
    }

    /**
     * Changes the weekly start date to Monday.
     */
    public function useMondayStartingDate(): static
    {
        $this->starting_day = 1;

        return $this;
    }

    /**
     * Returns, or prints, the default stylesheet.
     */
    public function stylesheet(bool $print = true): ?string
    {
        $styles = '<style>.weekly-calendar{min-width:850px;}.calendar{background:#2ca8c2;color:#fff;width:100%;font-family:Oxygen;table-layout:fixed}.calendar.purple{background:#913ccd}.calendar.pink{background:#f15f74}.calendar.orange{background:#f76d3c}.calendar.yellow{background:#f7d842}.calendar.green{background:#98cb4a}.calendar.grey{background:#839098}.calendar.blue{background:#5481e6}.calendar-title th{font-size:22px;font-weight:700;padding:20px;text-align:center;text-transform:uppercase;background:rgba(0,0,0,.05)}.calendar-header th{padding:10px;text-align:center;background:rgba(0,0,0,.1)}.calendar tbody tr td{text-align:center;vertical-align:top;width:14.28%}.calendar tbody tr td.pad{background:rgba(255,255,255,.1)}.calendar tbody tr td.day div:first-child{padding:4px;line-height:17px;height:25px}.calendar tbody tr td.day div:last-child{font-size:10px;padding:4px;min-height:25px}.calendar tbody tr td.today{background:rgba(0,0,0,.25)}.calendar tbody tr td.mask,.calendar tbody tr td.mask-end,.calendar tbody tr td.mask-start{background:#c23b22}.calendar .cal-weekview-time{padding:4px 2px 2px 4px;}.calendar .cal-weekview-time > div{background:rgba(0,0,0,0.03);padding:10px;min-height:50px;}.calendar .cal-weekview-event.mask-start,.calendar .cal-weekview-event.mask,.calendar .cal-weekview-event.mask-end{background:#C23B22;margin-bottom:3px;padding:5px;}.calendar .cal-weekview-time-th{background:rgba(0,0,0,.1);}.calendar .cal-weekview-time-th > div{padding:10px;min-height:50px;}.calendar .event-summary-row{display:block;}</style>';
        $styles .= '<style>@media screen and (max-width:768px){#weekly-calendar-container{display: block;overflow-x: scroll;overflow-y: hidden;white-space: nowrap;}}</style>';

        if ($print) {
            echo $styles;

            return null;
        }

        return $styles;
    }

    /**
     * The events array.
     *
     * @var array<Event>
     */
    private array $events = [];

    /**
     * Add an event to the current calendar instantiation.
     *
     * @param string|DateTimeInterface $start the start date in Y-m-d format
     * @param string|DateTimeInterface $end the end date in Y-m-d format
     * @param string $summary the summary string of the event
     * @param bool $mask the masking class
     * @param string|list<string> $classes (optional) A list of classes to use for the event
     * @param string|list<string> $box_classes (optional) A list of classes to apply to the event summary box
     */
    public function addEvent(
        string|DateTimeInterface $start,
        string|DateTimeInterface $end,
        string $summary = '',
        bool $mask = false,
        string|array $classes = [],
        string|array $box_classes = [],
    ): static {
        $this->events[] = new Event(
            Carbon::parse($start),
            Carbon::parse($end),
            $summary,
            $mask,
            $classes,
            $box_classes
        );

        return $this;
    }

    /**
     * Add an array of events using $this->addEvent();.
     *
     * Each array element must have the following:
     *     'start'  =>   start date in Y-m-d format.
     *     'end'    =>   end date in Y-m-d format.
     *     (optional) 'mask' => a masking class name.
     *     (optional) 'classes' => custom classes to include.
     *
     * @param array<int, array{start: (string | DateTimeInterface), end: (string | DateTimeInterface), summary?: string, classes?: (string | list<string>), mask?: bool, event_box_classes?: (string | list<string>)}> $events the events array
     */
    public function addEvents(array $events): static
    {
        foreach ($events as $event) {
            $classes = $event['classes'] ?? '';
            $mask = (bool) ($event['mask'] ?? false);
            $summary = $event['summary'] ?? '';
            $box_classes = $event['event_box_classes'] ?? '';
            $this->addEvent($event['start'], $event['end'], $summary, $mask, $classes, $box_classes);
        }

        return $this;
    }

    /**
     * Remove all events tied to this calendar.
     */
    public function clearEvents(): static
    {
        $this->events = [];

        return $this;
    }

    /**
     * Use Month View.
     */
    public function useMonthView(): static
    {
        $this->type = 'month';

        return $this;
    }

    /**
     * Use Week View.
     */
    public function useWeekView(): static
    {
        $this->type = 'week';

        return $this;
    }

    /**
     * Add any custom table classes that should be injected into the calendar table header.
     *
     * This can be a space separated list, or an array of classes.
     *
     * @param string|list<string> $classes
     */
    public function addTableClasses(string|array $classes): static
    {
        $classes = is_array($classes) ? implode(' ', $classes) : $classes;

        $this->table_classes = $classes;

        return $this;
    }

    /**
     * Find an event from the internal pool.
     *
     * @param string $view The type of view - either Week or Month
     *
     * @return array<Event> either an array of events or false
     */
    private function findEvents(Carbon $start, Carbon $end, string $view = 'month'): array
    {
        if ('month' === $view) {
            // Extracting and comparing only the dates (Y-m-d) to avoid time-based exclusion
            $callback = fn (Event $event): bool => $start->greaterThanOrEqualTo((clone $event->start)->startOfDay()) && $start->lessThanOrEqualTo((clone $event->end)->endOfDay());
        } else {
            $callback = fn (Event $event): bool => $event->start->betweenIncluded($start, $end)
                || $event->end->betweenIncluded($start, $end)
                || $end->betweenIncluded($event->start, $event->end);
        }

        return array_filter($this->events, $callback);
    }

    /**
     * Returns the calendar as a month view.
     */
    public function asMonthView(DateTimeInterface|string|null $startDate = null, string $color = ''): string
    {
        $calendar = '';

        $colspan = 7;

        foreach (array_intersect($this->hiddenDays, Carbon::getDays()) as $day) {
            --$colspan;
            $calendar .= '<style>.cal-th-'.strtolower($day).',.cal-day-'.strtolower($day).'{display:none!important;}</style>';
        }

        $startDate = Carbon::parse($startDate)->firstOfMonth();

        $total_days_in_month = $startDate->daysInMonth();

        $calendar .= sprintf('<table class="calendar  %s %s ">', $color, $this->table_classes);

        $calendar .= '<thead>';

        $calendar .= '<tr class="calendar-title">';

        $calendar .= '<th colspan="'.$colspan.'">';

        $calendar .= ucfirst($startDate->locale($this->locale)->monthName).' '.$startDate->year;

        $calendar .= '</th>';

        $calendar .= '</tr>';
        $calendar .= '<tr class="calendar-header">';

        $carbonPeriod = Carbon::now()->locale($this->locale)->startOfWeek($this->starting_day)->toPeriod(7);
        /** @var Carbon $day */
        foreach ($carbonPeriod as $day) {
            $calendar .= '<th class="cal-th cal-th-'.strtolower($day->englishDayOfWeek).'">'.ucfirst('full' === $this->day_format ? $day->dayName : $day->minDayName).'</th>';
        }

        $calendar .= '</tr>';

        $calendar .= '</thead>';

        $calendar .= '<tbody>';

        $week = 1;
        $calendar .= '<tr class="cal-week-'.$week.'">';

        // padding before the month start date IE. if the month starts on Wednesday
        for ($x = 0; $x < $startDate->dayOfWeek; ++$x) {
            $calendar .= '<td class="pad cal-'.strtolower(Carbon::now()->dayOfWeek($x)->englishDayOfWeek).'"> </td>';
        }

        $running_day = $startDate->clone();

        $running_day_count = 1;

        do {
            $events = $this->findEvents((clone $running_day)->startOfDay(), (clone $running_day)->endOfDay(), 'month');

            $classes = '';

            $event_summary = '';
            $today_class = $running_day->isToday() ? ' today' : '';

            foreach ($events as $event) {
                // is the current day the start of the event
                if ($event->start->isSameDay($running_day)) {
                    $classes .= $event->mask ? ' mask-start' : '';
                    $classes .= $event->classes;
                    $event_summary .= ($event->summary) ? '<span class="event-summary-row '.$event->box_classes.'">'.$event->summary.'</span>' : '';

                // is the current day in between the start and end of the event
                } elseif ($running_day->betweenExcluded($event->start, $event->end)) {
                    $classes .= $event->mask ? ' mask' : '';

                // is the current day the start of the event
                } elseif ($running_day->isSameDay($event->end)) {
                    $classes .= $event->mask ? ' mask-end' : '';
                }
            }

            $dayRender = '<td class="day cal-day cal-day-'.strtolower($running_day->englishDayOfWeek).' '.$classes.$today_class.'" title="'.htmlentities(strip_tags($event_summary)).'">';

            $dayRender .= '<div class="cal-day-box">';

            $dayRender .= $running_day->day;

            $dayRender .= '</div>';

            $dayRender .= '<div class="cal-event-box">';

            $dayRender .= $event_summary;

            $dayRender .= '</div>';

            $dayRender .= '</td>';

            // check if this calendar-row is full and if so push to a new calendar row
            if ($running_day->dayOfWeek == $this->starting_day) {
                $calendar .= '</tr>';

                // start a new calendar row if there are still days left in the month
                if (($running_day_count + 1) <= $total_days_in_month) {
                    ++$week;
                    $calendar .= '<tr class="cal-week-'.$week.'">';
                }

                // reset padding because its a new calendar row
                $day_padding_offset = 0;
            }

            $calendar .= $dayRender;
            $running_day->addDay();

            ++$running_day_count;
        } while ($running_day_count <= $total_days_in_month);

        if (0 == $this->starting_day) {
            $padding_at_end_of_month = 7 - $running_day->dayOfWeek;
        } else {
            $padding_at_end_of_month = (0 == $running_day->dayOfWeek) ? 1 : 7 - ($running_day->dayOfWeek - 1);
        }

        // padding at the end of the month
        if ($padding_at_end_of_month && $padding_at_end_of_month < 7) {
            for ($x = 1; $x <= $padding_at_end_of_month; ++$x) {
                $offset = (($x - 1) + $running_day->dayOfWeek);
                if (7 == $offset) {
                    $offset = 0;
                }

                $calendar .= '<td class="pad cal-'.strtolower(Carbon::now()->dayOfWeek($offset)->englishDayOfWeek).'"> </td>';
            }
        }

        $calendar .= '</tr>';

        $calendar .= '</tbody>';

        return $calendar.'</table>';
    }

    /**
     * Sets the time formats when overriding the default week view calendar start/end time and intervals.
     */
    public function setTimeFormat(string $start_time = '00:00', string $end_time = '00:00', int $minutes = 30): static
    {
        $this->start_time = $start_time;
        $this->end_time = $end_time;
        $this->time_interval = $minutes;

        return $this;
    }

    /**
     * Get an array of time slots.
     *
     * @return list<string>
     */
    public function getTimes(): array
    {
        $start_time = Carbon::createFromFormat('H:i', $this->start_time);
        $end_time = Carbon::createFromFormat('H:i', $this->end_time);
        if ($start_time->equalTo($end_time)) {
            $end_time->addDay();
        }

        $carbonPeriod = CarbonInterval::minutes($this->time_interval)->toPeriod($this->start_time, $end_time);

        $times = [];
        foreach ($carbonPeriod as $time) {
            $times[] = $time->format('H:i');
        }

        return array_unique($times);
    }

    /**
     * Returns the calendar output as a week view.
     */
    public function asWeekView(DateTimeInterface|string|null $startDate = null, string $color = ''): string
    {
        $calendar = '<div class="weekly-calendar-container">';

        $colspan = 7;

        foreach (array_intersect($this->hiddenDays, Carbon::getDays()) as $day) {
            --$colspan;
            $calendar .= '<style>.cal-'.strtolower($day).',.cal-day-'.strtolower($day).'{display:none!important;}</style>';
        }

        $startDate = Carbon::parse($startDate);

        if ($this->starting_day !== $startDate->dayOfWeek) {
            if (0 === $this->starting_day) {
                $startDate->previous('sunday');
            } elseif (1 == $this->starting_day) {
                $startDate->previous('monday');
            }
        }

        $carbonPeriod = $startDate->locale($this->locale)->toPeriod(7);

        $today = Carbon::now();

        $calendar .= '<table class="weekly-calendar calendar '.$color.' '.$this->table_classes.'">';

        $calendar .= '<thead>';

        $calendar .= '<tr class="calendar-header">';

        $calendar .= '<th></th>';

        /** @var Carbon $date */
        foreach ($carbonPeriod as $date) {
            $calendar .= '<th class="cal-th cal-th-'.strtolower($date->englishDayOfWeek).'">';
            $calendar .= '<div class="cal-weekview-dow">'.ucfirst($date->localeDayOfWeek).'</div>';
            $calendar .= '<div class="cal-weekview-day">'.$date->day.'</div>';
            $calendar .= '<div class="cal-weekview-month">'.ucfirst($date->localeMonth).'</div>';
            $calendar .= '</th>';
        }

        $calendar .= '</tr>';

        $calendar .= '</thead>';

        $calendar .= '<tbody>';

        $used_events = [];

        foreach ($this->getTimes() as $time) {
            $calendar .= '<tr>';

            $start_time = $time;
            $end_time = date('H:i', strtotime($time.' + '.$this->time_interval.' minutes'));

            $calendar .= '<td class="cal-weekview-time-th"><div>'.$start_time.' - '.$end_time.'</div></td>';

            /** @var Carbon $date */
            foreach ($carbonPeriod as $date) {
                $datetime = $date->setTimeFrom($time);

                $events = $this->findEvents($datetime, $datetime->clone()->addMinutes($this->time_interval), 'week');

                $today_class = $date->isSameHour($today) ? ' today' : '';

                $calendar .= '<td class="cal-weekview-time '.$today_class.'">';

                $calendar .= '<div>';

                foreach ($events as $event) {
                    $classes = '';

                    if (in_array($event, $used_events)) {
                        $event_summary = '&nbsp;';
                    } else {
                        $event_summary = $event->summary;
                        $used_events[] = $event;
                    }

                    // is the current day the start of the event
                    if ($event->start->isSameDay($date)) {
                        $classes .= $event->mask ? ' mask-start' : '';
                        $classes .= $event->classes;
                    // is the current day in between the start and end of the event
                    } elseif ($date->betweenExcluded($event->start, $event->end)) {
                        $classes .= $event->mask ? ' mask' : '';

                    // is the current day the start of the event
                    } elseif ($date->isSameDay($event->end)) {
                        $classes .= $event->mask ? ' mask-end' : '';
                    }

                    $calendar .= '<div class="cal-weekview-event '.$classes.'">';
                    $calendar .= $event_summary;
                    $calendar .= '</div>';
                }

                $calendar .= '</div>';

                $calendar .= '</td>';
            }

            $calendar .= '<tr/>';
        }

        $calendar .= '</tbody>';

        $calendar .= '</table>';

        return $calendar.'</div>';
    }

    /**
     * Draw the calendar and return HTML output.
     *
     * @param string|DateTimeInterface|null $date the date of this calendar
     *
     * @return string The calendar
     */
    public function draw(DateTimeInterface|string|null $date = null, string $color = ''): string
    {
        return 'week' === $this->type ? $this->asWeekView($date, $color) : $this->asMonthView($date, $color);
    }

    /**
     * Shortcut helper to print the calendar output.
     */
    public function display(string|DateTimeInterface|null $date = null, string $color = ''): void
    {
        echo $this->stylesheet();
        echo $this->draw($date, $color);
    }
}
