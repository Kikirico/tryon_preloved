<?php
namespace Tests\Feature;
use App\Models\{Product, User, Order};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
class ShopTest extends TestCase {
    use RefreshDatabase;
    private function product(): Product { return Product::create(['name'=>'Test Shirt','brand'=>'Test','size'=>'M','condition'=>'Like new','description'=>'Test','price'=>100000,'photo'=>'demo/sage.svg','garment'=>'demo/sage.svg']); }
    private function buyer(): array { return ['name'=>'Buyer','email'=>'buyer@example.com','phone'=>'08123456789','address'=>'Jalan Test 123']; }
    public function test_checkout_reserves_product_and_uses_server_price(): void {
        config(['shop.bank_account'=>'123']); $p=$this->product();
        $this->post('/checkout/'.$p->id, $this->buyer()+['total'=>1])->assertRedirect();
        $this->assertDatabaseHas('orders',['product_id'=>$p->id,'total'=>120000]);
        $this->assertFalse($p->fresh()->active);
        $this->post('/checkout/'.$p->id,$this->buyer())->assertStatus(409);
    }
    public function test_non_admin_cannot_upload_or_read_admin(): void {
        $this->get('/admin')->assertRedirect('/admin/login');
        $this->actingAs(User::factory()->create())->post('/admin/products',[])->assertForbidden();
    }
    public function test_payment_confirmation_requires_proof_and_terminal_orders_cannot_change(): void {
        $admin=User::factory()->create();$admin->forceFill(['is_admin'=>true])->save();
        $p=$this->product(); $p->update(['active'=>false]);
        $o=Order::create($this->buyer()+['token'=>(string)\Illuminate\Support\Str::uuid(),'product_id'=>$p->id,'total'=>120000]);
        $this->actingAs($admin)->post('/admin/orders/'.$o->id.'/status',['status'=>'paid'])->assertStatus(422);
        $this->post('/admin/orders/'.$o->id.'/status',['status'=>'cancelled'])->assertRedirect();
        $this->assertTrue($p->fresh()->active);
        $this->post('/admin/orders/'.$o->id.'/status',['status'=>'paid'])->assertStatus(409);
    }
    public function test_proof_is_private_and_review_is_not_automatically_paid(): void {
        Storage::fake('local');$p=$this->product();
        $o=Order::create($this->buyer()+['token'=>(string)\Illuminate\Support\Str::uuid(),'product_id'=>$p->id,'total'=>120000]);
        $this->post('/orders/'.$o->token.'/proof',['proof'=>UploadedFile::fake()->image('proof.png')])->assertRedirect();
        $o->refresh();$this->assertSame('review',$o->status);Storage::disk('local')->assertExists($o->proof);
        $this->get('/admin/orders/'.$o->id.'/proof')->assertRedirect('/admin/login');
    }
    public function test_pages_render_with_seed_products(): void {
        $this->seed();$p=Product::first();
        $this->get('/')->assertOk()->assertSee('Sage Everyday Shirt')->assertSee(route('product.show', $p))->assertDontSee('/try-on/');
        $this->get('/products/'.$p->id)->assertOk()->assertSee($p->name)->assertSee($p->brand)
            ->assertSee($p->size)->assertSee($p->condition)->assertSee($p->description)
            ->assertSee('Rp 149.000')->assertSee('1 barang tersedia')->assertSee('Beli sekarang')
            ->assertDontSee('<video', false)->assertDontSee('tryon.js')->assertDontSee('Coba pakaian ini di AI Fitting');
        $this->get('/try-on/'.$p->id)->assertStatus(301)->assertRedirect(route('product.show', $p));
        $this->get('/checkout/'.$p->id)->assertOk()->assertSee(route('product.show', $p))->assertSee('Kembali ke detail produk');
    }
    public function test_unavailable_products_cannot_be_viewed_or_bought(): void {
        $p=$this->product();$p->update(['active'=>false]);
        $this->get('/products/'.$p->id)->assertNotFound();
        $this->get('/try-on/'.$p->id)->assertNotFound();
        $this->get('/checkout/'.$p->id)->assertNotFound();
        $this->get('/')->assertDontSee($p->name);
    }
    public function test_admin_can_upload_then_confirm_payment(): void {
        Storage::fake('public');
        $admin=User::factory()->create();$admin->forceFill(['is_admin'=>true])->save();
        $this->actingAs($admin)->post('/admin/products', ['name'=>'Uploaded Shirt','brand'=>'Brand','size'=>'M','condition'=>'Good','description'=>'Real shirt','price'=>150000,'photo'=>UploadedFile::fake()->image('photo.jpg')])->assertRedirect()->assertSessionHasNoErrors();
        $p=Product::firstOrFail();Storage::disk('public')->assertExists($p->photo);$this->assertNull($p->garment);
        $p->update(['active'=>false]);
        $o=Order::create($this->buyer()+['token'=>(string)\Illuminate\Support\Str::uuid(),'product_id'=>$p->id,'total'=>170000,'proof'=>'proofs/test.png','status'=>'review']);
        $this->post('/admin/orders/'.$o->id.'/status',['status'=>'paid'])->assertRedirect();
        $this->assertSame('paid',$o->fresh()->status);$this->assertFalse($p->fresh()->active);
        $this->get('/admin')->assertOk()->assertSee('Uploaded Shirt')->assertDontSee('name="garment"', false);
        $this->get('/orders/'.$o->token)->assertOk()->assertSee('Pembayaran dikonfirmasi');
    }
}
