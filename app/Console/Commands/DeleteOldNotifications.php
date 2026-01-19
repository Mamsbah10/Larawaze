<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class DeleteOldNotifications extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notifications:cleanup {--days=7 : Nombre de jours avant suppression}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Supprimer les notifications de plus de X jours';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $days = $this->option('days');
        
        $deleted = DB::table('notifications')
            ->where('created_at', '<', now()->subDays($days))
            ->delete();
        
        $this->info("✓ {$deleted} notifications supprimées (plus de {$days} jours)");
        
        return Command::SUCCESS;
    }
}
