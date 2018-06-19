<?php

namespace WeatherBot;

use Telegram\Bot\Keyboard\Keyboard;

trait InlineKeyboardTrait
{
    /**
     * @param int $callbackData
     * @param bool $isDetailedForecast
     *
     * @return Keyboard
     */
    private function getInlineKeyboardRepeat(int $callbackData, bool $isDetailedForecast = true): Keyboard
    {
        return Keyboard::make()
            ->inline()
            ->row(
                Keyboard::inlineButton([
                    'text' => 'Repeat last request',
                    'callback_data' => $callbackData . '##' . (int) $isDetailedForecast
                ]),
                Keyboard::inlineButton([
                    'text' => $isDetailedForecast
                        ? '10-days forecast'
                        : 'Detailed forecast',
                    'callback_data' => $callbackData . '##' . (int) !$isDetailedForecast
                ])
            );
    }

    /**
     * @param array $data
     *
     * @return Keyboard
     */
    private function getInlineKeyboardMultipleChoices(array $data): Keyboard
    {
        $buttons = [];
        $maxButtonsPerRow = 2;
        $inlineKeyboard = Keyboard::make()->inline();
        foreach ($data as $city) {
            $buttons[] = Keyboard::inlineButton([
                'text' => "{$city['name']} ({$city['country']})",
                'callback_data' => $city['id']
            ]);
            if (\count($buttons) === $maxButtonsPerRow) {
                $inlineKeyboard = \call_user_func_array([$inlineKeyboard, 'row'], $buttons);
                $buttons = [];
            }
        }

        return $inlineKeyboard;
    }
}
