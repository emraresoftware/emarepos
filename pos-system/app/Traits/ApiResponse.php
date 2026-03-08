<?php

namespace App\Traits;

trait ApiResponse
{
    /**
     * Başarılı JSON yanıt
     */
    protected function success($data = [], string $message = 'Başarılı', int $code = 200)
    {
        return response()->json(array_merge([
            'success' => true,
            'message' => $message,
        ], is_array($data) ? $data : ['data' => $data]), $code);
    }

    /**
     * Hata JSON yanıt
     */
    protected function error(string $message = 'Bir hata oluştu', int $code = 422, array $errors = [])
    {
        $response = [
            'success' => false,
            'message' => $message,
        ];

        if (!empty($errors)) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $code);
    }

    /**
     * Kayıt bulunamadı yanıtı
     */
    protected function notFound(string $message = 'Kayıt bulunamadı')
    {
        return $this->error($message, 404);
    }

    /**
     * Yetkilendirme hatası
     */
    protected function forbidden(string $message = 'Bu işlem için yetkiniz yok')
    {
        return $this->error($message, 403);
    }
}
