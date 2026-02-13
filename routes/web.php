<?php

use App\Http\Controllers\Auth\AdminRegisterController;
use App\Livewire\Polls\Index as PollsIndex;
use App\Livewire\Polls\Show as PollsShow;
use App\Livewire\PollVote;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
})->name('home');

// throttle:60,1 is per IP by default
Route::livewire('polls/{poll:slug}', PollVote::class)
    ->middleware('throttle:60,1')
    ->name('polls.vote');

Route::post('poll-vote-test/{slug}', function ($slug) {
    $poll = \App\Models\Poll::where('slug', $slug)->first();
    if (! $poll) {
        return response()->json(['error' => 'Poll not found'], 404);
    }
    if (! $poll->isAcceptingVotes()) {
        return response()->json(['error' => 'Poll is not accepting votes'], 403);
    }
    $optionId = $poll->options()->select('id')->inRandomOrder()->first()->id;
    if (! $optionId) {
        return response()->json(['error' => 'Option ID is required'], 400);
    }
    $option = \App\Models\PollOption::query()
        ->where('id', $optionId)
        ->where('poll_id', $poll->id)
        ->first();
        
    if (! $option) {
        return response()->json(['error' => 'Invalid option for this poll'], 400);
    }
    // Get or create voter token
    $voterToken = request()->cookie('voter_token');
    if (! $voterToken || ! \Illuminate\Support\Str::isUuid($voterToken)) {
        $voterToken = \Illuminate\Support\Str::uuid()->toString();
        cookie()->queue('voter_token', $voterToken, 60 * 24 * 365);
    }
    try {
        \App\Models\Vote::create([
            'poll_id' => $poll->id,
            'poll_option_id' => $optionId,
            'user_id' => \Illuminate\Support\Facades\Auth::id(),
            'voter_token' => $voterToken,
            'ip_address' => request()->ip(),
        ]);

        \App\Events\VoteRecorded::dispatch(
            $poll->id,
            $poll->options()
                ->select('id')
                ->withCount('votes')
                ->get()
                ->map(fn ($opt) => [
                    'id' => $opt->id,
                    'votes_count' => $opt->votes_count,
                ])
                ->all()
        );

        return response()->json([
            'success' => 'Vote recorded successfully',
            'poll_id' => $poll->id,
            'option_id' => $optionId,
        ], 201);

    } catch (\Illuminate\Database\QueryException $e) {
        // Handle duplicate vote - update existing
        $message = $e->getMessage();
        $isDuplicate = str_contains($message, 'UNIQUE constraint')
            || str_contains($message, 'Duplicate entry')
            || str_contains($message, 'unique constraint');

        if ($isDuplicate) {
            \App\Models\Vote::where('poll_id', $poll->id)
                ->when(
                    \Illuminate\Support\Facades\Auth::check(),
                    fn ($query) => $query->where('user_id', \Illuminate\Support\Facades\Auth::id()),
                    fn ($query) => $query->where('voter_token', $voterToken)
                )
                ->update([
                    'poll_option_id' => $optionId,
                    'ip_address' => request()->ip(),
                ]);

            // Broadcast event
            \App\Events\VoteRecorded::dispatch(
                $poll->id,
                $poll->options()
                    ->select('id')
                    ->withCount('votes')
                    ->get()
                    ->map(fn ($opt) => [
                        'id' => $opt->id,
                        'votes_count' => $opt->votes_count,
                    ])
                    ->all()
            );

            return response()->json([
                'success' => 'Vote updated successfully',
                'poll_id' => $poll->id,
                'option_id' => $optionId,
            ], 200);
        }

        return response()->json(['error' => 'Database error occurred'], 500);
    }
});

// Admin auth routes (guest only)
Route::middleware('guest')->group(function () {
    Route::get('admin/login', fn () => view('livewire.auth.admin-login'))
        ->name('admin.login');
    Route::post('admin/login', [\Laravel\Fortify\Http\Controllers\AuthenticatedSessionController::class, 'store'])
        ->name('admin.login.store');
    Route::get('admin/register', [AdminRegisterController::class, 'create'])
        ->name('admin.register');
    Route::post('admin/register', [AdminRegisterController::class, 'store'])
        ->name('admin.register.store');
});

// Admin routes (requires authentication + admin role)
Route::middleware(['auth', 'verified', 'admin'])->prefix('admin')->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');
    Route::livewire('polls', PollsIndex::class)->name('polls.index');
    Route::livewire('polls/{poll:slug}', PollsShow::class)->name('polls.show');
});

// Authenticated user routes (non-admin)
Route::middleware(['auth', 'verified'])->group(function () {
    // Add user-specific authenticated routes here
});

require __DIR__.'/settings.php';
