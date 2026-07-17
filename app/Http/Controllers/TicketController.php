<?php

namespace App\Http\Controllers;

use App\Models\Station;
use App\Models\TicketListing;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** سوق تبادل وبيع التذاكر بين الركّاب. */
class TicketController extends Controller
{
    public function index(Request $request)
    {
        $listings = TicketListing::with(['user:id,name', 'fromStation:id,name_ar', 'toStation:id,name_ar'])
            ->where('status', 'active')
            ->when($request->filled('kind'), fn ($q) => $q->where('kind', $request->string('kind')))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $stations = Station::orderBy('name_ar')->get(['id', 'name_ar']);
        $mine = $request->user()
            ? TicketListing::where('user_id', $request->user()->id)->where('status', 'active')->count()
            : 0;

        return view('tickets.index', compact('listings', 'stations', 'mine'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'from_station_id' => ['required', 'exists:stations,id'],
            'to_station_id' => ['required', 'different:from_station_id', 'exists:stations,id'],
            'travel_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            'train_number' => ['nullable', 'string', 'max:20'],
            'class_ar' => ['nullable', 'string', 'max:60'],
            'seats' => ['required', 'integer', 'min:1', 'max:10'],
            'price_egp' => ['nullable', 'integer', 'min:0', 'max:100000'],
            'kind' => ['required', Rule::in(array_keys(TicketListing::KINDS))],
            'contact' => ['required', 'string', 'max:60'],
            'note' => ['nullable', 'string', 'max:300'],
        ]);

        $data['user_id'] = $request->user()->id;
        $data['status'] = 'active';
        TicketListing::create($data);

        return redirect()->route('tickets.index')->with('ok', 'اتنشر إعلانك، هيوصل لباقي الركّاب.');
    }

    public function close(Request $request, TicketListing $listing)
    {
        abort_unless($listing->user_id === $request->user()->id, 403);
        $listing->update(['status' => 'closed']);

        return redirect()->route('tickets.index')->with('ok', 'اتقفل الإعلان.');
    }
}
