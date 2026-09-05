<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Every editable field on the Creators Hub landing page.
 *
 * This is the single source of truth: the admin forms are generated from it, and the views
 * read through it. Each field carries the copy the page shipped with as its default, so an
 * empty database renders exactly what it renders today and an admin only overrides what they
 * actually want to change. Defaults pass through __(), so both locales keep working until
 * someone supplies their own wording.
 *
 * Adding a field is one entry here — no migration, no new form markup.
 *
 * 'anchor' names the element the section renders as on the live page, so the editor can show
 * that part of the real site beside the form. Null means the section has no block of its own.
 */
class SiteContentRegistry
{
    /**
     * @return array<string, array{label: string, description: string, anchor: ?string, fields: array<string, array{label: string, type: string, default?: string}>}>
     */
    public static function sections(): array
    {
        return [
            'hero' => [
                'label' => __('Hero'),
                'description' => __('The first screen of the page.'),
                'anchor' => 'hero',
                'fields' => [
                    'headline' => ['label' => __('Headline'), 'type' => 'textarea', 'default' => 'Where the people who design and build Egypt actually meet.'],
                    'body' => ['label' => __('Supporting text'), 'type' => 'textarea', 'default' => 'Creators Hub runs the events that bring interior designers, architects, contractors and the brands supplying them into the same room.'],
                    'primary_cta' => ['label' => __('Primary button'), 'type' => 'text', 'default' => 'Explore Events'],
                    'secondary_cta' => ['label' => __('Secondary button'), 'type' => 'text', 'default' => 'What we do'],
                    'image' => ['label' => __('Background image'), 'type' => 'image'],
                ],
            ],

            'stats' => [
                'label' => __('Headline Figures'),
                'description' => __('Three figures shown under the hero. Each needs both a figure and a label to appear; the band hides entirely when none are filled in.'),
                'anchor' => 'hero',
                'fields' => [
                    'figure_one' => ['label' => __('First figure'), 'type' => 'text'],
                    'label_one' => ['label' => __('First label'), 'type' => 'text'],
                    'figure_two' => ['label' => __('Second figure'), 'type' => 'text'],
                    'label_two' => ['label' => __('Second label'), 'type' => 'text'],
                    'figure_three' => ['label' => __('Third figure'), 'type' => 'text'],
                    'label_three' => ['label' => __('Third label'), 'type' => 'text'],
                ],
            ],

            'about' => [
                'label' => __('About'),
                'description' => __('Who Creators Hub is for.'),
                'anchor' => 'about',
                'fields' => [
                    'eyebrow' => ['label' => __('Small label'), 'type' => 'text', 'default' => 'About Creators Hub'],
                    'heading' => ['label' => __('Heading'), 'type' => 'textarea', 'default' => 'A hub for the people shaping spaces.'],
                    'statement' => ['label' => __('Statement'), 'type' => 'textarea', 'default' => 'Designers. Builders. Brands. Creators.'],
                    'statement_second_line' => ['label' => __('Statement, second line'), 'type' => 'text', 'default' => 'One connected ecosystem.'],
                    'body' => ['label' => __('Body'), 'type' => 'textarea', 'default' => 'Creators Hub connects the people and organizations behind interior design and construction — the architects, contractors, and developers who shape the built environment, and the suppliers, brands, and creative communities who work alongside them.'],
                    'image' => ['label' => __('Section image'), 'type' => 'image'],
                ],
            ],

            'audiences' => [
                'label' => __('Who it is for'),
                'description' => __('The heading above the audience switch. The tabs and their cards are managed under Audience Tabs.'),
                'anchor' => 'audiences',
                'fields' => [
                    'heading' => ['label' => __('Heading'), 'type' => 'textarea', 'default' => 'Two sides of the same room.'],
                    'body' => ['label' => __('Body'), 'type' => 'textarea', 'default' => 'An event only works when both halves of the industry turn up. Pick your side to see what Creators Hub is for.'],
                ],
            ],

            'events' => [
                'label' => __('Events'),
                'description' => __('Wording around the event listing. The events themselves are managed under Events.'),
                'anchor' => 'events',
                'fields' => [
                    'heading' => ['label' => __('Heading'), 'type' => 'textarea', 'default' => 'Events that bring the industry together.'],
                    'view_all' => ['label' => __('View all link'), 'type' => 'text', 'default' => 'View All Events'],
                    'empty_heading' => ['label' => __('Heading when there are no events'), 'type' => 'textarea', 'default' => 'Our first gathering is in the works.'],
                    'empty_body' => ['label' => __('Text when there are no events'), 'type' => 'textarea', 'default' => 'Get in touch to hear about it first — dates, format, and who is speaking, before anyone else.'],
                ],
            ],

            'community' => [
                'label' => __('Community'),
                'description' => __('The relationships-beyond-events section.'),
                'anchor' => 'community',
                'fields' => [
                    'eyebrow' => ['label' => __('Small label'), 'type' => 'text', 'default' => 'Community'],
                    'heading' => ['label' => __('Heading'), 'type' => 'textarea', 'default' => 'More than events. It is a community.'],
                    'body' => ['label' => __('Body'), 'type' => 'textarea', 'default' => 'Creators Hub is about relationships that outlast a single event — the ongoing exchange between the people designing spaces and the people building them.'],
                    'statement' => ['label' => __('Closing line'), 'type' => 'textarea', 'default' => 'A place where ideas meet people, and people create what comes next.'],
                    'cta' => ['label' => __('Button'), 'type' => 'text', 'default' => 'Join the Community'],
                ],
            ],

            'why_egypt' => [
                'label' => __('Why Egypt'),
                'description' => __('Reasons to build here. Hidden until the heading and at least one point are filled in.'),
                'anchor' => 'why-egypt',
                'fields' => [
                    'heading' => ['label' => __('Heading'), 'type' => 'textarea'],
                    'body' => ['label' => __('Body'), 'type' => 'textarea'],
                    'point_one' => ['label' => __('First point'), 'type' => 'text'],
                    'point_two' => ['label' => __('Second point'), 'type' => 'text'],
                    'point_three' => ['label' => __('Third point'), 'type' => 'text'],
                    'point_four' => ['label' => __('Fourth point'), 'type' => 'text'],
                    'point_five' => ['label' => __('Fifth point'), 'type' => 'text'],
                    'point_six' => ['label' => __('Sixth point'), 'type' => 'text'],
                    'image' => ['label' => __('Section image'), 'type' => 'image'],
                ],
            ],

            'partners' => [
                'label' => __('Partners'),
                'description' => __('The partnership invitation.'),
                'anchor' => 'partners',
                'fields' => [
                    'heading' => ['label' => __('Heading'), 'type' => 'textarea', 'default' => 'Built through collaboration.'],
                    'body' => ['label' => __('Body'), 'type' => 'textarea', 'default' => 'Creators Hub is founded on partnerships with the brands and organizations that supply, build, and shape the industry.'],
                    'cta' => ['label' => __('Button'), 'type' => 'text', 'default' => 'Become a Partner'],
                ],
            ],

            'faq' => [
                'label' => __('FAQ heading'),
                'description' => __('The questions themselves are managed under Creators Hub FAQs.'),
                'anchor' => 'faq',
                'fields' => [
                    'heading' => ['label' => __('Heading'), 'type' => 'textarea', 'default' => 'Questions people ask us.'],
                    'image' => ['label' => __('Image beside the questions'), 'type' => 'image'],
                ],
            ],

            'cta' => [
                'label' => __('Closing call to action'),
                'description' => __('The band above the contact form.'),
                'anchor' => 'contact',
                'fields' => [
                    'heading' => ['label' => __('Heading'), 'type' => 'textarea', 'default' => 'Be part of what is next.'],
                    'body' => ['label' => __('Body'), 'type' => 'textarea', 'default' => 'Join the community shaping the future of interior design and construction.'],
                    'primary_cta' => ['label' => __('Primary button'), 'type' => 'text', 'default' => 'Explore Events'],
                    'secondary_cta' => ['label' => __('Secondary button'), 'type' => 'text', 'default' => 'Get in Touch'],
                ],
            ],

            'contact' => [
                'label' => __('Contact'),
                'description' => __('The contact form introduction.'),
                'anchor' => 'contact',
                'fields' => [
                    'eyebrow' => ['label' => __('Small label'), 'type' => 'text', 'default' => 'Contact'],
                    'heading' => ['label' => __('Heading'), 'type' => 'textarea', 'default' => 'Get in touch.'],
                    'body' => ['label' => __('Body'), 'type' => 'textarea', 'default' => 'Whether you want to attend, partner, or bring an event idea to us — tell us a bit about it.'],
                    'submit' => ['label' => __('Submit button'), 'type' => 'text', 'default' => 'Send Message'],
                ],
            ],

            'footer' => [
                'label' => __('Footer'),
                'description' => __('The bottom of the page.'),
                'anchor' => null,
                'fields' => [
                    'blurb' => ['label' => __('Description under the logo'), 'type' => 'textarea', 'default' => 'Connecting the interior design and construction industry through events, community, and collaboration.'],
                ],
            ],

            'branding' => [
                'label' => __('Logo'),
                'description' => __('The Creators Hub mark in the navigation bar and the footer. Leave a slot empty to keep the logo the site ships with.'),
                'anchor' => null,
                'fields' => [
                    'nav_logo' => ['label' => __('Navigation bar logo'), 'type' => 'image'],
                    'footer_logo' => ['label' => __('Footer logo'), 'type' => 'image'],
                ],
            ],

            'contact_details' => [
                'label' => __('Contact and social links'),
                'description' => __('Shown in the footer. Anything left empty is simply not shown, and an event keeps its own details under that event.'),
                'anchor' => null,
                'fields' => [
                    'email' => ['label' => __('Email address'), 'type' => 'single', 'input' => 'email'],
                    'phone' => ['label' => __('Phone number'), 'type' => 'single', 'input' => 'tel'],
                    'address' => ['label' => __('Address'), 'type' => 'text'],
                    ...self::socialFields(),
                ],
            ],

            'sharing' => [
                'label' => __('Search results and link previews'),
                'description' => __('What people see when the site appears in search results or is shared on social media. A preview image works best at 1200 by 630 pixels.'),
                'anchor' => null,
                'fields' => [
                    'title' => ['label' => __('Page title'), 'type' => 'text', 'default' => 'Interior Design & Construction Events'],
                    'description' => ['label' => __('Description'), 'type' => 'textarea', 'default' => 'Creators Hub connects the interior design and construction industry through events, community, and collaboration.'],
                    'image' => ['label' => __('Preview image'), 'type' => 'image'],
                ],
            ],
        ];
    }

    /**
     * One URL field per network, so the list only has to be maintained in one place.
     *
     * @return array<string, array{label: string, type: string, input: string, placeholder: string}>
     */
    private static function socialFields(): array
    {
        $fields = [];

        foreach (SocialPlatforms::all() as $platform => $meta) {
            $fields[$platform] = [
                'label' => $meta['label'],
                'type' => 'single',
                'input' => 'url',
                'placeholder' => $meta['placeholder'],
            ];
        }

        return $fields;
    }

    /**
     * @return array<string, array{label: string, type: string, default?: string}>
     */
    public static function fieldsFor(string $section): array
    {
        return self::sections()[$section]['fields'] ?? [];
    }

    public static function has(string $section, string $field): bool
    {
        return array_key_exists($field, self::fieldsFor($section));
    }

    public static function isImage(string $section, string $field): bool
    {
        return (self::fieldsFor($section)[$field]['type'] ?? null) === 'image';
    }

    public static function defaultFor(string $section, string $field): ?string
    {
        return self::fieldsFor($section)[$field]['default'] ?? null;
    }
}
