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

        $complaints = Complaint::with(['user:id,name', 'train:id,number', 'fromStation:id,name_ar', 'toStation:id,name_ar', 'pollVotes'])
            ->withCount([
                'comments',
                'likers as likes_count' => fn ($q) => $q->where('complaint_likes.value', 1),
                'likers as dislikes_count' => fn ($q) => $q->where('complaint_likes.value', -1),
            ])
            ->when($request->filled('category'), fn ($q) => $q->where('category', $request->query('category')))
            ->when($train, fn ($q) => $q->where('train_id', $train->id))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        // تفاعل المستخدم لكل بوست (1 إعجاب · -1 عدم إعجاب).
        $myReactions = $request->user()
            ? \Illuminate\Support\Facades\DB::table('complaint_likes')
                ->where('user_id', $request->user()->id)->pluck('value', 'complaint_id')->all()
            : [];

        // محطات لمحرّر بوست "سوق التذاكر".
        $stations = \App\Models\Station::orderBy('name_ar')->get(['id', 'name_ar']);

        return view('complaints.index', compact('complaints', 'myReactions', 'train', 'stations'));
    }

    public function show(Request $request, Complaint $complaint)
    {
        $complaint->load(['user:id,name', 'train:id,number', 'fromStation:id,name_ar', 'toStation:id,name_ar', 'pollVotes', 'comments.user:id,name']);
        $base = \Illuminate\Support\Facades\DB::table('complaint_likes')->where('complaint_id', $complaint->id);
        $likes = (clone $base)->where('value', 1)->count();
        $dislikes = (clone $base)->where('value', -1)->count();
        $myReact = $request->user() ? (int) ((clone $base)->where('user_id', $request->user()->id)->value('value') ?? 0) : 0;
        $poll = $this->pollData($complaint, $request->user()?->id);

        return view('complaints.show', compact('complaint', 'likes', 'dislikes', 'myReact', 'poll'));
    }

    /** تعليقات بوست (JSON) للعرض inline في الفيد. */
    public function comments(Complaint $complaint)
    {
        $comments = $complaint->comments()
            ->whereNull('parent_id')
            ->with(['user:id,name', 'replies.user:id,name'])
            ->latest()->get();

        return response()->json(['comments' => $comments->map(fn ($cm) => $this->commentArray($cm, true))->all()]);
    }

    public function comment(Request $request, Complaint $complaint)
    {
        $data = $request->validate([
            'body' => ['required', 'string', 'min:1', 'max:600'],
            'parent_id' => ['nullable', 'integer', 'exists:complaint_comments,id'],
        ]);

        $parentId = null;
        if (! empty($data['parent_id'])) {
            $parent = \App\Models\ComplaintComment::where('id', $data['parent_id'])->where('complaint_id', $complaint->id)->first();
            // مستوى ردود واحد: الرد على ردّ يُعلّق على التعليق الأصلي.
            $parentId = $parent ? ($parent->parent_id ?? $parent->id) : null;
        }

        $cm = $complaint->comments()->create([
            'user_id' => $request->user()->id,
            'parent_id' => $parentId,
            'body' => $data['body'],
        ]);
        $cm->load('user:id,name');

        // إشعار: صاحب التعليق (عند الردّ) أو صاحب البوست (عند التعليق).
        $actorId = $request->user()->id;
        if ($parentId) {
            $parentAuthor = \App\Models\ComplaintComment::where('id', $parentId)->value('user_id');
            if ($parentAuthor && $parentAuthor !== $actorId) {
                \App\Models\AppNotification::notify($parentAuthor, 'حد ردّ على تعليقك', \Illuminate\Support\Str::limit($cm->body, 80), route('complaints.show', $complaint));
            }
        } elseif ($complaint->user_id && $complaint->user_id !== $actorId) {
            \App\Models\AppNotification::notify($complaint->user_id, 'حد علّق على بوستك', \Illuminate\Support\Str::limit($cm->body, 80), route('complaints.show', $complaint));
        }

        if ($request->wantsJson()) {
            return response()->json(['ok' => true, 'comment' => $this->commentArray($cm), 'parent_id' => $parentId]);
        }

        return redirect()->route('complaints.show', $complaint)->withFragment('comments');
    }

    /** إبلاغ عن بوست أو تعليق. */
    public function report(Request $request)
    {
        $data = $request->validate([
            'complaint_id' => ['nullable', 'exists:complaints,id'],
            'comment_id' => ['nullable', 'exists:complaint_comments,id'],
            'reason' => ['nullable', 'string', 'max:200'],
        ]);

        abort_if(empty($data['complaint_id']) && empty($data['comment_id']), 422);

        \App\Models\ContentReport::create([
            'user_id' => $request->user()?->id,
            'complaint_id' => $data['complaint_id'] ?? null,
            'comment_id' => $data['comment_id'] ?? null,
            'reason' => $data['reason'] ?? null,
        ]);

        return response()->json(['ok' => true]);
    }

    /** تمثيل تعليق كمصفوفة للـ JSON. */
    private function commentArray(\App\Models\ComplaintComment $cm, bool $withReplies = false): array
    {
        $name = $cm->user?->name ?? 'راكب';
        $a = [
            'id' => $cm->id,
            'user' => $name,
            'initial' => mb_substr($name, 0, 1),
            'user_url' => $cm->user ? route('profile', $cm->user) : null,
            'body' => $cm->body,
            'ago' => $cm->created_at->diffForHumans(),
        ];
        if ($withReplies) {
            $a['replies'] = $cm->replies->map(fn ($r) => $this->commentArray($r))->all();
        }

        return $a;
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'body' => ['required', 'string', 'min:3', 'max:1000'],
            'category' => ['nullable', Rule::in(array_keys(Complaint::CATEGORIES))],
            'train_number' => ['nullable', 'string', 'max:20'],
            'anonymous' => ['nullable', 'boolean'],
            // حقول "سوق التذاكر".
            'from_station_id' => ['nullable', 'required_if:category,ticket', 'exists:stations,id'],
            'to_station_id' => ['nullable', 'required_if:category,ticket', 'different:from_station_id', 'exists:stations,id'],
            'travel_date' => ['nullable', 'required_if:category,ticket', 'date_format:Y-m-d'],
            'price_egp' => ['nullable', 'integer', 'min:0', 'max:100000'],
            'contact' => ['nullable', 'required_if:category,ticket', 'string', 'max:60'],
            // خيارات الاستطلاع.
            'poll_options' => ['nullable', 'array', 'max:4'],
            'poll_options.*' => ['nullable', 'string', 'max:80'],
        ]);

        $category = $data['category'] ?? 'general';
        $isTicket = $category === 'ticket';
        $isPoll = $category === 'poll';

        $pollOptions = null;
        if ($isPoll) {
            $pollOptions = collect($data['poll_options'] ?? [])
                ->map(fn ($o) => trim((string) $o))->filter()->take(4)->values()->all();
            if (count($pollOptions) < 2) {
                $msg = 'الاستطلاع لازم يكون فيه خيارين على الأقل.';

                return $request->wantsJson()
                    ? response()->json(['ok' => false, 'message' => $msg], 422)
                    : back()->withErrors(['poll_options' => $msg]);
            }
        }

        $trainId = null;
        if (! empty($data['train_number'])) {
            $trainId = Train::where('number', trim($data['train_number']))->value('id');
        }

        $post = Complaint::create([
            'user_id' => $request->user()->id,
            'anonymous' => (bool) ($data['anonymous'] ?? false),
            'train_id' => $trainId,
            'category' => $category,
            'body' => $data['body'],
            'poll_options' => $pollOptions,
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
            $post->load(['user:id,name', 'train:id,number', 'fromStation:id,name_ar', 'toStation:id,name_ar', 'pollVotes'])->loadCount(['likers', 'comments']);

            return response()->json(['ok' => true, 'post' => $this->toArray($post, [])]);
        }

        return redirect()->route('home')->with('ok', 'اتنشر بوستك، شكرًا لمشاركتك.');
    }

    /** واجهة JSON للفيد — للتحديث اللحظي والتمرير اللانهائي. */
    public function feed(Request $request)
    {
        $q = Complaint::with(['user:id,name', 'train:id,number', 'fromStation:id,name_ar', 'toStation:id,name_ar', 'pollVotes'])
            ->withCount([
                'comments',
                'likers as likes_count' => fn ($qq) => $qq->where('complaint_likes.value', 1),
                'likers as dislikes_count' => fn ($qq) => $qq->where('complaint_likes.value', -1),
            ])
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
        $q->orderByDesc($sort === 'top' ? 'likes_count' : 'id')->orderByDesc('id');

        $posts = $q->take(20)->get();

        $myReactions = $request->user()
            ? \Illuminate\Support\Facades\DB::table('complaint_likes')
                ->where('user_id', $request->user()->id)
                ->whereIn('complaint_id', $posts->pluck('id'))->pluck('value', 'complaint_id')->all()
            : [];

        return response()->json(['posts' => $posts->map(fn ($c) => $this->toArray($c, $myReactions))->all()]);
    }

    /** تصويت في استطلاع. */
    public function vote(Request $request, Complaint $complaint)
    {
        abort_unless($complaint->category === 'poll' && is_array($complaint->poll_options), 404);

        $data = $request->validate([
            'choice' => ['required', 'integer', 'min:0', 'max:'.(count($complaint->poll_options) - 1)],
        ]);

        \App\Models\PollVote::updateOrCreate(
            ['complaint_id' => $complaint->id, 'user_id' => $request->user()->id],
            ['choice' => $data['choice']],
        );

        return response()->json([
            'ok' => true,
            'poll' => $this->pollData($complaint->load('pollVotes'), $request->user()->id),
        ]);
    }

    /** بيانات الاستطلاع (الخيارات + الأصوات + صوت المستخدم). */
    private function pollData(Complaint $c, ?int $uid): ?array
    {
        if ($c->category !== 'poll' || ! is_array($c->poll_options)) {
            return null;
        }
        $votes = $c->relationLoaded('pollVotes') ? $c->pollVotes : $c->pollVotes()->get();
        $options = [];
        foreach ($c->poll_options as $i => $text) {
            $options[] = ['text' => $text, 'votes' => $votes->where('choice', $i)->count()];
        }

        return [
            'options' => $options,
            'total' => $votes->count(),
            'my_vote' => $uid ? optional($votes->firstWhere('user_id', $uid))->choice : null,
            'vote_url' => route('complaints.vote', $c),
        ];
    }

    /** تمثيل بوست كمصفوفة للـ JSON/JS. */
    private function toArray(Complaint $c, array $myReactions): array
    {
        $anon = (bool) $c->anonymous;
        $name = $anon ? 'راكب مجهول' : ($c->user?->name ?? 'راكب');

        return [
            'id' => $c->id,
            'user' => $name,
            'initial' => $anon ? '؟' : mb_substr($name, 0, 1),
            'user_url' => (! $anon && $c->user) ? route('profile', $c->user) : null,
            'anonymous' => $anon,
            'poll' => $this->pollData($c, auth()->id()),
            'category' => $c->category,
            'category_label' => Complaint::CATEGORIES[$c->category] ?? 'عام',
            'body' => $c->body,
            'train_number' => $c->train?->number,
            'train_url' => $c->train ? route('complaints.index', ['train' => $c->train->number]) : null,
            'likes' => (int) ($c->likes_count ?? 0),
            'dislikes' => (int) ($c->dislikes_count ?? 0),
            'my_reaction' => (int) ($myReactions[$c->id] ?? 0),
            'comments' => (int) $c->comments_count,
            'ts' => $c->created_at->timestamp,
            'url' => route('complaints.show', $c),
            'like_url' => route('complaints.like', $c),
            'comments_url' => route('complaints.comments', $c),
            'comment_url' => route('complaints.comment', $c),
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
        $value = (int) $request->input('value', 1) === -1 ? -1 : 1;
        $uid = $request->user()->id;
        $db = \Illuminate\Support\Facades\DB::table('complaint_likes')
            ->where('complaint_id', $complaint->id)->where('user_id', $uid);

        $existing = $db->first();
        if ($existing && (int) $existing->value === $value) {
            $db->delete(); // نفس التفاعل → إلغاء
            $my = 0;
        } else {
            \Illuminate\Support\Facades\DB::table('complaint_likes')->updateOrInsert(
                ['complaint_id' => $complaint->id, 'user_id' => $uid],
                ['value' => $value, 'updated_at' => now(), 'created_at' => $existing->created_at ?? now()],
            );
            $my = $value;
        }

        $base = \Illuminate\Support\Facades\DB::table('complaint_likes')->where('complaint_id', $complaint->id);

        return response()->json([
            'ok' => true,
            'my' => $my,
            'likes' => (clone $base)->where('value', 1)->count(),
            'dislikes' => (clone $base)->where('value', -1)->count(),
        ]);
    }
}
