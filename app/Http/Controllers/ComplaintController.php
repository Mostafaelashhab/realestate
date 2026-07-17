<?php

namespace App\Http\Controllers;

use App\Models\Complaint;
use App\Models\Train;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** الشكاوى كبوستات — مجتمع الركّاب. */
class ComplaintController extends Controller
{
    public function index(Request $request)
    {
        // نقاش قطر معيّن (لو جاي بفلتر ?train=رقم).
        $train = $request->filled('train')
            ? Train::where('number', trim((string) $request->query('train')))->first()
            : null;

        $complaints = Complaint::with(['user:id,name', 'train:id,number', 'fromStation:id,name_ar', 'toStation:id,name_ar'])
            ->withCount(['likers', 'comments'])
            ->when($request->filled('category'), fn ($q) => $q->where('category', $request->query('category')))
            ->when($train, fn ($q) => $q->where('train_id', $train->id))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        // البوستات اللي عملها المستخدم إعجاب (لتلوين القلب).
        $likedIds = $request->user()
            ? \Illuminate\Support\Facades\DB::table('complaint_likes')
                ->where('user_id', $request->user()->id)->pluck('complaint_id')->all()
            : [];

        // محطات لمحرّر بوست "سوق التذاكر".
        $stations = \App\Models\Station::orderBy('name_ar')->get(['id', 'name_ar']);

        return view('complaints.index', compact('complaints', 'likedIds', 'train', 'stations'));
    }

    public function show(Request $request, Complaint $complaint)
    {
        $complaint->load(['user:id,name', 'train:id,number', 'fromStation:id,name_ar', 'toStation:id,name_ar', 'comments.user:id,name'])->loadCount('likers');
        $liked = $request->user()
            ? $complaint->likers()->where('user_id', $request->user()->id)->exists()
            : false;

        return view('complaints.show', compact('complaint', 'liked'));
    }

    public function comment(Request $request, Complaint $complaint)
    {
        $data = $request->validate(['body' => ['required', 'string', 'min:1', 'max:600']]);

        $complaint->comments()->create([
            'user_id' => $request->user()->id,
            'body' => $data['body'],
        ]);

        return redirect()->route('complaints.show', $complaint)->withFragment('comments');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'body' => ['required', 'string', 'min:3', 'max:1000'],
            'category' => ['nullable', Rule::in(array_keys(Complaint::CATEGORIES))],
            'train_number' => ['nullable', 'string', 'max:20'],
            // حقول "سوق التذاكر" (مطلوبة فقط لو النوع سوق).
            'from_station_id' => ['nullable', 'required_if:category,ticket', 'exists:stations,id'],
            'to_station_id' => ['nullable', 'required_if:category,ticket', 'different:from_station_id', 'exists:stations,id'],
            'travel_date' => ['nullable', 'required_if:category,ticket', 'date_format:Y-m-d'],
            'price_egp' => ['nullable', 'integer', 'min:0', 'max:100000'],
            'contact' => ['nullable', 'required_if:category,ticket', 'string', 'max:60'],
        ]);

        $trainId = null;
        if (! empty($data['train_number'])) {
            $trainId = Train::where('number', trim($data['train_number']))->value('id');
        }

        $isTicket = ($data['category'] ?? 'general') === 'ticket';

        $post = Complaint::create([
            'user_id' => $request->user()->id,
            'train_id' => $trainId,
            'category' => $data['category'] ?? 'general',
            'body' => $data['body'],
            'from_station_id' => $isTicket ? ($data['from_station_id'] ?? null) : null,
            'to_station_id' => $isTicket ? ($data['to_station_id'] ?? null) : null,
            'travel_date' => $isTicket ? ($data['travel_date'] ?? null) : null,
            'price_egp' => $isTicket ? ($data['price_egp'] ?? null) : null,
            'contact' => $isTicket ? ($data['contact'] ?? null) : null,
        ]);

        // نبّه متابعي القطر بالبوست الجديد.
        if ($trainId && ($tr = Train::find($trainId))) {
            \App\Models\AppNotification::notifyTrainFollowers(
                $tr,
                "بوست جديد عن قطار {$tr->number}",
                \Illuminate\Support\Str::limit($post->body, 90),
                route('complaints.show', $post),
                $request->user()->id,
            );
        }

        if ($request->wantsJson()) {
            $post->load(['user:id,name', 'train:id,number', 'fromStation:id,name_ar', 'toStation:id,name_ar'])->loadCount(['likers', 'comments']);

            return response()->json(['ok' => true, 'post' => $this->toArray($post, [])]);
        }

        return redirect()->route('home')->with('ok', 'اتنشر بوستك، شكرًا لمشاركتك.');
    }

    /** واجهة JSON للفيد — للتحديث اللحظي والتمرير اللانهائي. */
    public function feed(Request $request)
    {
        $q = Complaint::with(['user:id,name', 'train:id,number', 'fromStation:id,name_ar', 'toStation:id,name_ar'])
            ->withCount(['likers', 'comments'])
            ->when($request->filled('category'), fn ($qq) => $qq->where('category', $request->query('category')));

        if ($request->filled('train')) {
            $t = Train::where('number', trim((string) $request->query('train')))->first();
            $q->where('train_id', $t?->id ?? 0);
        }

        if ($request->filled('after')) {
            $q->where('id', '>', (int) $request->query('after'));
        } elseif ($request->filled('before')) {
            $q->where('id', '<', (int) $request->query('before'));
        }

        $sort = $request->query('sort', 'new');
        $q->orderByDesc($sort === 'top' ? 'likers_count' : 'id')->orderByDesc('id');

        $posts = $q->take(20)->get();

        $likedIds = $request->user()
            ? \Illuminate\Support\Facades\DB::table('complaint_likes')
                ->where('user_id', $request->user()->id)
                ->whereIn('complaint_id', $posts->pluck('id'))->pluck('complaint_id')->all()
            : [];

        return response()->json(['posts' => $posts->map(fn ($c) => $this->toArray($c, $likedIds))->all()]);
    }

    /** تمثيل بوست كمصفوفة للـ JSON/JS. */
    private function toArray(Complaint $c, array $likedIds): array
    {
        return [
            'id' => $c->id,
            'user' => $c->user?->name ?? 'راكب',
            'initial' => mb_substr($c->user?->name ?? 'راكب', 0, 1),
            'user_url' => $c->user ? route('profile', $c->user) : null,
            'category' => $c->category,
            'category_label' => Complaint::CATEGORIES[$c->category] ?? 'عام',
            'body' => $c->body,
            'train_number' => $c->train?->number,
            'train_url' => $c->train ? route('complaints.index', ['train' => $c->train->number]) : null,
            'likes' => (int) $c->likers_count,
            'comments' => (int) $c->comments_count,
            'liked' => in_array($c->id, $likedIds),
            'ts' => $c->created_at->timestamp,
            'url' => route('complaints.show', $c),
            'like_url' => route('complaints.like', $c),
            // حقول سوق التذاكر (تظهر لو النوع ticket).
            'from' => $c->fromStation?->name_ar,
            'to' => $c->toStation?->name_ar,
            'travel_date' => $c->travel_date?->translatedFormat('l j F'),
            'price' => $c->price_egp,
            'wa' => $c->contact ? preg_replace('/\D/', '', $c->contact) : null,
        ];
    }

    public function like(Request $request, Complaint $complaint)
    {
        $user = $request->user();
        $existing = $complaint->likers()->where('user_id', $user->id)->exists();

        if ($existing) {
            $complaint->likers()->detach($user->id);
            $liked = false;
        } else {
            $complaint->likers()->attach($user->id);
            $liked = true;
        }

        $count = $complaint->likers()->count();

        if ($request->wantsJson()) {
            return response()->json(['ok' => true, 'liked' => $liked, 'count' => $count]);
        }

        return redirect()->route('complaints.index');
    }
}
