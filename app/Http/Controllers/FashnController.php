<?php
namespace App\Http\Controllers;
use App\Jobs\GenerateTryon;
use App\Models\{Product, TryonRun};
use App\Services\FashnClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Cache, DB, Storage};
use Illuminate\Support\Str;
class FashnController extends Controller {
    private function owner(Request $request): string {
        if (!$request->session()->has('fashn_owner')) $request->session()->put('fashn_owner', Str::random(48));
        return hash('sha256', $request->session()->get('fashn_owner'));
    }
    public function index(Request $request) {
        $owner = $this->owner($request);
        $products = Product::where('active', true)->whereNotNull('fashn_image')->get();
        $latest = TryonRun::where('owner_hash', $owner)->where('expires_at', '>', now())->latest()->first();
        return view('fashn', compact('products', 'latest'));
    }
    public function health(FashnClient $client) { return response()->json(['ready' => $client->ready()]); }
    public function store(Request $request, FashnClient $client) {
        $data = $request->validate(['product_id'=>'required|integer|exists:products,id', 'consent'=>'accepted', 'person'=>'required|image|mimes:jpg,jpeg,png,webp|max:5120|dimensions:min_width=256,min_height=256,max_width=4096,max_height=4096']);
        $product = Product::whereKey($data['product_id'])->where('active', true)->whereNotNull('fashn_image')->firstOrFail();
        abort_unless($client->ready(), 503, 'Layanan AI belum siap. Jalankan start-fashn.ps1 dan tunggu model siap.');
        $owner = $this->owner($request);
        return Cache::lock('fashn-admission', 15)->block(3, function () use ($request, $product, $owner) {
            abort_if(TryonRun::where('owner_hash',$owner)->whereIn('status',['queued','processing'])->where('expires_at','>',now())->exists(), 409, 'Foto sebelumnya masih diproses.');
            abort_if(TryonRun::whereIn('status',['queued','processing'])->where('expires_at','>',now())->count() >= 5, 429, 'Antrean penuh. Coba kembali nanti.');
            $id = (string) Str::uuid();
            $path = $request->file('person')->storeAs('tryon/'.$id, 'person.'.$request->file('person')->extension(), 'local');
            abort_unless($path, 500, 'Foto gagal disimpan.');
            try {
                $run = DB::transaction(function () use ($id,$product,$owner,$path) {
                    $run = TryonRun::create(['id'=>$id,'product_id'=>$product->id,'owner_hash'=>$owner,'person_path'=>$path,'expires_at'=>now()->addDay()]);
                    GenerateTryon::dispatch($id)->onConnection('fashn')->onQueue('fashn');
                    return $run;
                });
            } catch (\Throwable $e) { Storage::disk('local')->delete($path); throw $e; }
            return response()->json(['id'=>$run->id], 202);
        });
    }
    private function authorizeRun(Request $request, TryonRun $run): void {
        abort_unless(hash_equals($run->owner_hash, $this->owner($request)), 404);
        abort_if($run->expires_at->isPast(), 410, 'Hasil sudah kedaluwarsa.');
    }
    public function status(Request $request, TryonRun $run) {
        $this->authorizeRun($request,$run);
        if (($run->status === 'processing' && $run->updated_at->lt(now()->subMinutes(20))) || ($run->status === 'queued' && $run->updated_at->lt(now()->subHour()))) {
            $run->update(['status'=>'failed','error'=>'Proses terhenti. Pastikan worker AI aktif lalu coba kembali.']);
        }
        return response()->json(['id'=>$run->id,'status'=>$run->status,'error'=>$run->error,
            'before'=>route('fashn.image',[$run,'person']), 'result'=>$run->status === 'completed' ? route('fashn.image',[$run,'result']) : null])->header('Cache-Control','no-store');
    }
    public function image(Request $request, TryonRun $run, string $kind) {
        $this->authorizeRun($request,$run);
        abort_unless(in_array($kind,['person','result']),404);
        $path = $kind === 'person' ? $run->person_path : ($run->status === 'completed' ? $run->result_path : null);
        abort_unless($path && Storage::disk('local')->exists($path),404);
        return Storage::disk('local')->response($path, null, ['Cache-Control'=>'private, no-store', 'X-Content-Type-Options'=>'nosniff']);
    }
    public function destroy(Request $request, TryonRun $run) {
        $this->authorizeRun($request,$run);
        abort_if(in_array($run->status,['queued','processing']),409,'Tunggu proses selesai sebelum menghapus.');
        Storage::disk('local')->deleteDirectory('tryon/'.$run->id); $run->delete();
        return response()->noContent();
    }
}
