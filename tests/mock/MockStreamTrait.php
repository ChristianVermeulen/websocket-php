<?php

/**
 * This class is used by phpunit tests to mock and track various socket/stream calls.
 */

namespace WebSocket\Test;

trait MockStreamTrait
{
    private $stack = [];
    private $last_ws_key;


    /* ---------- WebSocket Client combinded asserts --------------------------------------------------------------- */

    private function expectWsClientConnect(
        string $scheme = 'tcp',
        string $host = 'localhost',
        int $port = 8000,
        int $timeout = 5,
        array $context = [],
        bool $persistent = false
    ): void {
        $this->expectStreamFactoryCreateSockerClient()->addAssert(
            function ($method, $params) use ($scheme, $host, $port) {
                $this->assertInstanceOf('Phrity\Net\Uri', $params[0]);
                $this->assertEquals("{$scheme}://{$host}:{$port}", "{$params[0]}");
            }
        );
        $this->expectSocketClient()->addAssert(function ($method, $params) use ($scheme, $host, $port) {
            $this->assertInstanceOf('Phrity\Net\Uri', $params[0]);
            $this->assertEquals("{$scheme}://{$host}:{$port}", "{$params[0]}");
        });
        $this->expectSocketClientSetPersistent()->addAssert(function ($method, $params) use ($persistent) {
            $this->assertEquals($persistent, $params[0]);
        });
        $this->expectSocketClientSetTimeout()->addAssert(function ($method, $params) use ($timeout) {
            $this->assertEquals($timeout, $params[0]);
        });
        $this->expectSocketClientSetContext()->addAssert(function ($method, $params) use ($context) {
            $this->assertEquals($context, $params[0]);
        });
        $this->expectSocketClientConnect();
        $this->expectSocketStream();
        $this->expectSocketStreamGetMetadata();
        $this->expectSocketStreamSetTimeout()->addAssert(function ($method, $params) use ($timeout) {
            $this->assertEquals($timeout, $params[0]);
            $this->assertEquals(0, $params[1]);
        });
        $this->expectSocketStreamIsConnected();
        if ($persistent) {
            $this->expectSocketStreamTell();
        }
    }

    private function expectWsClientPerformHandshake(
        string $host = 'localhost:8000',
        string $path = '/my/mock/path',
        string $headers = '',
        int $timeout = 5,
        $tell = null
    ): void {
        $this->expectSocketStreamWrite()->addAssert(
            function (string $method, array $params) use ($host, $path, $headers): void {
                preg_match('/Sec-WebSocket-Key: ([\S]*)\r\n/', $params[0], $m);
                $this->last_ws_key = $m[1];
                $this->assertEquals(
                    "GET {$path} HTTP/1.1\r\nHost: {$host}\r\nUser-Agent: websocket-client-php\r\nConnection: Upgrade"
                    . "\r\nUpgrade: websocket\r\nSec-WebSocket-Key: {$this->last_ws_key}\r\nSec-WebSocket-Version: 13"
                    . "\r\n{$headers}\r\n",
                    $params[0]
                );
            }
        );

        $this->expectSocketStreamReadLine()->addAssert(function (string $method, array $params): void {
            $this->assertEquals(1024, $params[0]);
        })->setReturn(function (array $params) {
            $ws_key_res = base64_encode(pack('H*', sha1($this->last_ws_key . '258EAFA5-E914-47DA-95CA-C5AB0DC85B11')));
            return "HTTP/1.1 101 Switching Protocols\r\nUpgrade: websocket\r\nConnection: Upgrade"
            . "\r\nSec-WebSocket-Accept: {$ws_key_res}\r\n\r\n";
        });
    }


    /* ---------- WebSocket Server combinded asserts --------------------------------------------------------------- */

    private function expectWsServerPerformHandshake(?int $timeout = null): void
    {
        $this->expectSocketStreamReadLine()->addAssert(function (string $method, array $params): void {
            $this->assertEquals(1024, $params[0]);
        })->setReturn(function (array $params) {
            return "GET /my/mock/path HTTP/1.1\r\nHost: localhost:8000\r\nUser-Agent: websocket-client-php\r\n"
            . "Connection: Upgrade\r\nUpgrade: websocket\r\nSec-WebSocket-Key: cktLWXhUdDQ2OXF0ZCFqOQ==\r\n"
            . "Sec-WebSocket-Version: 13"
            . "\r\n\r\n";
        });
        $this->expectSocketStreamWrite()->addAssert(function (string $method, array $params): void {
            $expect = "HTTP/1.1 101 Switching Protocols\r\nUpgrade: websocket\r\nConnection: Upgrade\r\n"
            . "Sec-WebSocket-Accept: YmysboNHNoWzWVeQpduY7xELjgU=\r\n\r\n";
            $this->assertEquals($expect, $params[0]);
        })->setReturn(function () {
            return 129;
        });
    }
}