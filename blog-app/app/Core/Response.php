<?php
namespace App\Core;

class Response {
    public static function json($data, int $statusCode = 200): void {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data);
        exit;
    }

    public static function error(string $message, int $statusCode = 400, array $errors = []): void {
        $payload = [
            'success' => false,
            'error'   => $message
        ];
        if (!empty($errors)) {
            $payload['errors'] = $errors;
        }
        self::json($payload, $statusCode);
    }

    public static function success($data = [], string $message = 'Success', int $statusCode = 200): void {
        $payload = array_merge(['success' => true, 'message' => $message], is_array($data) ? $data : ['data' => $data]);
        self::json($payload, $statusCode);
    }
}
