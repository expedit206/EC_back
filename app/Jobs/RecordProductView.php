<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Redis;

class RecordProductView implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    protected $userId;
    protected $productId;

    public function __construct($userId, $productId)
    {
        $this->userId = $userId;
        $this->productId = $productId;
    }

    public function handle()
    {
        $redisKey = "view:user:{$this->userId}:product:{$this->productId}";
        if (Redis::get($redisKey) === null) {
            $productViewKey = "produit:views:{$this->productId}";
            Redis::incr($productViewKey);
            Redis::set($redisKey, 1);
            Redis::expire($redisKey, 86400); // Expire après 24h

            // Mise à jour de product_counts
            $this->syncViewsToDatabase();
        }
    }

    protected function syncViewsToDatabase()
    {
        $viewCount = Redis::get("produit:views:{$this->productId}");
        if ($viewCount && $viewCount > 0) {
            \App\Models\Produit::find($this->productId)->counts()->updateOrCreate(
                ['produit_id' => $this->productId],
                ['views_count' => $viewCount]
            );
            Redis::del("produit:views:{$this->productId}");
        }
    }
}