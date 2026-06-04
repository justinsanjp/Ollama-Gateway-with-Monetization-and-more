<?php

declare(strict_types=1);

namespace App\Services;

use App\Config\App;
use App\Models\AiModel;

final class OllamaService
{
    private static string $baseUrl = '';

    private static function getBaseUrl(): string
    {
        if (empty(self::$baseUrl)) {
            self::$baseUrl = rtrim(App::get('OLLAMA_BASE_URL', 'http://127.0.0.1:11434'), '/');
        }
        return self::$baseUrl;
    }

    public static function listModels(): array
    {
        $url = self::getBaseUrl() . '/api/tags';
        $response = self::httpGet($url);
        $data = json_decode($response, true);

        if (!$data || !isset($data['models'])) {
            return ['models' => []];
        }

        $models = [];
        foreach ($data['models'] as $model) {
            $models[] = [
                'id' => $model['name'],
                'object' => 'model',
                'created' => $model['modified_at'] ?? time(),
                'owned_by' => 'ollama',
            ];
        }

        return ['models' => $models, 'object' => 'list'];
    }

    public static function chatCompletion(array $request): string
    {
        $url = self::getBaseUrl() . '/api/chat';
        $ollamaRequest = self::transformChatRequest($request);

        return self::httpPost($url, $ollamaRequest);
    }

    public static function streamChatCompletion(array $request, callable $onChunk, ?string $requestId = null): void
    {
        $url = self::getBaseUrl() . '/api/chat';
        $ollamaRequest = self::transformChatRequest($request);
        $ollamaRequest['stream'] = true;

        $cancelDir = '/tmp/opencode-cancel';

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($ollamaRequest),
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_RETURNTRANSFER => false,
            CURLOPT_TIMEOUT => 300,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_BUFFERSIZE => 128,
            CURLOPT_TCP_NODELAY => true,
        ]);

        $buffer = '';

        curl_setopt($ch, CURLOPT_WRITEFUNCTION, function ($ch, $data) use ($onChunk, &$buffer, $cancelDir, $requestId) {
            if (connection_aborted()) {
                return 0;
            }
            if ($requestId && file_exists($cancelDir . '/' . $requestId)) {
                @unlink($cancelDir . '/' . $requestId);
                return 0;
            }

            $buffer .= $data;
            $lines = explode("\n", $buffer);
            $buffer = array_pop($lines);

            foreach ($lines as $line) {
                $line = trim($line);
                if (empty($line)) {
                    continue;
                }

                $ollamaChunk = json_decode($line, true);
                if (!$ollamaChunk) {
                    continue;
                }

                $openaiChunk = self::transformChatChunk($ollamaChunk);
                $onChunk($openaiChunk, $ollamaChunk);

                if (connection_aborted()) {
                    return 0;
                }
                if ($requestId && file_exists($cancelDir . '/' . $requestId)) {
                    @unlink($cancelDir . '/' . $requestId);
                    return 0;
                }
            }

            return strlen($data);
        });

        curl_exec($ch);

        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            $errorChunk = [
                'error' => [
                    'message' => "Ollama connection error: {$error}",
                    'type' => 'upstream_error',
                    'code' => 502,
                ],
            ];
            $onChunk($errorChunk, null);
        }
    }

    public static function nonStreamChatCompletion(array $request): array
    {
        $url = self::getBaseUrl() . '/api/chat';
        $ollamaRequest = self::transformChatRequest($request);
        $ollamaRequest['stream'] = false;

        $response = self::httpPost($url, $ollamaRequest);
        $ollamaResponse = json_decode($response, true);

        if (!$ollamaResponse || !isset($ollamaResponse['message'])) {
            $errorMsg = $ollamaResponse['error'] ?? 'Unknown Ollama error';
            return [
                'error' => [
                    'message' => $errorMsg,
                    'type' => 'upstream_error',
                    'code' => 502,
                ],
            ];
        }

        $promptTokens = $ollamaResponse['prompt_eval_count'] ?? 0;
        $completionTokens = $ollamaResponse['eval_count'] ?? 0;

        $thinking = $ollamaResponse['message']['thinking'] ?? '';
        $content = $ollamaResponse['message']['content'] ?? '';

        $message = ['role' => 'assistant'];
        if ($thinking && !$content) {
            $message['reasoning_content'] = trim($thinking);
            $message['content'] = '';
        } else {
            $message['content'] = $content;
            if ($thinking) {
                $message['reasoning_content'] = trim($thinking);
            }
        }

        return [
            'id' => 'chatcmpl-' . bin2hex(random_bytes(12)),
            'object' => 'chat.completion',
            'created' => time(),
            'model' => $request['model'] ?? 'unknown',
            'choices' => [
                [
                    'index' => 0,
                    'message' => $message,
                    'finish_reason' => 'stop',
                ],
            ],
            'usage' => [
                'prompt_tokens' => $promptTokens,
                'completion_tokens' => $completionTokens,
                'total_tokens' => $promptTokens + $completionTokens,
            ],
        ];
    }

    private static function transformChatRequest(array $request): array
    {
        $messages = [];
        foreach ($request['messages'] ?? [] as $msg) {
            $messages[] = [
                'role' => $msg['role'] ?? 'user',
                'content' => $msg['content'] ?? '',
            ];
        }

        $ollamaRequest = [
            'model' => $request['model'],
            'messages' => $messages,
            'stream' => $request['stream'] ?? false,
        ];

        $options = [];
        if (isset($request['temperature'])) {
            $options['temperature'] = (float) $request['temperature'];
        }

        if (isset($request['top_p'])) {
            $options['top_p'] = (float) $request['top_p'];
        }

        if (isset($request['max_tokens'])) {
            $options['num_predict'] = (int) $request['max_tokens'];
        }

        if (isset($request['stop'])) {
            $options['stop'] = $request['stop'];
        }

        if (!empty($options)) {
            $ollamaRequest['options'] = $options;
        }

        return $ollamaRequest;
    }

    private static function transformChatChunk(array $ollamaChunk): array
    {
        if (isset($ollamaChunk['done']) && $ollamaChunk['done'] === true) {
            return [
                'id' => 'chatcmpl-' . bin2hex(random_bytes(12)),
                'object' => 'chat.completion.chunk',
                'created' => time(),
                'model' => $ollamaChunk['model'] ?? 'unknown',
                'choices' => [
                    [
                        'index' => 0,
                        'delta' => new \stdClass(),
                        'finish_reason' => 'stop',
                    ],
                ],
                'usage' => [
                    'prompt_tokens' => $ollamaChunk['prompt_eval_count'] ?? 0,
                    'completion_tokens' => $ollamaChunk['eval_count'] ?? 0,
                    'total_tokens' => ($ollamaChunk['prompt_eval_count'] ?? 0) + ($ollamaChunk['eval_count'] ?? 0),
                ],
            ];
        }

        $content = $ollamaChunk['message']['content'] ?? '';
        $thinking = $ollamaChunk['message']['thinking'] ?? '';

        $delta = ['role' => 'assistant'];
        if ($thinking !== '' && $content === '') {
            $delta['reasoning_content'] = $thinking;
            $delta['content'] = '';
        } else {
            $delta['content'] = $content;
        }

        return [
            'id' => 'chatcmpl-' . bin2hex(random_bytes(12)),
            'object' => 'chat.completion.chunk',
            'created' => time(),
            'model' => $ollamaChunk['model'] ?? 'unknown',
            'choices' => [
                [
                    'index' => 0,
                    'delta' => $delta,
                    'finish_reason' => null,
                ],
            ],
        ];
    }

    private static function httpGet(string $url): string
    {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_CONNECTTIMEOUT => 5,
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error || $httpCode >= 400) {
            return json_encode(['error' => $error ?: "HTTP {$httpCode}"]);
        }

        return $response;
    }

    private static function httpPost(string $url, array $data): string
    {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 300,
            CURLOPT_CONNECTTIMEOUT => 10,
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            return json_encode(['error' => $error]);
        }

        return $response;
    }
}
