<?php

namespace WeatherBot;

class Emoji
{
    private const WEATHER_EMOJI = [
        'clear sky' => "\u{2600}",
        'sky is clear' => "\u{2600}",
        'clear sky night' => "\u{1F319}", // Crescent Moon or '1F311' for Moon
        'scattered clouds' => "\u{2601}", // one cloud
        'broken clouds' => "\u{2601}", // two clouds
        'overcast clouds' => "\u{2601}", // two clouds
        'few clouds' => "\u{26C5}", // one cloud & sun
        'light rain' => "\u{2614}", // cloud & rain & sun
        'moderate rain' => "\u{2614}", // cloud & rain & sun
        'heavy intensity rain' => "\u{2614}",
        'light snow' => "\u{2744}",
        'snow' => "\u{2744}",
    ];

    /**
     * @param string $weatherDescription
     *
     * @return string
     */
    public function render(string $weatherDescription): string
    {
        return self::WEATHER_EMOJI[$weatherDescription] ?? '';
    }
}
