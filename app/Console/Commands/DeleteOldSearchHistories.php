<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\SearchHistory;
use Carbon\Carbon;

class DeleteOldSearchHistories extends Command
{
    protected $signature = 'search-history:cleanup';
    protected $description = 'Delete search history records older than 14 days';

    public function handle()
    {
        $twoWeeksAgo = Carbon::now()->subDays(14);
        $deleted = SearchHistory::where('created_at', '<', $twoWeeksAgo)->delete();
        
        $this->info("Deleted {$deleted} old search history records.");
    }
}
