<?php

namespace App\Console\Commands;

use App\Models\Cart;
use App\Models\Item;
use App\Models\Room;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;



class ReleaseExpiredReservations extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:release-expired-reservations';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */

    public function handle()
    {
        $expiredCarts = Cart::where('reserved_at', '<', Carbon::now()->subHours(24))->get();

        foreach ($expiredCarts as $cart) {
            if ($cart->item_id) {
                $item = Item::find($cart->item_id);
                if ($item) {
                    $availableStock = $item->count - $item->count_reserved;

                    if ($availableStock >= $cart->count) {
                        $item->count_reserved += $cart->count;
                        $item->save();
                    } else {
                        $item->count_reserved = max(0, $item->count_reserved - $cart->count);
                        $item->save();
                        Log::info("Insufficient stock to re-reserve Item ID: {$cart->item_id}. Reservation cancelled.");
                    }
                }
            }
            elseif ($cart->room_id) {
                $room = Room::with('items')->find($cart->room_id);
                if ($room) {
                    foreach ($room->items as $roomItem) {
                        $availableStock = $roomItem->count - $roomItem->count_reserved;

                        if ($availableStock >= $cart->count) {
                            $roomItem->count_reserved += $cart->count;
                            $roomItem->save();
                        } else {
                            $roomItem->count_reserved = max(0, $roomItem->count_reserved - $cart->count);
                            $roomItem->save();
                            Log::info("Insufficient stock to re-reserve Room Item ID: {$roomItem->id}. Reservation cancelled.");
                        }
                    }
                }
            }

            $newTime = 0;
            if ($cart->item_id) {
                $item = Item::find($cart->item_id);
                if ($item) {
                    $newTime = $item->time * $cart->count; 
                }
            } elseif ($cart->room_id) {
                $room = Room::with('items')->find($cart->room_id);
                if ($room) {
                    foreach ($room->items as $roomItem) {
                        $newTime += $roomItem->time * $cart->count;
                    }
                }
            }

            $cart->time = $newTime;
            $cart->save();
        }

        Log::info('Expired reservations released successfully and cart times updated!');
    }
}