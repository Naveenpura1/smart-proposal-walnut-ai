<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ProposalController extends Controller
{

    public function create()
    {
        return view('proposals.create');
    }
    
    public function store(Request $request) {
        $validated = $request->validate([
            'client_name' => 'required|string|max:255',
            'industry' => 'required|string',
            'pain_points' => 'required|string',
            'deal_size' => 'required|numeric',
        ]);

        // Placeholder for Walnut AI integration
        $generatedContent = "Generated Proposal for " . $validated['client_name'] . 
                            "\nIndustry: " . $validated['industry'] . 
                            "\nSolutions for: " . $validated['pain_points'];

        $proposal = auth()->user()->proposals()->create(array_merge(
            $validated, 
            ['generated_content' => $generatedContent]
        ));

        return redirect()->route('dashboard')->with('success', 'Proposal created successfully!');
    }
}
