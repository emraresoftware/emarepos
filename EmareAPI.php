<?php
/**
 * EmareAPI Helper — PHP projeleri için anahtar yöneticisi
 * EmareAPI kasasından JWT ile API anahtarı çeker.
 *
 * Kullanım:
 *   $key = EmareAPI::get('GEMINI_API_KEY');
 */
class EmareAPI {
    private static ?string $token = null;

    public static function get(string $keyName): string {
        $baseUrl  = env('EMAREAPI_URL', 'http://localhost:8000');
        $username = env('EMAREAPI_USERNAME', '');
        $password = env('EMAREAPI_PASSWORD', '');

        if (!self::$token) {
            $resp = Http::post("{$baseUrl}/auth/login", [
                'username' => $username,
                'password' => $password,
            ]);
            if ($resp->failed()) {
                throw new \RuntimeException("EmareAPI giriş başarısız: " . $resp->body());
            }
            self::$token = $resp->json('access_token');
        }

        $keyResp = Http::withToken(self::$token)
            ->get("{$baseUrl}/keys/{$keyName}/reveal");

        if ($keyResp->failed()) {
            throw new \RuntimeException("Anahtar alınamadı: {$keyName}");
        }
        return $keyResp->json('value');
    }
}
