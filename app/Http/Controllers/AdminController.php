<?php
namespace App\Http\Controllers;
use App\Models\{Product, Order};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Auth, DB, Storage};
class AdminController extends Controller {
    public function login() { return view('login'); }
    public function authenticate(Request $request) {
        $data = $request->validate(['email'=>'required|email', 'password'=>'required|string']);
        if (!Auth::attempt($data + ['is_admin'=>true])) return back()->withErrors(['email'=>'Email atau kata sandi tidak sesuai.']);
        $request->session()->regenerate(); return redirect()->route('admin');
    }
    public function logout(Request $request) { Auth::logout(); $request->session()->invalidate(); $request->session()->regenerateToken(); return redirect('/'); }
    public function index() {
        $products = Product::latest()->get();
        return view('admin', [
            'products' => $products,
            'productsWithoutFashn' => $products->filter(fn ($product) => !$product->fashn_image),
            'orders' => Order::with('product')->latest()->paginate(20),
        ]);
    }
    public function store(Request $request) {
        $data = $request->validate(['name'=>'required|string|max:100', 'brand'=>'required|string|max:80', 'size'=>'required|in:XS,S,M,L,XL,XXL', 'condition'=>'required|string|max:80', 'description'=>'required|string|max:2000', 'price'=>'required|integer|min:1000|max:100000000', 'photo'=>'required|image|mimes:jpg,jpeg,png,webp|max:5120|dimensions:max_width=4096,max_height=4096']);
        $data['photo'] = $request->file('photo')->store('products', 'public');
        Product::create($data); return back()->with('success', 'Produk berhasil ditambahkan.');
    }
    public function destroy(Product $product) {
        DB::transaction(function () use ($product) {
            $locked = Product::whereKey($product->id)->lockForUpdate()->firstOrFail();
            $locked->update(['active' => false]);
            $locked->delete();
        });
        return redirect()->route('admin')->with('success', 'Produk berhasil dihapus dari katalog. Riwayat pesanan tetap tersimpan.');
    }
    public function status(Request $request, Order $order) {
        $data = $request->validate(['status'=>'required|in:paid,rejected,cancelled']);
        DB::transaction(function () use ($data, $order) {
            $product = Product::withTrashed()->whereKey($order->product_id)->lockForUpdate()->firstOrFail();
            $locked = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();
            abort_unless(in_array($locked->status, ['pending','review']), 409);
            abort_if($data['status'] === 'paid' && !$locked->proof, 422, 'Bukti pembayaran diperlukan.');
            $locked->update($data);
            if ($data['status'] !== 'paid' && !$product->trashed()) $product->update(['active'=>true]);
        });
        return back()->with('success', 'Status pembayaran diperbarui.');
    }
    public function fashn(Request $request, Product $product) {
        $data = $request->validate(['fashn_image'=>'required|image|mimes:jpg,jpeg,png,webp|max:5120|dimensions:min_width=256,min_height=256,max_width=4096,max_height=4096', 'fashn_photo_type'=>'required|in:flat-lay,model']);
        $path = $request->file('fashn_image')->store('fashn-products','public');
        abort_unless($path,500,'Foto gagal disimpan.');
        $product->update(['fashn_image'=>$path,'fashn_photo_type'=>$data['fashn_photo_type']]);
        return back()->with('success','Foto FASHN disimpan. Produk tersedia di AI Fitting.');
    }
    public function proof(Order $order) { abort_unless($order->proof, 404); return Storage::disk('local')->response($order->proof); }
}
