<?php

namespace App\Http\Controllers;

use App\Events\NotificationStored;
use App\Http\Requests\StoreQuoteRequest;
use App\Http\Requests\StoreUpdateQuoteRequest;
use App\Models\Comment;
use App\Models\Movie;
use App\Models\Notification;
use App\Models\Quote;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class QuoteController extends Controller
{
	public function index(): JsonResponse
	{
		if (Quote::latest()->first() !== null)
		{
			$start = (int) request()->query('start');
			$quotes = Quote::all();

			$sortedQuotes = collect($quotes)->sortDesc()->slice($start)->take(3)->flatten();

			$movies = Movie::all();

			if (request()->query('start') === '0')
			{
				$responseArray = [$sortedQuotes, $movies];
			}
			else
			{
				$responseArray = [$sortedQuotes];
			}

			return response()->json($responseArray, 200);
		}

		return response()->json([[]], 200);
	}

	public function search(Request $request): JsonResponse
	{
		$quotes = quote::filter(['search' => $request['search']])->latest()->get();

		return response()->json($quotes, 200);
	}

	public function show(): JsonResponse
	{
		$quote = Quote::where('id', request()->query('quote_id'))->first();
		return response()->json($quote, 200);
	}

	public function store(StoreQuoteRequest $storedRequest): JsonResponse
	{
		$user = auth()->user();
		$request = $storedRequest->validated();

		$quote = Quote::create([
			'quote' => [
				'en' => $request['quote_en'],
				'ka' => $request['quote_ka'],
			],
			'thumbnail'    => Storage::url($storedRequest->file('thumbnail')->store('quote_thumbnails')),
			'movie_id'     => $request['movie_id'],
			'user_id'      => $user->id,
			'movie_title'  => [
				'en' => $request['movie_title_en'],
				'ka' => $request['movie_title_ka'],
			],
		]);

		$createdQuote = Quote::where('id', $quote->id)->first();

		return response()->json($createdQuote, 201);
	}

	public function edit(StoreUpdateQuoteRequest $updatedRequest): JsonResponse
	{
		$request = $updatedRequest->validated();
		$quote = Quote::where('id', $request['quote_id'])->first();

		$quote->update([
			'quote' => [
				'en' => $request['quote_en'],
				'ka' => $request['quote_ka'],
			],
			'movie_title'  => [
				'en' => $request['movie_title_en'],
				'ka' => $request['movie_title_ka'],
			],
		]);
		if (isset($request['thumbnail']))
		{
			$quote->update([
				'thumbnail'    => Storage::url($updatedRequest->file('thumbnail')->store('quote_thumbnails')),
			]);
		}

		return response()->json($quote, 201);
	}

	public function storeComment(): JsonResponse
	{
		$userId = auth()->user()->id;
		$quoteId = request()->input('quote_id');
		$storedUserId = request()->input('user_id');
		$comment = request()->input('comment');

		$comment = Comment::create([
			'comment'            => $comment,
			'user_id'            => $userId,
			'quote_id'           => $quoteId,
		]);

		$comment->load('user');

		if ($comment->user_id !== (int) $storedUserId)
		{
			$notification = Notification::create([
				'is_notification_on'  => true,
				'notificatable_id'    => $comment->id,
				'notificatable_type'  => 'App\Models\Comment',
				'user_id'             => $storedUserId,
			]);

			$createdNotification = Notification::where('id', $notification->id)->first();

			event(new NotificationStored($createdNotification));
		}

		return response()->json($comment, 201);
	}

	public function delete(Request $request): JsonResponse
	{
		$quote = Quote::where('id', $request['quote_id'])->first();
		$quote->delete();

		return response()->json(['success' => 'quote deleted successfully'], 200);
	}
}
