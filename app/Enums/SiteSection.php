<?php

declare(strict_types=1);

namespace App\Enums;

enum SiteSection: string
{
    case Stats = 'stats';
    case WhyEgypt = 'why_egypt';

    /**
     * The editable fields each section exposes, as field_key => label.
     *
     * @return array<string, string>
     */
    public function fields(): array
    {
        return match ($this) {
            self::Stats => [
                'figure_one' => __('First figure'),
                'label_one' => __('First label'),
                'figure_two' => __('Second figure'),
                'label_two' => __('Second label'),
                'figure_three' => __('Third figure'),
                'label_three' => __('Third label'),
            ],
            self::WhyEgypt => [
                'heading' => __('Heading'),
                'body' => __('Body'),
                'point_one' => __('First point'),
                'point_two' => __('Second point'),
                'point_three' => __('Third point'),
                'point_four' => __('Fourth point'),
                'point_five' => __('Fifth point'),
                'point_six' => __('Sixth point'),
            ],
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Stats => __('Headline Figures'),
            self::WhyEgypt => __('Why Egypt'),
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Stats => __('Three figures shown near the top of the landing page. The band stays hidden until at least one figure and its label are filled in.'),
            self::WhyEgypt => __('The reasons to build here. The section stays hidden until the heading and at least one point are filled in.'),
        };
    }
}
