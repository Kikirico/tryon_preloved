<?php
namespace App\Http\Controllers;
use App\Models\{Product, Order};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{DB, Storage};
use Illuminate\Support\Str;
class ShopController extends Controller {
    public function index(Request $request) {
        $search = mb_substr($request->string('q')->toString(), 0, 100);
        $products = Product::where('active', true)->when($search, fn ($q) => $q->where('name', 'like', '%'.$search.'%'))->latest()->get();
        return view('shop', compact('products', 'search'));
    }
    public function show(Product $product) {
        abort_unless($product->active, 404);
        $products = Product::where('active', true)->where('id', '!=', $product->id)->latest()->limit(4)->get();
        return view('product', compact('product', 'products'));
    }
    public function legacyProduct(Product $product) {
        abort_unless($product->active, 404);
        return redirect()->route('product.show', $product, 301);
    }
    public function checkout(Product $product) { abort_unless($product->active, 404); return view('checkout', compact('product')); }
    public function order(Request $request, Product $product) {
        abort_unless(config('shop.bank_account'), 503, 'Rekening pembayaran belum dikonfigurasi.');
        $data = $request->validate(['name'=>'required|string|max:100', 'email'=>'required|email|max:150', 'phone'=>'required|string|max:30', 'address'=>'required|string|max:1000']);
        $order = DB::transaction(function () use ($product, $data) {
            $locked = Product::whereKey($product->id)->lockForUpdate()->firstOrFail();
            abort_unless($locked->active, 409, 'Produk ini sudah dipesan.');
            $locked->update(['active'=>false]);
            return Order::create($data + ['token'=>(string) Str::uuid(), 'product_id'=>$locked->id, 'total'=>$locked->price + config('shop.shipping')]);
        });
        return redirect()->route('receipt', $order->token);
    }
    public function receipt(Order $order) { return view('receipt', compact('order')); }
    public function proof(Request $request, Order $order) {
        $request->validate(['proof'=>'required|image|mimes:jpg,jpeg,png,webp|max:5120']);
        $path = $request->file('proof')->store('proofs', 'local');
        try {
            DB::transaction(function () use ($order, $path) {
                $locked = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();
                abort_unless($locked->status === 'pending', 409, 'Pesanan tidak menerima bukti pembayaran baru.');
                $locked->update(['proof'=>$path, 'status'=>'review']);
            });
        } catch (\Throwable $e) { Storage::disk('local')->delete($path); throw $e; }
        return back()->with('success', 'Bukti diterima. Admin akan memverifikasi pembayaran Anda.');
    }
}
