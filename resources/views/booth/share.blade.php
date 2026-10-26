<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Foto Photobox Kamu</title>
<style>
 body{margin:0;font-family:system-ui,sans-serif;background:#fff7f0;color:#3b2a20}
 main{max-width:720px;margin:0 auto;padding:24px 16px 48px}
 h1{font-size:22px;margin:0 0 4px} p{margin:0 0 20px;color:#7a6556}
 .grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:14px}
 .card{background:#fff;border-radius:14px;padding:10px;box-shadow:0 2px 10px #0001}
 .card img,.card video{width:100%;border-radius:8px;display:block}
 .card a{display:block;margin-top:8px;text-align:center;padding:8px;border-radius:999px;background:#ff7a2f;color:#fff;text-decoration:none;font-weight:600;font-size:14px}
 .empty{padding:30px;text-align:center;background:#fff;border-radius:14px}
</style>
</head>
<body><main>
<h1>Foto &amp; video sesi kamu 🎉</h1>
<p>Simpan segera — link ini aktif beberapa hari saja.</p>
@if($items->isEmpty())
 <div class="empty">File sedang diproses/diunggah. Coba lagi beberapa menit lagi.</div>
@else
 <div class="grid">
 @foreach($items as $i)
  <div class="card">
   @if($i['video'])<video src="{{ $i['url'] }}" controls playsinline></video>
   @else<img src="{{ $i['url'] }}" alt="{{ $i['name'] }}" loading="lazy">@endif
   <a href="{{ $i['url'] }}" download="{{ $i['name'] }}">Unduh</a>
  </div>
 @endforeach
 </div>
@endif
</main></body></html>
