<?php

use App\Events\VoteRecorded;
use App\Livewire\PollVote;
use App\Models\Poll;
use App\Models\PollOption;
use App\Models\User;
use App\Models\Vote;
use Illuminate\Support\Facades\Event;
use Livewire\Livewire;

beforeEach(function () {
    $this->poll = Poll::factory()->published()->create();
    $this->pollOptions = PollOption::factory(3)->sequence(
        ['sort_order' => 1],
        ['sort_order' => 2],
        ['sort_order' => 3],
    )->create(['poll_id' => $this->poll->id]);
});

// ──────────────────────────────────────────────────────────
// Page access & route resolution
// ──────────────────────────────────────────────────────────

describe('page access', function () {
    test('published poll page can be rendered', function () {
        $this->get(route('polls.vote', $this->poll))
            ->assertOk();
    });

    test('draft poll returns 404', function () {
        $poll = Poll::factory()->draft()->create();

        $this->get(route('polls.vote', $poll))
            ->assertNotFound();
    });

    test('archived poll returns 404', function () {
        $poll = Poll::factory()->archived()->create();

        $this->get(route('polls.vote', $poll))
            ->assertNotFound();
    });

    test('poll is resolved by slug in the route', function () {
        $this->get('/polls/'.$this->poll->slug)
            ->assertOk();
    });
});

// ──────────────────────────────────────────────────────────
// Guest behavior
// ──────────────────────────────────────────────────────────

describe('guest voting', function () {
    test('guest can view poll and sees voting form', function () {
        Livewire::test(PollVote::class, ['poll' => $this->poll])
            ->assertSet('hasVoted', false)
            ->assertSet('showLoginModal', false)
            ->assertSee($this->poll->title);
    });

    test('guest sees all poll options', function () {
        $component = Livewire::test(PollVote::class, ['poll' => $this->poll]);

        foreach ($this->pollOptions as $option) {
            $component->assertSee($option->option);
        }
    });

    test('guest is shown login modal when trying to vote', function () {
        $option = $this->pollOptions->first();

        Livewire::test(PollVote::class, ['poll' => $this->poll])
            ->assertSet('showLoginModal', false)
            ->set('selectedOption', $option->id)
            ->call('vote')
            ->assertSet('showLoginModal', true)
            ->assertSet('hasVoted', false);

        $this->assertDatabaseMissing('votes', [
            'poll_id' => $this->poll->id,
        ]);
    });

    test('intended url is stored in session when guest tries to vote', function () {
        $option = $this->pollOptions->first();

        Livewire::test(PollVote::class, ['poll' => $this->poll])
            ->set('selectedOption', $option->id)
            ->call('vote');

        expect(session('url.intended'))->toBe(route('polls.vote', $this->poll));
    });

    test('login modal contains login and register links', function () {
        Livewire::test(PollVote::class, ['poll' => $this->poll])
            ->call('vote')
            ->assertSet('showLoginModal', true)
            ->assertSeeHtml(route('login'))
            ->assertSeeHtml(route('register'));
    });

    test('guest vote attempt does not create a vote record', function () {
        Event::fake([VoteRecorded::class]);

        $option = $this->pollOptions->first();

        Livewire::test(PollVote::class, ['poll' => $this->poll])
            ->set('selectedOption', $option->id)
            ->call('vote');

        expect(Vote::query()->where('poll_id', $this->poll->id)->count())->toBe(0);

        Event::assertNotDispatched(VoteRecorded::class);
    });
});

// ──────────────────────────────────────────────────────────
// Authenticated voting — happy path
// ──────────────────────────────────────────────────────────

describe('authenticated voting', function () {
    test('authenticated user can vote on a poll', function () {
        Event::fake([VoteRecorded::class]);

        $user = User::factory()->create();
        $option = $this->pollOptions->first();

        Livewire::actingAs($user)
            ->test(PollVote::class, ['poll' => $this->poll])
            ->assertSet('hasVoted', false)
            ->set('selectedOption', $option->id)
            ->call('vote')
            ->assertSet('hasVoted', true)
            ->assertSet('votedOptionId', $option->id)
            ->assertSet('showLoginModal', false);

        $this->assertDatabaseHas('votes', [
            'poll_id' => $this->poll->id,
            'poll_option_id' => $option->id,
            'user_id' => $user->id,
        ]);

        Event::assertDispatched(VoteRecorded::class);
    });

    test('vote stores the user ip address', function () {
        $user = User::factory()->create();
        $option = $this->pollOptions->first();

        Livewire::actingAs($user)
            ->test(PollVote::class, ['poll' => $this->poll])
            ->set('selectedOption', $option->id)
            ->call('vote');

        $vote = Vote::query()
            ->where('poll_id', $this->poll->id)
            ->where('user_id', $user->id)
            ->first();

        expect($vote)->not->toBeNull()
            ->and($vote->ip_address)->not->toBeNull();
    });

    test('vote recorded event contains correct poll id and option counts', function () {
        Event::fake([VoteRecorded::class]);

        $user = User::factory()->create();
        $option = $this->pollOptions->first();

        Livewire::actingAs($user)
            ->test(PollVote::class, ['poll' => $this->poll])
            ->set('selectedOption', $option->id)
            ->call('vote');

        Event::assertDispatched(VoteRecorded::class, function (VoteRecorded $event) {
            return $event->pollId === $this->poll->id
                && count($event->options) === 3;
        });
    });

    test('vote recorded event includes vote counts per option', function () {
        Event::fake([VoteRecorded::class]);

        $user = User::factory()->create();
        $option = $this->pollOptions->first();

        Livewire::actingAs($user)
            ->test(PollVote::class, ['poll' => $this->poll])
            ->set('selectedOption', $option->id)
            ->call('vote');

        Event::assertDispatched(VoteRecorded::class, function (VoteRecorded $event) use ($option) {
            $votedOption = collect($event->options)->firstWhere('id', $option->id);

            return $votedOption !== null && $votedOption['votes_count'] === 1;
        });
    });

    test('multiple users can vote on the same poll', function () {
        $option1 = $this->pollOptions->first();
        $option2 = $this->pollOptions->last();

        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        Livewire::actingAs($user1)
            ->test(PollVote::class, ['poll' => $this->poll])
            ->set('selectedOption', $option1->id)
            ->call('vote')
            ->assertSet('hasVoted', true);

        Livewire::actingAs($user2)
            ->test(PollVote::class, ['poll' => $this->poll])
            ->set('selectedOption', $option2->id)
            ->call('vote')
            ->assertSet('hasVoted', true);

        expect(Vote::query()->where('poll_id', $this->poll->id)->count())->toBe(2);
    });

    test('multiple users can vote for the same option', function () {
        $option = $this->pollOptions->first();

        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        Livewire::actingAs($user1)
            ->test(PollVote::class, ['poll' => $this->poll])
            ->set('selectedOption', $option->id)
            ->call('vote');

        Livewire::actingAs($user2)
            ->test(PollVote::class, ['poll' => $this->poll])
            ->set('selectedOption', $option->id)
            ->call('vote');

        expect(
            Vote::query()
                ->where('poll_id', $this->poll->id)
                ->where('poll_option_id', $option->id)
                ->count()
        )->toBe(2);
    });
});

// ──────────────────────────────────────────────────────────
// Validation
// ──────────────────────────────────────────────────────────

describe('vote validation', function () {
    test('vote requires a selected option', function () {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(PollVote::class, ['poll' => $this->poll])
            ->call('vote')
            ->assertHasErrors(['selectedOption' => 'required']);
    });

    test('vote rejects a non-existent option id', function () {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(PollVote::class, ['poll' => $this->poll])
            ->set('selectedOption', 999999)
            ->call('vote')
            ->assertHasErrors(['selectedOption']);

        $this->assertDatabaseMissing('votes', [
            'poll_id' => $this->poll->id,
            'user_id' => $user->id,
        ]);
    });

    test('vote rejects an option that belongs to a different poll', function () {
        Event::fake([VoteRecorded::class]);

        $user = User::factory()->create();
        $otherPoll = Poll::factory()->published()->create();
        $foreignOption = PollOption::factory()->create(['poll_id' => $otherPoll->id]);

        Livewire::actingAs($user)
            ->test(PollVote::class, ['poll' => $this->poll])
            ->set('selectedOption', $foreignOption->id)
            ->call('vote')
            ->assertSet('hasVoted', false);

        $this->assertDatabaseMissing('votes', [
            'poll_id' => $this->poll->id,
            'poll_option_id' => $foreignOption->id,
        ]);

        Event::assertNotDispatched(VoteRecorded::class);
    });

    test('vote rejects a null option value', function () {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(PollVote::class, ['poll' => $this->poll])
            ->set('selectedOption', null)
            ->call('vote')
            ->assertHasErrors(['selectedOption' => 'required']);
    });
});

// ──────────────────────────────────────────────────────────
// Duplicate vote prevention
// ──────────────────────────────────────────────────────────

describe('duplicate vote prevention', function () {
    test('user cannot vote twice on the same poll', function () {
        Event::fake([VoteRecorded::class]);

        $user = User::factory()->create();
        $firstOption = $this->pollOptions->first();
        $secondOption = $this->pollOptions->last();

        Vote::factory()->create([
            'poll_id' => $this->poll->id,
            'poll_option_id' => $firstOption->id,
            'user_id' => $user->id,
            'ip_address' => '127.0.0.1',

        ]);

        Livewire::actingAs($user)
            ->test(PollVote::class, ['poll' => $this->poll])
            ->assertSet('hasVoted', true)
            ->assertSet('votedOptionId', $firstOption->id)
            ->set('selectedOption', $secondOption->id)
            ->call('vote')
            ->assertSet('votedOptionId', $firstOption->id);

        expect(Vote::query()->where('poll_id', $this->poll->id)->where('user_id', $user->id)->count())->toBe(1);

        Event::assertNotDispatched(VoteRecorded::class);
    });

    test('duplicate vote caught by db constraint shows correct original vote', function () {
        Event::fake([VoteRecorded::class]);

        $user = User::factory()->create();
        $firstOption = $this->pollOptions->first();

        Livewire::actingAs($user)
            ->test(PollVote::class, ['poll' => $this->poll])
            ->set('selectedOption', $firstOption->id)
            ->call('vote')
            ->assertSet('hasVoted', true)
            ->assertSet('votedOptionId', $firstOption->id);

        Event::assertDispatched(VoteRecorded::class);

        Livewire::actingAs($user)
            ->test(PollVote::class, ['poll' => $this->poll])
            ->assertSet('hasVoted', true)
            ->assertSet('votedOptionId', $firstOption->id);
    });

    test('user who already voted sees results not voting form', function () {
        $user = User::factory()->create();
        $option = $this->pollOptions->first();

        Vote::factory()->create([
            'poll_id' => $this->poll->id,
            'poll_option_id' => $option->id,
            'user_id' => $user->id,
            'ip_address' => '10.0.0.1',

        ]);

        Livewire::actingAs($user)
            ->test(PollVote::class, ['poll' => $this->poll])
            ->assertSet('hasVoted', true)
            ->assertSee(__('Results'))
            ->assertSee(__('Your vote:'))
            ->assertDontSee(__('Submit Vote'));
    });
});

// ──────────────────────────────────────────────────────────
// Existing vote detection on mount
// ──────────────────────────────────────────────────────────

describe('existing vote detection on mount', function () {
    test('component detects existing vote for authenticated user', function () {
        $user = User::factory()->create();
        $option = $this->pollOptions->first();

        Vote::factory()->create([
            'poll_id' => $this->poll->id,
            'poll_option_id' => $option->id,
            'user_id' => $user->id,
            'ip_address' => '10.0.0.1',

        ]);

        Livewire::actingAs($user)
            ->test(PollVote::class, ['poll' => $this->poll])
            ->assertSet('hasVoted', true)
            ->assertSet('votedOptionId', $option->id);
    });

    test('component shows clean state for user who has not voted', function () {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(PollVote::class, ['poll' => $this->poll])
            ->assertSet('hasVoted', false)
            ->assertSet('votedOptionId', null)
            ->assertSet('selectedOption', null);
    });

    test('component shows clean state for guest', function () {
        Livewire::test(PollVote::class, ['poll' => $this->poll])
            ->assertSet('hasVoted', false)
            ->assertSet('votedOptionId', null)
            ->assertSet('showLoginModal', false);
    });
});

// ──────────────────────────────────────────────────────────
// Results display
// ──────────────────────────────────────────────────────────

describe('results display', function () {
    test('results show correct percentage for single vote', function () {
        $user = User::factory()->create();
        $option = $this->pollOptions->first();

        Vote::factory()->create([
            'poll_id' => $this->poll->id,
            'poll_option_id' => $option->id,
            'user_id' => $user->id,
            'ip_address' => '10.0.0.1',

        ]);

        Livewire::actingAs($user)
            ->test(PollVote::class, ['poll' => $this->poll])
            ->assertSet('hasVoted', true)
            ->assertSee('100%')
            ->assertSee('(1)');
    });

    test('results show distributed percentages across multiple options', function () {
        $option1 = $this->pollOptions[0];
        $option2 = $this->pollOptions[1];

        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $user3 = User::factory()->create();

        Vote::factory()->create([
            'poll_id' => $this->poll->id,
            'poll_option_id' => $option1->id,
            'user_id' => $user1->id,
            'ip_address' => '10.0.0.1',

        ]);
        Vote::factory()->create([
            'poll_id' => $this->poll->id,
            'poll_option_id' => $option1->id,
            'user_id' => $user2->id,
            'ip_address' => '10.0.0.2',

        ]);
        Vote::factory()->create([
            'poll_id' => $this->poll->id,
            'poll_option_id' => $option2->id,
            'user_id' => $user3->id,
            'ip_address' => '10.0.0.3',

        ]);

        Livewire::actingAs($user1)
            ->test(PollVote::class, ['poll' => $this->poll])
            ->assertSet('hasVoted', true)
            ->assertSee('66.7%')
            ->assertSee('(2)')
            ->assertSee('33.3%')
            ->assertSee('(1)');
    });

    test('results show total votes badge', function () {
        $user = User::factory()->create();
        $option = $this->pollOptions->first();

        Vote::factory()->create([
            'poll_id' => $this->poll->id,
            'poll_option_id' => $option->id,
            'user_id' => $user->id,
            'ip_address' => '10.0.0.1',

        ]);

        Livewire::actingAs($user)
            ->test(PollVote::class, ['poll' => $this->poll])
            ->assertSee('1 vote');
    });

    test('results show plural votes label for multiple votes', function () {
        $option = $this->pollOptions->first();

        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        Vote::factory()->create([
            'poll_id' => $this->poll->id,
            'poll_option_id' => $option->id,
            'user_id' => $user1->id,
            'ip_address' => '10.0.0.1',

        ]);
        Vote::factory()->create([
            'poll_id' => $this->poll->id,
            'poll_option_id' => $option->id,
            'user_id' => $user2->id,
            'ip_address' => '10.0.0.2',

        ]);

        Livewire::actingAs($user1)
            ->test(PollVote::class, ['poll' => $this->poll])
            ->assertSee('2 votes');
    });

    test('results display the voted option name under your vote label', function () {
        $user = User::factory()->create();
        $option = $this->pollOptions->first();

        Vote::factory()->create([
            'poll_id' => $this->poll->id,
            'poll_option_id' => $option->id,
            'user_id' => $user->id,
            'ip_address' => '10.0.0.1',

        ]);

        Livewire::actingAs($user)
            ->test(PollVote::class, ['poll' => $this->poll])
            ->assertSee(__('Your vote:'))
            ->assertSee($option->option);
    });
});

// ──────────────────────────────────────────────────────────
// Poll content display
// ──────────────────────────────────────────────────────────

describe('poll content display', function () {
    test('poll title is displayed', function () {
        Livewire::test(PollVote::class, ['poll' => $this->poll])
            ->assertSee($this->poll->title);
    });

    test('poll description is displayed when present', function () {
        $poll = Poll::factory()->published()->create(['description' => 'A detailed poll description']);
        PollOption::factory(2)->create(['poll_id' => $poll->id]);

        Livewire::test(PollVote::class, ['poll' => $poll])
            ->assertSee('A detailed poll description');
    });

    test('poll status badge is displayed', function () {
        Livewire::test(PollVote::class, ['poll' => $this->poll])
            ->assertSee($this->poll->status->label());
    });

    test('options are rendered in sort order', function () {
        $component = Livewire::test(PollVote::class, ['poll' => $this->poll]);

        $html = $component->html();
        $positions = [];

        foreach ($this->pollOptions->sortBy('sort_order') as $option) {
            $positions[] = strpos($html, $option->option);
        }

        expect($positions)->toEqual(collect($positions)->sort()->values()->all());
    });
});
