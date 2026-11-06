<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('shop:admin {email}', function () {
    $email = $this->argument('email');
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) { $this->error('Email tidak valid.'); return 1; }
    if (\App\Models\User::where('email', $email)->exists()) { $this->error('Email sudah terdaftar.'); return 1; }
    $password = $this->secret('Kata sandi admin (minimal 12 karakter)');
    if (strlen($password ?? '') < 12) { $this->error('Minimal 12 karakter.'); return 1; }
    $user = new \App\Models\User;
    $user->name = 'Admin Rewear'; $user->email = $email;
    $user->password = \Illuminate\Support\Facades\Hash::make($password); $user->is_admin = true; $user->save();
    $this->info('Admin berhasil dibuat. Masuk melalui /admin/login.');
})->purpose('Buat admin Rewear tanpa kata sandi bawaan');

Artisan::command('fashn:prune', function () {
    \App\Models\TryonRun::where(function ($q) {
        $q->where(fn ($q) => $q->where('status','processing')->where('updated_at','<',now()->subMinutes(20)))
          ->orWhere(fn ($q) => $q->where('status','queued')->where('updated_at','<',now()->subHour()));
    })->update(['status'=>'failed','error'=>'Proses terhenti. Jalankan layanan AI lalu coba kembali.','updated_at'=>now()]);
    $count = 0;
    foreach (\App\Models\TryonRun::where('expires_at','<=',now())->cursor() as $run) {
        \Illuminate\Support\Facades\Storage::disk('local')->deleteDirectory('tryon/'.$run->id);
        $run->delete(); $count++;
    }
    $this->info("Removed $count expired try-on runs.");
});
Schedule::command('fashn:prune')->everyMinute()->withoutOverlapping();
