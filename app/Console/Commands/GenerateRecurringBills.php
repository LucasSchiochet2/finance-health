<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Bill;
use Carbon\Carbon;
use Illuminate\Support\Str;

class GenerateRecurringBills extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bills:generate-recurring';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate recurring bills for the next month to maintain 6-month buffer';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Find distinct recurring bill groups
        // We look for bills that are recurring.
        // We need to group by group_id to find the latest bill in each group.
        
        $groups = Bill::where('is_recurring', 1)
                      ->whereNotNull('group_id')
                      ->select('group_id')
                      ->distinct()
                      ->get();

        foreach ($groups as $group) {
            // Get the latest bill for this group
            $latestBill = Bill::where('group_id', $group->group_id)
                              ->orderBy('due_date', 'desc')
                              ->first();

            if (!$latestBill) {
                continue;
            }

            // Check if we need to add more bills to reach 6 months ahead
            // The requirement is "a cada mes adiciona mais um". 
            // So if today + 5 months > latest_due_date, add one more.
            // Or simpler: always ensure there is a bill > 6 months from now?
            
            // "Creating next 6 months" implies we always want to see 6 months ahead.
            // So if the latest bill is < 6 months from now, add new ones until it is >= 6 months from now.
            
            $targetDate = Carbon::now()->addMonths(5); // Ensure at least 5 months ahead, creating the 6th if needed to maintain "6 months" view approximately?
            // Actually, "proximos 6 meses" means users sees bills for +1, +2 .. +6 months.
            // If I created +5 months (total 6 bills), the last one is +5 months from now.
            // So latest_due_date = now + 5 months.
            // Requirement: "a cada mes adiciona mais um". This implies when one month passes, add one.
            // So we want to maintain the buffer at 5 months ahead (6 total bills)?
            // Or buffer at 6 months ahead (7 total bills)?
            // Let's assume buffer of 5 months ahead (finding bills due in month 6).
            // So if latest < now + 5 months, add one.

            $targetDate = Carbon::now()->addMonths(5);

            // If the latest bill is before target date, we need to add bills
            if (Carbon::parse($latestBill->due_date)->lt($targetDate)) {
                 $nextDueDate = Carbon::parse($latestBill->due_date)->addMonth();
                 
                 // Create the new bill
                 $newBill = $latestBill->replicate();
                 $newBill->due_date = $nextDueDate;
                 $newBill->paid = false; // Ensure new bill is unpaid
                 $newBill->save();
                 
                 $this->info("Generated bill for group {$group->group_id} due on {$nextDueDate->format('Y-m-d')}");
            }
        }
    }
}
