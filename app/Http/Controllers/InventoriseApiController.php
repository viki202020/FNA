<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Support\InventoriseLink;

/**
 * The door Inventorise knocks on. POST /api/inventorise with a JSON body and two headers:
 *   X-Timestamp  seconds since 1970 (refused if more than 5 minutes off)
 *   X-Signature  hex HMAC-SHA256 of "<timestamp>.<raw body>" with the shared secret (INVENTORISE_SECRET in .env)
 * The company is INVENTORISE_MERCHANT_ID in .env. Anything unsigned or wrongly signed is refused.
 */
class InventoriseApiController extends Controller
{
    public function receive(Request $request)
    {
        $secret = (string) config('services.inventorise.secret');
        $merchant = (int) config('services.inventorise.merchant');
        $ts = (string) $request->header('X-Timestamp');
        $given = (string) $request->header('X-Signature');
        if ($secret === '' || $merchant < 1 || !ctype_digit($ts) || abs(time() - (int) $ts) > 300
            || !hash_equals(hash_hmac('sha256', $ts . '.' . $request->getContent(), $secret), $given)) {
            return response()->json(['message' => 'Not accepted.'], 403);
        }
        $p = $request->json()->all();
        if (!in_array($p['type'] ?? '', InventoriseLink::TYPES, true) || trim((string) ($p['ref'] ?? '')) === '' || mb_strlen((string) $p['ref']) > 60) {
            return response()->json(['message' => 'type and ref are required.'], 422);
        }
        if (!is_array($p['lines'] ?? null) || count($p['lines']) > 500 || !is_array($p['party'] ?? null)) {
            return response()->json(['message' => 'party and lines are required.'], 422);
        }
        $row = InventoriseLink::receive($merchant, $p);
        return response()->json(['status' => $row->status, 'note' => $row->note]);
    }
}
