<?php

namespace App\Http\Controllers;

use App\Enums\OfferStatus;
use App\Models\Activity;
use App\Models\Offer;
use Illuminate\Http\Request;

/** Candidate-facing offer letter: review, draw a signature and accept, or decline. */
class OfferSigningController extends Controller
{
    private function offer(string $token): Offer
    {
        return Offer::with(['application.candidate', 'application.jobOpening.location'])->where('token', $token)->firstOrFail();
    }

    public function show(Request $request, string $token)
    {
        $offer = $this->offer($token);

        return view('public.offer', ['offer' => $offer, 'print' => $request->boolean('print')]);
    }

    public function sign(Request $request, string $token)
    {
        $offer = $this->offer($token);
        abort_unless(in_array($offer->status, [OfferStatus::Sent, OfferStatus::Approved], true), 403, 'This offer can no longer be signed.');

        $data = $request->validate([
            'signed_name' => ['required', 'string', 'max:120'],
            'signature' => ['required', 'string', 'starts_with:data:image/png;base64,', 'max:400000'],
            'agree' => ['accepted'],
        ], ['signature.required' => 'Please draw your signature.', 'agree.accepted' => 'Please confirm you accept the offer.']);

        $offer->update([
            'status' => OfferStatus::Signed,
            'signed_name' => $data['signed_name'],
            'signature' => $data['signature'],
            'signed_at' => now(),
        ]);

        Activity::log("Offer signed by {$data['signed_name']}", $offer->application, 'offer');

        return redirect()->route('offers.show', $token)->with('status', 'signed');
    }

    public function decline(string $token)
    {
        $offer = $this->offer($token);
        abort_unless(in_array($offer->status, [OfferStatus::Sent, OfferStatus::Approved], true), 403);

        $offer->update(['status' => OfferStatus::Declined]);
        Activity::log('Offer declined by candidate', $offer->application, 'offer');

        return redirect()->route('offers.show', $token)->with('status', 'declined');
    }
}
