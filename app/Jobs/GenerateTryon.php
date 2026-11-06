<?php
namespace App\Jobs;
use App\Models\TryonRun;
use App\Services\FashnClient;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;
class GenerateTryon implements ShouldQueue {
    use Queueable;
    public $tries = 1;
    public $timeout = 660;
    public function __construct(public string $runId) {}
    public function handle(FashnClient $client): void {
        $run = TryonRun::find($this->runId);
        if (!$run || $run->status !== 'queued' || $run->expires_at->isPast()) return;
        if (!TryonRun::whereKey($run->id)->where('status','queued')->update(['status'=>'processing','updated_at'=>now()])) return;
        try {
            $person = Storage::disk('local')->get($run->person_path);
            $garment = Storage::disk('public')->get($run->product->fashn_image);
            if (!$person || !$garment) throw new \RuntimeException('Missing input image');
            $response = $client->request()->timeout(config('fashn.timeout'))
                ->attach('person',$person,'person.jpg')->attach('garment',$garment,'garment.png')
                ->post('/generate',['photo_type'=>$run->product->fashn_photo_type]);
            $response->throw();
            $body = $response->body();
            if (!str_starts_with($body, "\x89PNG\r\n\x1a\n")) throw new \RuntimeException('Invalid AI output');
            $path = 'tryon/'.$run->id.'/result.png';
            if (!Storage::disk('local')->put($path,$body)) throw new \RuntimeException('Cannot save output');
            TryonRun::whereKey($run->id)->where('status','processing')->update(['status'=>'completed','result_path'=>$path,'updated_at'=>now()]);
        } catch (\Throwable $e) {
            $this->failed($e);
        }
    }
    public function failed(?\Throwable $exception): void {
        TryonRun::whereKey($this->runId)->whereIn('status',['queued','processing'])->update(['status'=>'failed','error'=>'AI gagal memproses foto. Periksa layanan AI, VRAM, dan foto produk, lalu coba kembali.','updated_at'=>now()]);
        // Do not log request bodies: they contain private photos.
    }
}
