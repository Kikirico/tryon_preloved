<?php

namespace Tests\Feature;

use App\Jobs\GenerateTryon;
use App\Models\{Order, Product, TryonRun, User};
use App\Services\FashnClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\{Http, Storage};
use Illuminate\Support\Str;
use Tests\TestCase;

class AdminProductTest extends TestCase {
    use RefreshDatabase;

    private function admin(): User {
        $user = User::factory()->create();
        $user->forceFill(['is_admin' => true])->save();
        return $user;
    }

    private function product(array $attributes = []): Product {
        return Product::create(array_merge([
            'name' => 'Atasan pilihan', 'brand' => 'Brand', 'size' => 'M',
            'condition' => 'Like new', 'description' => 'Deskripsi atasan',
            'price' => 100000, 'photo' => 'products/shirt.jpg',
        ], $attributes));
    }

    public function test_only_admin_can_delete_products(): void {
        $product = $this->product();
        $url = route('admin.products.destroy', $product);
        $this->delete($url)->assertRedirect('/admin/login');
        $this->actingAs(User::factory()->create())->delete($url)->assertForbidden();
        $this->assertNotSoftDeleted($product);
    }

    public function test_deleted_product_disappears_from_inventory_catalog_and_fitting(): void {
        Storage::fake('public');
        $product = $this->product(['fashn_image' => 'fashn-products/shirt.png']);
        Storage::disk('public')->put($product->photo, 'catalog image');
        Storage::disk('public')->put($product->fashn_image, 'garment image');

        $this->actingAs($this->admin())->delete(route('admin.products.destroy', $product))->assertRedirect('/admin');
        $this->assertSoftDeleted($product);
        $this->assertFalse($product->fresh()->active);
        $this->get('/')->assertOk()->assertDontSee($product->name);
        $this->get('/admin')->assertOk()->assertDontSee($product->name);
        $this->get('/ai-fitting')->assertOk()->assertDontSee($product->name);
        $this->get('/products/'.$product->id)->assertNotFound();
        $this->get('/checkout/'.$product->id)->assertNotFound();
        $this->delete('/admin/products/'.$product->id)->assertNotFound();
        Storage::disk('public')->assertExists([$product->photo, $product->fashn_image]);
    }

    public function test_order_history_survives_deletion_and_cancellation_does_not_restore_product(): void {
        $product = $this->product(['active' => false]);
        $order = Order::create([
            'product_id' => $product->id, 'token' => (string) Str::uuid(),
            'name' => 'Buyer', 'email' => 'buyer@example.com', 'phone' => '08123456789',
            'address' => 'Jalan Test', 'total' => 120000, 'status' => 'review', 'proof' => 'proofs/test.png',
        ]);
        $this->actingAs($this->admin())->delete('/admin/products/'.$product->id)->assertRedirect();
        $this->get('/orders/'.$order->token)->assertOk()->assertSee($product->name);
        $this->get('/admin')->assertOk()->assertSee($product->name);
        $this->post('/admin/orders/'.$order->id.'/status', ['status' => 'cancelled'])->assertRedirect();
        $this->assertSame('cancelled', $order->fresh()->status);
        $this->assertSoftDeleted($product);
        $this->assertFalse($product->fresh()->active);
        $this->get('/')->assertDontSee($product->name);
    }

    public function test_queued_fitting_can_finish_after_product_is_deleted(): void {
        Storage::fake('local');
        Storage::fake('public');
        $product = $this->product(['fashn_image' => 'fashn-products/shirt.png']);
        $run = TryonRun::create([
            'id' => (string) Str::uuid(), 'product_id' => $product->id,
            'owner_hash' => hash('sha256', 'owner'), 'person_path' => 'tryon/person.png',
            'status' => 'queued', 'expires_at' => now()->addDay(),
        ]);
        Storage::disk('local')->put($run->person_path, 'person image');
        Storage::disk('public')->put($product->fashn_image, 'garment image');
        $this->actingAs($this->admin())->delete('/admin/products/'.$product->id)->assertRedirect();

        $png = "\x89PNG\r\n\x1a\nexample";
        Http::fake(['*' => Http::response($png, 200, ['Content-Type' => 'image/png'])]);
        $client = $this->mock(FashnClient::class);
        $client->shouldReceive('request')->andReturn(Http::baseUrl('http://127.0.0.1:8001'));
        (new GenerateTryon($run->id))->handle($client);

        $this->assertSame('completed', $run->fresh()->status);
        $this->withSession(['fashn_owner' => 'owner'])->get('/ai-fitting/runs/'.$run->id.'/images/result')
            ->assertOk()->assertStreamedContent($png);
    }

    public function test_fashn_upload_forms_only_show_products_missing_a_photo(): void {
        $ready = $this->product(['name' => 'Sudah lengkap', 'fashn_image' => 'fashn-products/ready.png']);
        $missing = $this->product(['name' => 'Belum lengkap']);
        $this->actingAs($this->admin())->get('/admin')->assertOk()
            ->assertSee('/admin/products/'.$missing->id.'/fashn')
            ->assertDontSee('/admin/products/'.$ready->id.'/fashn')
            ->assertSee('Hapus produk Sudah lengkap')->assertSee('Hapus produk Belum lengkap');
    }

    public function test_fashn_form_disappears_after_upload_and_other_missing_products_remain(): void {
        Storage::fake('public');
        $product = $this->product();
        $other = $this->product(['name' => 'Masih perlu foto']);
        $this->actingAs($this->admin())->from('/admin')->post('/admin/products/'.$product->id.'/fashn', [
            'fashn_photo_type' => 'flat-lay',
            'fashn_image' => UploadedFile::fake()->image('shirt.jpg', 600, 800),
        ])->assertRedirect('/admin')->assertSessionHasNoErrors();
        Storage::disk('public')->assertExists($product->fresh()->fashn_image);
        $this->get('/admin')->assertOk()->assertDontSee('/admin/products/'.$product->id.'/fashn')
            ->assertSee('/admin/products/'.$other->id.'/fashn');
    }

    public function test_fashn_section_is_hidden_when_every_product_has_a_photo(): void {
        $this->product(['fashn_image' => 'fashn-products/ready.png']);
        $this->actingAs($this->admin())->get('/admin')->assertOk()
            ->assertDontSee('<h2>Foto produk untuk FASHN</h2>', false)
            ->assertDontSee('name="fashn_image"', false);
    }
}
