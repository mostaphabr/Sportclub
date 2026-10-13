@extends('SportClub.layouts.parts')

@section('content')
    @php
        $tabs = [
            'overview' => ['Overview', 'bi-speedometer2'],
            'pitches' => ['Terrains', 'bi-grid-3x3'],
            'users' => ['Users', 'bi-people'],
            'posts' => ['Blog posts', 'bi-journal-text'],
            'reservations' => ['Reservations', 'bi-calendar-check'],
            'messages' => ['Messages', 'bi-envelope'],
        ];
        $roleBadge = ['admin' => 'bg-danger', 'provider' => 'bg-warning text-dark', 'user' => 'bg-secondary'];
    @endphp

    <div class="container my-5">
        <div class="d-flex flex-wrap align-items-center justify-content-between mb-3 gap-2">
            <h1 class="h2 m-0">Admin Dashboard</h1>
            <span class="text-muted small">Signed in as {{ auth()->user()->name }}</span>
        </div>

        {{-- Flash messages --}}
        @if (session('message'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('message') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif
        @if (session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif
        @if (session('generated_password'))
            @php $credentials = session('generated_password'); @endphp
            <div class="alert alert-warning" role="alert">
                <h5 class="alert-heading"><i class="bi bi-key"></i> Login details for {{ $credentials['name'] }}</h5>
                <p class="mb-2">Copy this password now and give it to the user. It is shown <strong>only once</strong>.</p>
                <div class="d-flex flex-wrap gap-3 align-items-center">
                    <span>Email: <code class="fs-6">{{ $credentials['email'] }}</code></span>
                    <span>Password: <code class="fs-5" id="generated-password">{{ $credentials['password'] }}</code></span>
                    <button type="button" class="btn btn-sm btn-outline-dark" onclick="navigator.clipboard && navigator.clipboard.writeText(document.getElementById('generated-password').innerText); this.innerText='Copied';">Copy</button>
                </div>
            </div>
        @endif

        {{-- Tabs --}}
        <ul class="nav nav-tabs mb-4 flex-nowrap overflow-auto">
            @foreach ($tabs as $key => [$label, $icon])
                <li class="nav-item">
                    <a class="nav-link text-nowrap {{ $tab === $key ? 'active fw-semibold' : '' }}"
                        href="{{ route('AdminDashbord.index', ['tab' => $key]) }}">
                        <i class="bi {{ $icon }}"></i> {{ $label }}
                        @if ($key === 'messages' && $unreadMessages > 0)
                            <span class="badge rounded-pill bg-danger">{{ $unreadMessages }}</span>
                        @endif
                    </a>
                </li>
            @endforeach
        </ul>

        {{-- ============================================================ OVERVIEW --}}
        @if ($tab === 'overview')
            <div class="row g-3">
                @foreach ([
                    ['Users', $stats['users'], $stats['players'].' players · '.$stats['providers'].' providers · '.$stats['admins'].' admins', 'users'],
                    ['Terrains', $stats['pitches'], 'pitches on the platform', 'pitches'],
                    ['Bookings for '.$playDay->format('D d/m'), $stats['reservations_next'], $stats['reservations_upcoming'].' upcoming in total', 'reservations'],
                    ['Blog posts', $stats['posts'], $stats['comments'].' comments', 'posts'],
                    ['Revenue '.now()->format('F'), number_format($stats['revenue_month'], 0, ',', ' ').' '.config('sportclub.currency'), $stats['bookings_month'].' bookings this month', 'reservations'],
                ] as [$label, $value, $hint, $link])
                    <div class="col-sm-6 col-lg">
                        <a href="{{ route('AdminDashbord.index', ['tab' => $link]) }}" class="text-decoration-none">
                            <div class="card h-100 shadow-sm">
                                <div class="card-body">
                                    <div class="text-muted small text-uppercase">{{ $label }}</div>
                                    <div class="display-6 fw-bold text-dark">{{ $value }}</div>
                                    <div class="small text-muted">{{ $hint }}</div>
                                </div>
                            </div>
                        </a>
                    </div>
                @endforeach
            </div>

            <h3 class="h5 mt-5">Terrains this month</h3>
            @if ($topPitches->isEmpty())
                <p class="text-muted">No terrains yet.</p>
            @else
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead>
                            <tr>
                                <th>Terrain</th>
                                <th class="text-center">Bookings</th>
                                <th class="text-end">Revenue</th>
                                <th>Rating</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($topPitches as $pitche)
                                <tr>
                                    <td><a href="{{ route('reservation.show', $pitche->name) }}">{{ $pitche->name }}</a></td>
                                    <td class="text-center">{{ $pitche->bookings_month }}</td>
                                    <td class="text-end">{{ number_format((int) $pitche->revenue_month, 0, ',', ' ') }} {{ config('sportclub.currency') }}</td>
                                    <td>@include('SportClub.partials.stars', ['rating' => $pitche->averageRating()])</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <p class="small text-muted">Revenue = price of each booked hour this month (price saved at booking time).</p>
            @endif
        @endif

        {{-- ============================================================ PITCHES --}}
        @if ($tab === 'pitches')
            <div class="row g-4">
                <div class="col-lg-7">
                    <h3 class="h5">All terrains</h3>
                    @if ($pitches->isEmpty())
                        <p class="text-muted">No terrains yet. Add the first one with the form.</p>
                    @else
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Provider</th>
                                        <th>Format</th>
                                        <th>Price</th>
                                        <th>Hours</th>
                                        <th class="text-center">Upcoming</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($pitches as $pitche)
                                        <tr>
                                            <td><a href="{{ route('reservation.show', $pitche->name) }}">{{ $pitche->name }}</a></td>
                                            <td>{{ optional($pitche->user)->name ?? '—' }}</td>
                                            <td>{{ $pitche->formatLabel() }}</td>
                                            <td class="text-nowrap">{{ $pitche->price_per_hour !== null ? $pitche->price_per_hour.' '.config('sportclub.currency') : '—' }}</td>
                                            <td>{{ $pitche->openhours }}</td>
                                            <td class="text-center">{{ $pitche->upcoming_reservations_count }}</td>
                                            <td class="text-end text-nowrap">
                                                <a class="btn btn-sm btn-outline-primary" href="{{ route('AdminDashbord.edit', $pitche->id) }}">Edit</a>
                                                <form class="d-inline" action="{{ route('AdminDashbord.destroy', $pitche->id) }}" method="POST"
                                                    onsubmit="return confirm('Delete {{ addslashes($pitche->name) }}? Its reservations will be deleted too.');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                                </form>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>

                <div class="col-lg-5">
                    <div class="card shadow-sm">
                        <div class="card-body">
                            <h3 class="h5 text-warning">Add terrain</h3>

                            @if ($errors->any())
                                <div class="alert alert-danger">
                                    <ul class="mb-0">
                                        @foreach ($errors->all() as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif

                            <form action="{{ route('AdminDashbord.store') }}" method="POST" enctype="multipart/form-data">
                                @csrf
                                <label for="name" class="form-label">Name</label>
                                <input type="text" class="form-control mb-2" id="name" name="name" value="{{ old('name') }}" required>

                                <label for="provider" class="form-label">Provider</label>
                                @if (count($providers) > 0)
                                    <select name="provider" id="provider" class="form-select mb-2" required>
                                        <option value="" disabled {{ old('provider') ? '' : 'selected' }}>Select provider</option>
                                        @foreach ($providers as $provider)
                                            <option value="{{ $provider->id }}" @selected(old('provider') == $provider->id)>{{ $provider->name }}</option>
                                        @endforeach
                                    </select>
                                @else
                                    <p class="alert alert-danger">No providers yet. Add a user with the role "provider" in the
                                        <a href="{{ route('AdminDashbord.index', ['tab' => 'users']) }}">Users</a> tab first.</p>
                                @endif

                                <label for="capacity" class="form-label">Capacity (players)</label>
                                <input type="number" min="2" max="30" class="form-control mb-2" id="capacity" name="capacity" value="{{ old('capacity') }}" placeholder="e.g. 10 for 5 vs 5" required>

                                <label for="price_per_hour" class="form-label">Price per hour ({{ config('sportclub.currency') }})</label>
                                <input type="number" min="0" class="form-control mb-2" id="price_per_hour" name="price_per_hour" value="{{ old('price_per_hour') }}" placeholder="e.g. 150">

                                <label for="localisation" class="form-label">Location</label>
                                <input type="text" class="form-control mb-2" id="localisation" name="localisation" value="{{ old('localisation') }}" required>

                                <label for="openhours" class="form-label">Open hours</label>
                                <input type="text" placeholder="example: 8:00-21:00" class="form-control mb-2" id="openhours" name="openhours" value="{{ old('openhours') }}" required>
                                <div class="form-text mb-2">Players can book one-hour slots inside these hours.</div>

                                <label for="image" class="form-label">Image</label>
                                <input type="file" class="form-control mb-3" id="image" name="image" accept="image/*" required>

                                <button type="submit" class="btn btn-info">Add terrain</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        {{-- ============================================================ USERS --}}
        @if ($tab === 'users')
            <div class="row g-4">
                <div class="col-lg-4 order-lg-2">
                    <div class="card shadow-sm">
                        <div class="card-body">
                            <h3 class="h5 text-warning">Add user</h3>
                            <p class="small text-muted">A password is generated automatically and shown to you once after saving.</p>

                            @if ($errors->addUser->any())
                                <div class="alert alert-danger">
                                    <ul class="mb-0">
                                        @foreach ($errors->addUser->all() as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif

                            <form action="{{ route('admin.users.store') }}" method="POST">
                                @csrf
                                <label for="user-name" class="form-label">Name</label>
                                <input type="text" class="form-control mb-2" id="user-name" name="name" value="{{ old('name') }}" required>

                                <label for="user-email" class="form-label">Email</label>
                                <input type="email" class="form-control mb-2" id="user-email" name="email" value="{{ old('email') }}" required>

                                <label for="user-role" class="form-label">Role</label>
                                <select name="role" id="user-role" class="form-select mb-3">
                                    @foreach (['user' => 'User (player)', 'provider' => 'Provider (pitch owner)', 'admin' => 'Admin'] as $value => $label)
                                        <option value="{{ $value }}" @selected(old('role', 'user') === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>

                                <button type="submit" class="btn btn-info">Add user</button>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-lg-8 order-lg-1">
                    <form class="d-flex gap-2 mb-3" method="GET" action="{{ route('AdminDashbord.index') }}">
                        <input type="hidden" name="tab" value="users">
                        <input type="search" name="q" value="{{ $search }}" class="form-control" placeholder="Search by name or email">
                        <button class="btn btn-outline-secondary" type="submit">Search</button>
                        @if ($search !== '')
                            <a class="btn btn-link" href="{{ route('AdminDashbord.index', ['tab' => 'users']) }}">Clear</a>
                        @endif
                    </form>

                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Role</th>
                                    <th class="text-center">Posts</th>
                                    <th class="text-center">Bookings</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($users as $user)
                                    @php $isMe = (int) $user->id === (int) auth()->id(); @endphp
                                    <tr>
                                        <td>
                                            <div class="fw-semibold">{{ $user->name }} @if ($isMe)<span class="text-muted small">(you)</span>@endif</div>
                                            <div class="small text-muted">{{ $user->email }}</div>
                                        </td>
                                        <td>
                                            @if ($isMe)
                                                <span class="badge {{ $roleBadge[$user->role] ?? 'bg-secondary' }}">{{ $user->role }}</span>
                                            @else
                                                <form action="{{ route('admin.users.role', $user) }}" method="POST" class="d-flex gap-1">
                                                    @csrf
                                                    @method('PATCH')
                                                    <select name="role" class="form-select form-select-sm" style="min-width: 7rem" onchange="this.form.submit()" aria-label="Role of {{ $user->name }}">
                                                        @foreach (['user', 'provider', 'admin'] as $role)
                                                            <option value="{{ $role }}" @selected($user->role === $role)>{{ $role }}</option>
                                                        @endforeach
                                                    </select>
                                                    <noscript><button class="btn btn-sm btn-outline-secondary">Save</button></noscript>
                                                </form>
                                            @endif
                                        </td>
                                        <td class="text-center">{{ $user->posts_count }}</td>
                                        <td class="text-center">{{ $user->reservations_count }}</td>
                                        <td class="text-end text-nowrap">
                                            @unless ($isMe)
                                                <form class="d-inline" action="{{ route('admin.users.password', $user) }}" method="POST"
                                                    onsubmit="return confirm('Generate a new password for {{ addslashes($user->name) }}? The old one will stop working.');">
                                                    @csrf
                                                    @method('PATCH')
                                                    <button type="submit" class="btn btn-sm btn-outline-secondary" title="Generate a new password"><i class="bi bi-key"></i></button>
                                                </form>
                                                <form class="d-inline" action="{{ route('admin.users.destroy', $user) }}" method="POST"
                                                    onsubmit="return confirm('Delete {{ addslashes($user->name) }}? Their posts, comments, reservations and pitches will be deleted too.');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                                </form>
                                            @endunless
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="5" class="text-muted">No users found.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    {{ $users->links() }}
                </div>
            </div>
        @endif

        {{-- ============================================================ POSTS --}}
        @if ($tab === 'posts')
            <p class="text-muted">Remove posts that don't respect the community rules. Deleting a post also deletes its comments.</p>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th style="width: 72px"></th>
                            <th>Title</th>
                            <th>Author</th>
                            <th class="text-center">Comments</th>
                            <th>Published</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($posts as $post)
                            <tr>
                                <td><img src="{{ $post->imageUrl() }}" alt="" class="rounded" style="width: 64px; height: 44px; object-fit: cover"></td>
                                <td>
                                    <a href="{{ route('posts.show', $post->id) }}" class="fw-semibold">{{ $post->title }}</a>
                                    <div class="small text-muted">{{ \Illuminate\Support\Str::limit($post->description, 90) }}</div>
                                </td>
                                <td>{{ optional($post->user)->name ?? '—' }}</td>
                                <td class="text-center">{{ $post->comments_count }}</td>
                                <td class="text-nowrap">{{ $post->created_at?->format('d/m/Y') }}</td>
                                <td class="text-end">
                                    <form action="{{ route('posts.destroy', $post) }}" method="POST"
                                        onsubmit="return confirm('Delete the post &quot;{{ addslashes($post->title) }}&quot; and its comments?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-muted">No posts yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            {{ $posts->links() }}
        @endif

        {{-- ============================================================ RESERVATIONS --}}
        @if ($tab === 'reservations')
            <p class="text-muted">Upcoming bookings on all terrains. Players book for the next day.</p>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Day</th>
                            <th>Time</th>
                            <th>Terrain</th>
                            <th>Player</th>
                            <th>Phone</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($reservations as $reservation)
                            <tr>
                                <td class="text-nowrap">{{ $reservation->date?->format('D d/m/Y') }}</td>
                                <td class="text-nowrap">{{ $reservation->timeRange() }}</td>
                                <td>{{ optional($reservation->pitche)->name }}</td>
                                <td>{{ optional($reservation->user)->name }}<div class="small text-muted">{{ optional($reservation->user)->email }}</div></td>
                                <td>{{ optional(optional($reservation->user)->profile)->phone_number ?? '—' }}</td>
                                <td class="text-end">
                                    <form action="{{ route('reservation.destroy', $reservation) }}" method="POST"
                                        onsubmit="return confirm('Cancel this reservation?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger">Cancel</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-muted">No upcoming reservations.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            {{ $reservations->links() }}
        @endif

        {{-- ============================================================ MESSAGES --}}
        @if ($tab === 'messages')
            <p class="text-muted">Messages sent from the Contact page. Answer by email, then mark them as read.</p>
            @forelse ($messages as $contact)
                <div class="card mb-3 {{ $contact->read_at ? '' : 'border-info shadow-sm' }}">
                    <div class="card-body">
                        <div class="d-flex flex-wrap justify-content-between gap-2">
                            <div>
                                <h3 class="h6 mb-1">
                                    @unless ($contact->read_at)<span class="badge bg-info text-dark me-1">new</span>@endunless
                                    {{ $contact->subject }}
                                </h3>
                                <div class="small text-muted">
                                    {{ $contact->name }} · <a href="mailto:{{ $contact->email }}?subject={{ rawurlencode('Re: '.$contact->subject) }}">{{ $contact->email }}</a>
                                    · {{ $contact->created_at?->format('d/m/Y H:i') }}
                                    @if ($contact->user)
                                        · <a href="{{ route('profile', $contact->user) }}">member profile</a>
                                    @endif
                                </div>
                            </div>
                            <div class="text-nowrap">
                                <form class="d-inline" action="{{ route('admin.messages.read', $contact) }}" method="POST">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="btn btn-sm btn-outline-secondary">{{ $contact->read_at ? 'Mark unread' : 'Mark read' }}</button>
                                </form>
                                <form class="d-inline" action="{{ route('admin.messages.destroy', $contact) }}" method="POST"
                                    onsubmit="return confirm('Delete this message?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                </form>
                            </div>
                        </div>
                        <p class="mt-2 mb-0" style="white-space: pre-line">{{ $contact->message }}</p>
                    </div>
                </div>
            @empty
                <p class="text-muted">No messages yet.</p>
            @endforelse
            {{ $messages->links() }}
        @endif
    </div>
@endsection

@section('title', 'dashbord')
