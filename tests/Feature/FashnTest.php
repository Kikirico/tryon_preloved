<?php
namespace Tests\Feature;
use App\Jobs\GenerateTryon;
use App\Models\{Product, TryonRun, User};
use App\Services\FashnClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\{Http, Queue, Storage};
use Illuminate\Support\Str;
use Tests\TestCase;
class FashnTest extends TestCase {
    use RefreshDatabase;
    private function product(): Product {
        return Product::create(['name'=>'Shirt','brand'=>'Brand','size'=>'M','condition'=>'Good','description'=>str_repeat('LongText',200),'price'=>100000,'photo'=>'demo/sage.svg','garment'=>'demo/sage.svg','fashn_image'=>'fashn-products/shirt.png']);
    }
    private function ready(): void { $this->mock(FashnClient::class)->shouldReceive('ready')->andReturn(true); }
    private function makeRun(string $status = 'queued'): TryonRun {
        return TryonRun::create(['id'=>(string)Str::uuid(),'product_id'=>$this->product()->id,'owner_hash'=>hash('sha256','owner'),'person_path'=>'tryon/person.png','status'=>$status,'expires_at'=>now()->addDay()]);
    }
    public function test_navbar_and_fitting_page_render(): void {
        $p=$this->product();$this->get('/')->assertOk()->assertSee('AI Fitting');
        $this->get('/ai-fitting')->assertOk()->assertSee('Shirt');
        $this->get('/products/'.$p->id)->assertOk()->assertSee('product-description')->assertSee($p->description)
            ->assertSee(route('fashn', ['product' => $p->id]))->assertSee('Coba pakaian ini di AI Fitting');
        $this->get('/ai-fitting?product='.$p->id)->assertOk()->assertSee('value="'.$p->id.'" data-image="'.$p->photo_url.'" selected', false);
    }
    public function test_submission_requires_consent_and_queues_private_photo_once(): void {
        Queue::fake();Storage::fake('local');$this->ready();$p=$this->product();
        $this->postJson('/ai-fitting',['product_id'=>$p->id,'person'=>UploadedFile::fake()->image('person.jpg',600,800)])->assertUnprocessable();
        $payload=['product_id'=>$p->id,'consent'=>'1','person'=>UploadedFile::fake()->image('person.jpg',600,800)];
        $response=$this->withSession(['fashn_owner'=>'owner'])->postJson('/ai-fitting',$payload)->assertStatus(202);
        $run=TryonRun::findOrFail($response->json('id'));Storage::disk('local')->assertExists($run->person_path);
        Queue::assertPushed(GenerateTryon::class,1);
        $this->postJson('/ai-fitting',$payload)->assertStatus(409);
    }
    public function test_other_sessions_cannot_access_status_images_or_delete(): void {
        $run=$this->makeRun('completed');
        $this->withSession(['fashn_owner'=>'stranger'])->getJson('/ai-fitting/runs/'.$run->id)->assertNotFound();
        $this->get('/ai-fitting/runs/'.$run->id.'/images/person')->assertNotFound();
        $this->deleteJson('/ai-fitting/runs/'.$run->id)->assertNotFound();
    }
    public function test_generate_saves_png_and_marks_completed(): void {
        Storage::fake('local');Storage::fake('public');$run=$this->makeRun();
        Storage::disk('local')->put($run->person_path,'input');Storage::disk('public')->put($run->product->fashn_image,'garment');
        $png="\x89PNG\r\n\x1a\nexample";Http::fake(['*'=>Http::response($png,200,['Content-Type'=>'image/png'])]);
        $client=$this->mock(FashnClient::class);$client->shouldReceive('request')->andReturn(Http::baseUrl('http://127.0.0.1:8001'));
        (new GenerateTryon($run->id))->handle($client);
        $run->refresh();$this->assertSame('completed',$run->status);Storage::disk('local')->assertExists($run->result_path);
    }
    public function test_worker_failure_and_expiration_are_visible_and_pruned(): void {
        $run=$this->makeRun();(new GenerateTryon($run->id))->failed(new \RuntimeException('private detail'));
        $this->withSession(['fashn_owner'=>'owner'])->getJson('/ai-fitting/runs/'.$run->id)->assertJsonPath('status','failed')->assertDontSee('private detail');
        $run->update(['expires_at'=>now()->subMinute()]);
        $this->getJson('/ai-fitting/runs/'.$run->id)->assertStatus(410);
        $this->artisan('fashn:prune')->assertSuccessful();$this->assertDatabaseMissing('tryon_runs',['id'=>$run->id]);
    }
    public function test_admin_can_set_existing_product_fashn_image(): void {
        Storage::fake('public');$p=$this->product();$u=User::factory()->create();$u->forceFill(['is_admin'=>true])->save();
        $this->actingAs($u)->post('/admin/products/'.$p->id.'/fashn',['fashn_photo_type'=>'model','fashn_image'=>UploadedFile::fake()->image('shirt.jpg',600,800)])->assertRedirect();
        $this->assertSame('model',$p->fresh()->fashn_photo_type);Storage::disk('public')->assertExists($p->fresh()->fashn_image);
    }
}
