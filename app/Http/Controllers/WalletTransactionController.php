<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\WalletTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WalletTransactionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    public function wallet_topups() {
        $topups_transaction = WalletTransaction::where('type', "Topup")->orderByDesc('id')->paginate(10);
        return view('admin.wallet_transactions.topups', compact('topups_transaction'));
    }

    public function wallet_withdrawals() {
        $withdrawals_transaction = WalletTransaction::where('type', "Withdraw")->orderByDesc('id')->paginate(10);
        return view('admin.wallet_transactions.withdrawals', compact('withdrawals_transaction'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(WalletTransaction $walletTransaction)
    {
        //
        return view('admin.wallet_transactions.details', compact('walletTransaction'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(WalletTransaction $walletTransaction)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, WalletTransaction $walletTransaction)
    {
        //
        $user_to_be_approved =  User::where('id', $walletTransaction->user_id)->first();

        DB::transaction(function () use ($user_to_be_approved, $walletTransaction, $request) {
            if ($walletTransaction->type == "Withdraw") {
                if($request->hasFile('proof')) {
                    $proofPath = $request->file('proof')->store('proofs',"public");
                }
                $walletTransaction->update([
                    "proof" => $proofPath,
                    "is_paid" => true,
                ]);
            }
            else if ($walletTransaction->type == "Topup") {
                $walletTransaction->update([
                    "is_paid" => true,
                ]);

                $user_to_be_approved->wallet->increment("balance", $walletTransaction->amount);
            }
        });

        if ($walletTransaction->type == "Withdraw") {
            return redirect()->route("admin.withdrawals");
        } else {
            return redirect()->route("admin.topups");
        }

    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(WalletTransaction $walletTransaction)
    {
        //
    }
}