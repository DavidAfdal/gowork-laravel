<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTopupWalletRequest;
use App\Http\Requests\StoreWithdrawWalletRequest;
use App\Models\Project;
use App\Models\ProjectApplicant;
use App\Models\WalletTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * Display a listing of the resource.
     */

     public function proposals() {
        return view('dashboard.proposals');
     }

     public function proposal_details(Project $project, ProjectApplicant $projectApplicant) {
        if($projectApplicant->freelancer_id != auth()->id()) {
            abort(403, "you are not authorized to see this page");
        }

        return view('dashboard.proposal_details', compact('projectApplicant', 'project'));
     }

    public function wallet()
    {
        //
        $user = Auth::user();

        $wallet_transaction = WalletTransaction::where('user_id', $user->id)
        ->orderByDesc('id')
        ->paginate(10);

        return view('dashboard.wallet', compact('wallet_transaction'));
    }

    public function withdraw_wallet()
    {
        $user = Auth::user();
        return view("dashboard.withdraw_wallet");
    }

    public function topup_wallet() {
        $user = Auth::user();
        return view("dashboard.topup_wallet");
    }

    public function topup_wallet_store(StoreTopupWalletRequest $request) {
        $user = Auth::user();

        DB::transaction(function () use ($request, $user) {
            $validated = $request->validated();

            if($request->hasFile('proof')) {
                $proofPath = $request->file('proof')->store('proofs', "public");
                $validated['proof'] = $proofPath;
            }

            $validated['type'] = 'Topup';
            $validated['is_paid'] = false;
            $validated['user_id'] = $user->id;

            $newTopupWallet = WalletTransaction::create($validated);
        });

        return redirect()->route('dashboard.wallet');
    }

    public function withdraw_wallet_store(StoreWithdrawWalletRequest $request) {
        $user = Auth::user();

        if($user->wallet->balance < 100000){
            return redirect()->back()->withErrors([
                "amount" => "Balance anda saat ini tidak cukup"
            ]);
        }

        DB::transaction(function () use ($request, $user) {
            $validated = $request->validated();

            if($request->hasFile('proof')) {
                $proofPath = $request->file('proof')->store('proofs', "public");
                $validated['proof'] = $proofPath;
            }

            $validated['type'] = 'Withdraw';
            $validated["amount"]= $user->wallet->balance;
            $validated['is_paid'] = false;
            $validated['user_id'] = $user->id;

            $newTopupWallet = WalletTransaction::create($validated);

            $user->wallet->update([
                'balance' => 0
            ]);
        });

        return redirect()->route('dashboard.wallet');
    }


}
