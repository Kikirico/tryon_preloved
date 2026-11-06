<?php
namespace App\Services;
use Illuminate\Support\Facades\Http;
class FashnClient {
    public function request() {
        $key = is_file(config('fashn.key_file')) ? trim(file_get_contents(config('fashn.key_file'))) : '';
        if (!$key) throw new \RuntimeException('Layanan FASHN belum disiapkan.');
        return Http::baseUrl(config('fashn.url'))->withToken($key)->connectTimeout(3);
    }
    public function ready(): bool {
        try { return $this->request()->timeout(4)->get('/health')->json('ready') === true; }
        catch (\Throwable) { return false; }
    }
}
