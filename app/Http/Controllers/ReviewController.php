<?php

namespace App\Http\Controllers;

use App\Models\Review;
use Illuminate\Http\Request;

class ReviewController extends Controller
{

    public function index()
    {
        $reviews = Review::with('user:id,full_name')
            ->latest('created_at')
            ->paginate(10);

        return response()->json($reviews);
    }

    public function store(Request $request)
    {
        $request->validate([
            'rating'  => 'required|integer|min:1|max:5',
            'comment' => 'required|string',
        ]);

        $user = $request->user();

        $review = Review::create([
            'user_id'       => $user->id,
            'reviewer_name' => $user->full_name,
            'rating'        => $request->rating,
            'comment'       => $request->comment,
        ]);

        return response()->json([
            'message' => 'Review posted successfully',
            'review'  => $review,
        ], 201);
    }
}
