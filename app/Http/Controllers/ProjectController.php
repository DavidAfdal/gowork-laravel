<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProjectRequest;
use App\Http\Requests\StoreToolProjectRequest;
use App\Http\Requests\StoreToolRequest;
use App\Models\Category;
use App\Models\Project;
use App\Models\ProjectTool;
use App\Models\Tool;
use App\Models\WalletTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProjectController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //

        $user = Auth::user();

        $projectsQuery = Project::with(['category', "applicants"])->orderByDesc('id');

        if($user->hasRole('project_client')) {
            $projectsQuery->whereHas('owner', function ($query) use ($user) {
                $query->where('client_id', $user->id);
            });
        }

        $projects = $projectsQuery->paginate(10);

        return view('admin.projects.index', compact('projects'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
        $categories = Category::all();
        return view('admin.projects.create', compact('categories'));
    }

    public function tools(Project $project) {
        if($project->client_id != auth()->id()) {
            abort(403, "You are not authorized");
        }
        $tools = Tool::all();
        return view('admin.projects.tools', compact('tools', 'project'));
    }

    public function tools_store(StoreToolProjectRequest $request, Project $project) {
        // if($project->client_id != auth()->id()) {
        //     abort(403, "You are not authorized");
        // }


        DB::transaction(function () use ($request, $project) {
            $validated = $request->validated();
            $validated["project_id"] = $project->id;

            $toolProject = ProjectTool::firstOrCreate($validated);
        });

        return redirect()->route('admin.projects.tools', $project->id);


    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreProjectRequest $request)
    {
        //
        $user = Auth::user();
        $balance = $user->wallet->balance;


        if ($request->input("budget") > $balance ) {
            return redirect()->back()->with(["budget" => "Wallet balance tidak mencukupi"]);
        };


        DB::transaction(function () use ($request, $user) {
            $user->wallet->decrement("balance", $request->input("budget"));
            $projectWalletTransactions = WalletTransaction::create([
                "type" => "Project Cost",
                'amount' => $request->input("budget"),
                'is_paid' => true,
                'user_id' => $user->id,
            ]);

            $validated = $request->validated();

            if($request->hasFile('thumbnail')) {
                $thumbnailPath = $request->file('thumbnail')->store('thumbnails', 'public');
                $validated['thumbnail'] = $thumbnailPath;
            }

            $validated['slug'] = Str::slug($validated['name']);
            $validated["has_finished"] = false;
            $validated["has_started"] = false;
            $validated['client_id'] = $user->id;

            $newProject = Project::create($validated);

        });

        return redirect()->route('admin.projects.index');

    }

    /**
     * Display the specified resource.
     */
    public function show(Project $project)
    {
        //
        return view('admin.projects.show', compact('project'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Project $project)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Project $project)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Project $project)
    {
        //
    }
}
