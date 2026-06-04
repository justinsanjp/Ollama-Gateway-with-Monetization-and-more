<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Config\App;
use App\Config\Database;
use App\Models\AiModel;
use App\Models\User;
use App\Models\Usage;
use App\Services\BillingService;
use App\Services\OllamaService;
use App\Services\QueueService;

final class ApiController
{
    private array $apiUser;

    public function __construct()
    {
        $this->apiUser = $_REQUEST['_api_user'] ?? [];
    }

    public function listModels(): void
    {
        $ollamaModels = OllamaService::listModels();
        $ollamaNames = [];
        foreach ($ollamaModels['models'] ?? [] as $m) {
            $ollamaNames[] = $m['id'];
        }

        // Only show visible models (active and deactivated — NOT hidden)
        $dbModels = AiModel::getAllVisible();
        $result = [
            'object' => 'list',
            'data' => [],
        ];

        foreach ($dbModels as $model) {
            $isGhost = !empty($model['internal_model']);
            $resolvedName = $isGhost ? $model['internal_model'] : $model['name'];
            if (in_array($resolvedName, $ollamaNames, true)) {
                $result['data'][] = [
                    'id' => $model['name'],
                    'object' => 'model',
                    'created' => strtotime($model['created_at']),
                    'owned_by' => $model['provider'],
                    'permission' => [],
                ];
            }
        }

        header('Content-Type: application/json');
        echo json_encode($result, JSON_UNESCAPED_UNICODE);
    }

    public function retrieveModel(array $params): void
    {
        $modelName = $params['model'] ?? '';
        $model = AiModel::findByName($modelName);

        if (!$model || !$model['is_active']) {
            http_response_code(404);
            header('Content-Type: application/json');
            echo json_encode([
                'error' => [
                    'message' => "Model '{$modelName}' not found",
                    'type' => 'model_not_found',
                    'code' => 404,
                ],
            ]);
            return;
        }

        header('Content-Type: application/json');
        echo json_encode([
            'id' => $model['name'],
            'object' => 'model',
            'created' => strtotime($model['created_at']),
            'owned_by' => $model['provider'],
        ], JSON_UNESCAPED_UNICODE);
    }

    public function cancelCompletion(array $params): void
    {
        $requestId = $params['requestId'] ?? '';
        if (empty($requestId)) {
            http_response_code(400);
            echo json_encode([
                'error' => [
                    'message' => 'Request ID is required',
                    'type' => 'invalid_request_error',
                    'code' => 400,
                ],
            ]);
            return;
        }

        $cancelDir = '/tmp/opencode-cancel';
        if (!is_dir($cancelDir)) {
            @mkdir($cancelDir, 0755, true);
        }

        $cancelFile = $cancelDir . '/' . basename($requestId);
        file_put_contents($cancelFile, '1');

        http_response_code(200);
        header('Content-Type: application/json');
        echo json_encode([
            'message' => 'Cancel signal sent. The stream will terminate shortly.',
            'request_id' => $requestId,
        ]);
    }

    public function chatCompletions(): void
    {
        $body = json_decode(file_get_contents('php://input'), true);

        if (!$body || !isset($body['model'])) {
            http_response_code(400);
            echo json_encode([
                'error' => [
                    'message' => 'Model is required',
                    'type' => 'invalid_request_error',
                    'code' => 400,
                ],
            ]);
            return;
        }

        $queueData = QueueService::getQueueData();
        if ($queueData !== null) {
            http_response_code(503);
            echo json_encode([
                'error' => [
                    'message' => "Server is busy. You are #{$queueData['position']} in queue. Please wait and retry.",
                    'type' => 'server_busy',
                    'code' => 503,
                    'queue_position' => $queueData['position'],
                    'queue_total' => $queueData['total'],
                ],
            ]);
            return;
        }

        $modelName = $body['model'];
        $model = AiModel::findByName($modelName);

        if (!$model) {
            http_response_code(404);
            echo json_encode([
                'error' => [
                    'message' => "Model '{$modelName}' not found",
                    'type' => 'model_not_found',
                    'code' => 404,
                ],
            ]);
            return;
        }

        $status = $model['status'] ?? 'active';

        // Hidden models return 404 as if they don't exist
        if ($status === 'hidden') {
            http_response_code(404);
            echo json_encode([
                'error' => [
                    'message' => "Model '{$modelName}' not found",
                    'type' => 'model_not_found',
                    'code' => 404,
                ],
            ]);
            return;
        }

        // Deactivated models return an error with the deactivation reason
        if ($status === 'deactivated') {
            $reason = $model['deactivation_reason'] ?? 'This model is currently unavailable.';
            http_response_code(503);
            echo json_encode([
                'error' => [
                    'message' => $reason,
                    'type' => 'model_deactivated',
                    'code' => 503,
                ],
            ]);
            return;
        }

        // Active models proceed as normal
        if ($status !== 'active') {
            http_response_code(503);
            echo json_encode([
                'error' => [
                    'message' => 'This model is currently unavailable.',
                    'type' => 'model_unavailable',
                    'code' => 503,
                ],
            ]);
            return;
        }

        // Resolve ghost model to real Ollama model name
        if (!empty($model['internal_model'])) {
            $body['model'] = $model['internal_model'];
        }

        $messages = $body['messages'] ?? [];
        if (empty($messages)) {
            http_response_code(400);
            echo json_encode([
                'error' => [
                    'message' => 'Messages are required',
                    'type' => 'invalid_request_error',
                    'code' => 400,
                ],
            ]);
            return;
        }

        // Inject system prompt if set on model
        if (!empty($model['system_prompt'])) {
            $hasSystem = false;
            foreach ($messages as $msg) {
                if (isset($msg['role']) && $msg['role'] === 'system') {
                    $hasSystem = true;
                    break;
                }
            }
            if (!$hasSystem) {
                array_unshift($messages, [
                    'role' => 'system',
                    'content' => $model['system_prompt'],
                ]);
                $body['messages'] = $messages;
            }
        }

        $isStream = $body['stream'] ?? false;
        $inputPrice = (float) $model['input_price'];
        $outputPrice = (float) $model['output_price'];

        $estimatedInputTokens = self::estimateTokens($messages);
        $estimatedOutputTokens = $body['max_tokens'] ?? 2048;

        if (is_array($estimatedOutputTokens)) {
            $estimatedOutputTokens = 2048;
        }

        $estimatedCost = BillingService::calculateCost(
            $estimatedInputTokens,
            (int) $estimatedOutputTokens,
            $inputPrice,
            $outputPrice
        );

        $balanceCheck = BillingService::checkBalance(
            $this->apiUser['id'],
            $estimatedCost
        );

        if (!$balanceCheck['sufficient']) {
            http_response_code(402);
            echo json_encode([
                'error' => [
                    'message' => 'Insufficient credits — Your account does not have enough credits to process this request. Required: $' . number_format($balanceCheck['required'], 6, '.', ',') .
                        ', Available: $' . number_format($balanceCheck['balance'], 6, '.', ',') .
                        ', Shortfall: $' . number_format($balanceCheck['shortfall'], 6, '.', ',') .
                        '. Please top up your account and try again.',
                    'type' => 'insufficient_balance',
                    'code' => 402,
                ],
            ]);
            return;
        }

        $startTime = hrtime(true);
        $requestId = BillingService::requestId();
        $totalTokensInput = 0;
        $totalTokensOutput = 0;
        $totalCost = 0;
        $streamAborted = false;

        if ($isStream) {
            self::streamResponse($body, $model, $this->apiUser, $startTime, $requestId, $inputPrice, $outputPrice);
            return;
        }

        $result = OllamaService::nonStreamChatCompletion($body);

        if (isset($result['error'])) {
            http_response_code(502);
            echo json_encode($result);
            return;
        }

        $usage = $result['usage'] ?? [];
        $totalTokensInput = $usage['prompt_tokens'] ?? $estimatedInputTokens;
        $totalTokensOutput = $usage['completion_tokens'] ?? 0;
        $totalCost = BillingService::calculateCost($totalTokensInput, $totalTokensOutput, $inputPrice, $outputPrice);
        $durationMs = (int) ((hrtime(true) - $startTime) / 1e6);

        $deducted = BillingService::deductAndRecord([
            'user_id' => $this->apiUser['id'],
            'model_id' => $model['id'],
            'tokens_input' => $totalTokensInput,
            'tokens_output' => $totalTokensOutput,
            'cost' => $totalCost,
            'endpoint' => '/v1/chat/completions',
            'streamed' => 0,
            'duration_ms' => $durationMs,
            'request_id' => $requestId,
        ]);

        if (!$deducted) {
            http_response_code(402);
            echo json_encode([
                'error' => [
                    'message' => 'Insufficient credits — Your account balance changed during processing and is no longer sufficient to complete this request. Please top up your account and try again.',
                    'type' => 'insufficient_balance',
                    'code' => 402,
                ],
            ]);
            return;
        }

        $result['usage']['prompt_tokens'] = $totalTokensInput;
        $result['usage']['completion_tokens'] = $totalTokensOutput;
        $result['usage']['total_tokens'] = $totalTokensInput + $totalTokensOutput;

        header('Content-Type: application/json');
        echo json_encode($result, JSON_UNESCAPED_UNICODE);
    }

    private static function streamResponse(array $body, array $model, array $user, int $startTime, string $requestId, float $inputPrice, float $outputPrice): void
    {
        $totalTokensInput = 0;
        $totalTokensOutput = 0;
        $streamId = 'chatcmpl-' . bin2hex(random_bytes(12));

        header('Content-Type: text/event-stream');
        header('Cache-Control: no-cache');
        header('Connection: keep-alive');
        header('X-Accel-Buffering: no');

        // Disable all output buffering for PHP-FPM streaming
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        ob_implicit_flush(true);

        // Send request_id as first event so clients can cancel later
        echo 'event: request_id' . "\n";
        echo 'data: ' . json_encode(['request_id' => $requestId], JSON_UNESCAPED_UNICODE) . "\n\n";
        if (ob_get_level() > 0) {
            ob_flush();
        }
        flush();

        // Setup cancel signal directory
        $cancelDir = '/tmp/opencode-cancel';
        if (!is_dir($cancelDir)) {
            @mkdir($cancelDir, 0755, true);
        }

        $onChunk = function (array $openaiChunk, ?array $ollamaChunk) use (
            $user, $model, $requestId, $startTime, $inputPrice, $outputPrice, $streamId,
            &$totalTokensInput, &$totalTokensOutput
        ) {
            if (isset($openaiChunk['error'])) {
                echo 'data: ' . json_encode($openaiChunk, JSON_UNESCAPED_UNICODE) . "\n\n";
                ob_flush();
                flush();
                return;
            }

            if ($ollamaChunk !== null) {
                if (isset($ollamaChunk['message']['content'])) {
                    $totalTokensOutput++;
                }
                if (isset($ollamaChunk['prompt_eval_count'])) {
                    $totalTokensInput = max($totalTokensInput, $ollamaChunk['prompt_eval_count']);
                }
            }

            if (isset($openaiChunk['choices'][0]['finish_reason']) && $openaiChunk['choices'][0]['finish_reason'] === 'stop') {
                $totalCost = BillingService::calculateCost($totalTokensInput, $totalTokensOutput, $inputPrice, $outputPrice);
                $durationMs = (int) ((hrtime(true) - $startTime) / 1e6);

                $openaiChunk['usage'] = [
                    'prompt_tokens' => $totalTokensInput,
                    'completion_tokens' => $totalTokensOutput,
                    'total_tokens' => $totalTokensInput + $totalTokensOutput,
                ];

                if ($totalTokensInput > 0 || $totalTokensOutput > 0) {
                    try {
                        BillingService::deductAndRecord([
                            'user_id' => $user['id'],
                            'model_id' => $model['id'],
                            'tokens_input' => $totalTokensInput,
                            'tokens_output' => $totalTokensOutput,
                            'cost' => $totalCost,
                            'endpoint' => '/v1/chat/completions',
                            'streamed' => 1,
                            'duration_ms' => $durationMs,
                            'request_id' => $requestId,
                        ]);
                    } catch (\Exception $e) {
                        $openaiChunk['error'] = 'Billing error: ' . $e->getMessage();
                    }
                }
            }

            $content = $openaiChunk['choices'][0]['delta']['content'] ?? null;
            $reasoning = $openaiChunk['choices'][0]['delta']['reasoning_content'] ?? null;
            $isFinal = isset($openaiChunk['choices'][0]['finish_reason']);
            if ($content === '' && !$reasoning && !$isFinal) {
                return;
            }

            echo 'data: ' . json_encode($openaiChunk, JSON_UNESCAPED_UNICODE) . "\n\n";
            if (ob_get_level() > 0) {
                ob_flush();
            }
            flush();

            if (connection_aborted()) {
                return;
            }
        };

        try {
            OllamaService::streamChatCompletion($body, $onChunk, $requestId);
        } catch (\Exception $e) {
            echo 'data: ' . json_encode([
                'error' => [
                    'message' => $e->getMessage(),
                    'type' => 'server_error',
                    'code' => 500,
                ],
            ], JSON_UNESCAPED_UNICODE) . "\n\n";
            if (ob_get_level() > 0) {
                ob_flush();
            }
            flush();
        }

        // Clean up cancel signal file
        $cancelFile = $cancelDir . '/' . $requestId;
        if (file_exists($cancelFile)) {
            @unlink($cancelFile);
        }

        echo "data: [DONE]\n\n";
        if (ob_get_level() > 0) {
            ob_flush();
        }
        flush();
    }

    private static function estimateTokens(array $messages): int
    {
        $total = 0;
        foreach ($messages as $msg) {
            $content = $msg['content'] ?? '';
            if (is_array($content)) {
                foreach ($content as $part) {
                    if (isset($part['text'])) {
                        $total += (int) ceil(strlen($part['text']) / 4);
                    }
                    if (isset($part['image_url'])) {
                        $total += 1000;
                    }
                }
            } elseif (is_string($content)) {
                $total += (int) ceil(strlen($content) / 4);
            }
            $total += 10;
        }
        return max($total, 10);
    }
}
