<?php

namespace Modules\Cart\Console;

use Illuminate\Console\Command;
use Modules\Cart\Services\AbandonedCartService;

class DetectAbandonedCartsCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'cart:detect-abandoned {--hours=1 : Inactivity threshold in hours}';

    /**
     * The console command description.
     */
    protected $description = 'Scan shopping carts and record newly abandoned carts for recovery';

    /**
     * Execute the console command.
     */
    public function handle(AbandonedCartService $service): int
    {
        $hours = (int) $this->option('hours');
        $this->info("Scanning carts inactive for >= {$hours} hours...");

        $detected = $service->detectAbandonedCarts($hours);

        $this->info("Scan complete. {$detected} newly abandoned carts identified and queued for recovery.");

        return Command::SUCCESS;
    }
}
