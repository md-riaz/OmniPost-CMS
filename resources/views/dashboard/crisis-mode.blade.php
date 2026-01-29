<!DOCTYPE html>
<html>
<head>
    <title>Crisis Mode - {{ $brand->name }}</title>
</head>
<body>
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1>Crisis Mode</h1>
                    <p class="text-muted">Emergency controls for {{ $brand->name }}</p>
                </div>
                <a href="{{ url('/dashboard') }}" class="btn btn-secondary">
                    <i class="bi bi-arrow-left"></i> Back to Dashboard
                </a>
            </div>
        </div>
    </div>

    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    <div class="row">
        <div class="col-md-8">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-danger text-white">
                    <h5 class="mb-0">
                        <i class="bi bi-exclamation-triangle-fill"></i> Crisis Mode Controls
                    </h5>
                </div>
                <div class="card-body">
                    @if($status['enabled'])
                        <div class="alert alert-danger">
                            <h5 class="alert-heading">
                                <i class="bi bi-pause-circle-fill"></i> Crisis Mode is ACTIVE
                            </h5>
                            <p class="mb-0">
                                @if($status['scope'] === 'all')
                                    All scheduled posts for this brand are paused.
                                @else
                                    Posts are paused for: {{ implode(', ', array_keys($status['platforms'])) }}
                                @endif
                            </p>
                        </div>

                        <div class="d-grid gap-2">
                            <form method="POST" action="{{ route('dashboard.crisis-mode.disable', $brand) }}" 
                                  onsubmit="return confirm('Are you sure you want to disable crisis mode? Posts will resume publishing.');">
                                @csrf
                                <input type="hidden" name="platform" value="all">
                                <button type="submit" class="btn btn-success btn-lg w-100">
                                    <i class="bi bi-play-circle-fill"></i> Disable Crisis Mode (Resume All)
                                </button>
                            </form>

                            @if($status['scope'] === 'platform')
                                @foreach($status['platforms'] as $platform => $details)
                                <form method="POST" action="{{ route('dashboard.crisis-mode.disable', $brand) }}" 
                                      class="mt-2"
                                      onsubmit="return confirm('Resume posts for {{ ucfirst($platform) }}?');">
                                    @csrf
                                    <input type="hidden" name="platform" value="{{ $platform }}">
                                    <button type="submit" class="btn btn-outline-success w-100">
                                        <i class="bi bi-play"></i> Resume {{ ucfirst($platform) }} Only
                                    </button>
                                </form>
                                @endforeach
                            @endif
                        </div>
                    @else
                        <div class="alert alert-success">
                            <h5 class="alert-heading">
                                <i class="bi bi-check-circle-fill"></i> Crisis Mode is INACTIVE
                            </h5>
                            <p class="mb-0">All posts are publishing normally.</p>
                        </div>

                        <h6 class="mb-3">Activate Crisis Mode:</h6>
                        
                        <form method="POST" action="{{ route('dashboard.crisis-mode.enable', $brand) }}" 
                              onsubmit="return confirm('Are you sure? This will pause ALL scheduled posts for this brand.');">
                            @csrf
                            <input type="hidden" name="platform" value="all">
                            <button type="submit" class="btn btn-danger btn-lg w-100 mb-3">
                                <i class="bi bi-pause-circle-fill"></i> Pause All Platforms
                            </button>
                        </form>

                        <div class="row">
                            <div class="col-md-6">
                                <form method="POST" action="{{ route('dashboard.crisis-mode.enable', $brand) }}"
                                      onsubmit="return confirm('Pause Facebook posts only?');">
                                    @csrf
                                    <input type="hidden" name="platform" value="facebook">
                                    <button type="submit" class="btn btn-outline-danger w-100">
                                        <i class="bi bi-facebook"></i> Pause Facebook Only
                                    </button>
                                </form>
                            </div>
                            <div class="col-md-6">
                                <form method="POST" action="{{ route('dashboard.crisis-mode.enable', $brand) }}"
                                      onsubmit="return confirm('Pause LinkedIn posts only?');">
                                    @csrf
                                    <input type="hidden" name="platform" value="linkedin">
                                    <button type="submit" class="btn btn-outline-danger w-100">
                                        <i class="bi bi-linkedin"></i> Pause LinkedIn Only
                                    </button>
                                </form>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card shadow-sm">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0">
                        <i class="bi bi-info-circle-fill"></i> About Crisis Mode
                    </h5>
                </div>
                <div class="card-body">
                    <h6>When to use:</h6>
                    <ul class="small">
                        <li>Breaking news requires immediate silence</li>
                        <li>Brand reputation issue</li>
                        <li>Legal or compliance concerns</li>
                        <li>System maintenance</li>
                        <li>Token or account issues</li>
                    </ul>

                    <h6 class="mt-3">What it does:</h6>
                    <ul class="small">
                        <li>Stops all scheduled posts from publishing</li>
                        <li>Existing posts remain published</li>
                        <li>Can be platform-specific</li>
                        <li>All actions are logged in audit trail</li>
                    </ul>

                    <div class="alert alert-warning small mt-3">
                        <strong>Note:</strong> Crisis mode lasts 24 hours by default. It will automatically disable after that time.
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
