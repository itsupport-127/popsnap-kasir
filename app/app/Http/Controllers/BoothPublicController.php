<?php

namespace App\Http\Controllers;

use App\Models\BoothSession;
use App\Services\Booth\BoothStorage;

class BoothPublicController extends Controller
{
    public function show(string $token)
    {
        $s = BoothSession::where('share_token', $token)->firstOrFail();
        $disk = BoothStorage::disk();
        $items = $s->media()->whereNull('deleted_at')->orderBy('kind')->get()->map(function ($m) use ($disk) {
            try {
                $url = BoothStorage::isS3()
                    ? $disk->temporaryUrl($m->path, now()->addHours(2))
                    : route('booth.file', [$m->session->share_token, $m->id]);
            } catch (\Throwable) {
                $url = route('booth.file', [$m->session->share_token, $m->id]);
            }

            return ['kind' => $m->kind, 'name' => $m->name, 'url' => $url, 'video' => str_ends_with(strtolower($m->name), '.mp4') || str_ends_with(strtolower($m->name), '.webm')];
        });

        return view('booth.share', ['session' => $s, 'items' => $items]);
    }

    /** Sajikan file dari disk lokal (kalau bukan S3). */
    public function file(string $token, int $media)
    {
        $s = BoothSession::where('share_token', $token)->firstOrFail();
        $m = $s->media()->whereNull('deleted_at')->findOrFail($media);

        return BoothStorage::disk()->download($m->path, $m->name);
    }
}
