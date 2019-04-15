<?php
declare(strict_types=1);

namespace WeatherBot\Tests;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Telegram\Bot\Objects\Update;
use WeatherBot\RequestHandler\CallbackHandler;
use WeatherBot\RequestHandler\Factory;
use WeatherBot\RequestHandler\MessageHandler;

class FactoryTest extends TestCase
{
    public function testUnknownUpdateType(): void
    {
        /** @var Update|MockObject $telegramUpdateMock */
        $telegramUpdateMock = $this->createMock(Update::class);
        $telegramUpdateMock
            ->method('isType')
            ->willReturn(false);

        $this->expectException(\UnexpectedValueException::class);

        (new Factory())->getHandlerObject($telegramUpdateMock);
    }

    public function testMessageUpdateType(): void
    {
        $telegramUpdate = new Update([
            'update_id' => 123,
            'message' => [
                'message_id' => 456,
                'text' => 'Test',
            ],
        ]);

        $handler = (new Factory())->getHandlerObject($telegramUpdate);

        $this->assertInstanceOf(MessageHandler::class, $handler);
    }

    public function testCallbackUpdateType(): void
    {
        $telegramUpdate = new Update([
            'update_id' => 123,
            'callback_query' => [
                'id' => 456,
                'data' => '777##1',
            ],
        ]);

        $handler = (new Factory())->getHandlerObject($telegramUpdate);

        $this->assertInstanceOf(CallbackHandler::class, $handler);
    }
}
