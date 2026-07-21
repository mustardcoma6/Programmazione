<?php

namespace App\Console\Commands;

use App\Models\Auction;
use App\Models\LeagueParticipant;
use App\Models\Roster;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CloseExpiredAuctions extends Command
{
    protected $signature = 'market:close-expired-auctions';

    protected $description = 'Chiude automaticamente le aste scadute';

    public function handle(): int
    {
        $auctionIds = Auction::where('is_finished', false)
            ->where('expires_at', '<=', now())
            ->pluck('id');

        foreach ($auctionIds as $auctionId) {
            DB::transaction(function () use ($auctionId) {
                $auction = Auction::lockForUpdate()->find($auctionId);

                if (!$auction || $auction->is_finished || $auction->expires_at->isFuture()) {
                    return;
                }

                $winner = LeagueParticipant::where('league_id', $auction->league_id)
                    ->where('user_id', $auction->user_id)
                    ->first();

                if (!$winner) {
                    $auction->update(['is_finished' => true]);
                    return;
                }

                Roster::create([
                    'league_id' => $auction->league_id,
                    'user_id' => $auction->user_id,
                    'real_player_id' => $auction->real_player_id,
                    'purchase_price' => $auction->current_bid,
                    'contract_years' => 1,
                ]);

                $winner->decrement('remaining_budget', $auction->current_bid);
                $winner->decrement('years_budget', 1);

                $auction->update(['is_finished' => true]);
            });
        }

        return Command::SUCCESS;
    }
}