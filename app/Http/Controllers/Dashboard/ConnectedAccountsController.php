<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\ConnectedSocialAccount;
use Illuminate\Http\Request;

class ConnectedAccountsController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', ConnectedSocialAccount::class);

        $brands = Brand::orderBy('name')->get();
        $accounts = ConnectedSocialAccount::with(['brand', 'token'])
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return view('dashboard.connected-accounts', compact('brands', 'accounts'));
    }
}
