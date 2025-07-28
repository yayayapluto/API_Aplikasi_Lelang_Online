<?php

namespace App\Custom;

class Formatter
{
    public static function ApiResponse(int $code = 200, string $message = "no message", mixed $data = null, mixed $error = null)
    {
        $success = is_numeric($code) && $code > 199 && $code < 300;
        return response()->json([
            "success" => $success,
            "message" => $message,
            "content" => $data,
            "error" => $error
        ], $code);
    }
}
