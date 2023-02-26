<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreUpdateQuoteRequest extends FormRequest
{
	public function rules()
	{
		return [
			'quote_en'                        => ['required', 'max:600'],
			'quote_ka'                        => ['required', 'max:600'],
			'movie_title_en'                  => ['required', 'max:255'],
			'movie_title_ka'                  => ['required', 'max:255'],
			'thumbnail'                       => 'image|mimes:jpg,png,jpeg,gif,svg|max:2048',
			'quote_id'                        => 'required',
		];
	}
}
