@extends('tyro-dashboard::layouts.admin')

@section('title', 'Connect Social Accounts')

@section('breadcrumb')
<a href="{{ route('tyro-dashboard.index') }}">Dashboard</a>
<span class="breadcrumb-separator">/</span>
<span>Connect Social Accounts</span>
@endsection

@section('content')
<div class="page-header">
    <div class="page-header-row">
        <div>
            <h1 class="page-title">Connect Social Accounts</h1>
            <p style="color: var(--text-secondary); margin-top: 0.25rem;">Connect your Facebook Pages and LinkedIn Organizations to start publishing.</p>
        </div>
    </div>
</div>

<!-- Connect New Account Card -->
<div class="card" style="margin-bottom: 1.5rem;">
    <div class="card-header">
        <h3 class="card-title">Connect New Account</h3>
    </div>
    <div class="card-body">
        <form id="connectForm" style="display: flex; flex-wrap: wrap; gap: 1rem; align-items: flex-end;">
            <div style="flex: 1; min-width: 200px;">
                <label class="form-label" for="brand_id">Select Brand</label>
                <select name="brand_id" id="brand_id" class="form-input" required>
                    <option value="">-- Select a Brand --</option>
                    @foreach($brands as $brand)
                        <option value="{{ $brand->id }}">{{ $brand->name }}</option>
                    @endforeach
                </select>
            </div>
            <div style="display: flex; gap: 0.75rem;">
                <button type="button" onclick="connectPlatform('facebook')" class="btn" style="background: #1877F2; color: white; display: flex; align-items: center; gap: 0.5rem;">
                    <svg width="20" height="20" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                    </svg>
                    Connect Facebook
                </button>
                <button type="button" onclick="connectPlatform('linkedin')" class="btn" style="background: #0A66C2; color: white; display: flex; align-items: center; gap: 0.5rem;">
                    <svg width="20" height="20" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/>
                    </svg>
                    Connect LinkedIn
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Connected Accounts Table -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Connected Accounts</h3>
    </div>
    @if($accounts->count())
    <div class="table-container">
        <table class="table">
            <thead>
                <tr>
                    <th>Brand</th>
                    <th>Platform</th>
                    <th>Account Name</th>
                    <th>Status</th>
                    <th>Connected At</th>
                    <th style="text-align: right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($accounts as $account)
                <tr>
                    <td>{{ $account->brand->name ?? '-' }}</td>
                    <td>
                        <span class="badge {{ $account->platform === 'facebook' ? 'badge-info' : 'badge-primary' }}">
                            {{ ucfirst($account->platform) }}
                        </span>
                    </td>
                    <td>{{ $account->display_name }}</td>
                    <td>
                        @php
                            $statusColors = [
                                'connected' => 'badge-success',
                                'expired' => 'badge-warning',
                                'revoked' => 'badge-danger',
                            ];
                        @endphp
                        <span class="badge {{ $statusColors[$account->status] ?? 'badge-secondary' }}">
                            {{ ucfirst($account->status) }}
                        </span>
                    </td>
                    <td>{{ $account->created_at->format('M d, Y') }}</td>
                    <td style="text-align: right;">
                        <div class="table-actions" style="justify-content: flex-end;">
                            @if($account->status === 'connected')
                                <form action="{{ route('oauth.disconnect', $account) }}" method="POST" onsubmit="return confirm('Disconnect this account?')" style="display: inline;">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-ghost text-danger" title="Disconnect">
                                        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 16px; height: 16px;">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                                        </svg>
                                        Disconnect
                                    </button>
                                </form>
                            @else
                                <a href="{{ route('oauth.reconnect', $account) }}" class="btn btn-sm btn-ghost text-primary" title="Reconnect">
                                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 16px; height: 16px;">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                    </svg>
                                    Reconnect
                                </a>
                            @endif
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @if($accounts->hasPages())
    <div class="pagination">
        {{ $accounts->links() }}
    </div>
    @endif
    @else
    <div class="empty-state">
        <div class="empty-state-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
            </svg>
        </div>
        <h3>No connected accounts</h3>
        <p>Select a brand above and connect your first social media account.</p>
    </div>
    @endif
</div>

<script>
function connectPlatform(platform) {
    const brandId = document.getElementById('brand_id').value;
    if (!brandId) {
        alert('Please select a brand first');
        return;
    }
    window.location.href = '/oauth/' + platform + '/redirect?brand_id=' + brandId;
}
</script>
@endsection
