<?php

namespace App\Http\Controllers;

use Http;
use Illuminate\Http\Request;
use App\Record;
use App\Genre;
use App\Helpers\Json;

class ShopController extends Controller
{
    // Master Page: http://vinyl_shop.test/shop or http://localhost:3000/shop
    public function index(Request $request)
    {
        $genre_id = $request->input('genre_id') ?? '%'; //OR $genre_id = $request->genre_id ?? '%';
        $artist_title = '%' . $request->input('artist') . '%'; // OR $artist_title = '%' . $request->artist . '%';
        $records = Record::with('genre')
            ->where(function ($query) use ($artist_title, $genre_id) {
                $query->where('artist', 'like', $artist_title)
                    ->where('genre_id', 'like', $genre_id);
            })
            ->orWhere(function ($query) use ($artist_title, $genre_id) {
                $query->where('title', 'like', $artist_title)
                    ->where('genre_id', 'like', $genre_id);
            })
            ->orderBy('artist', 'asc')
            ->paginate(12)
            ->appends(['artist'=> $request->input('artist'), 'genre_id' => $request->input('genre_id')])
        ;
        foreach ($records as $record) {
            $record->cover = $record->cover ?? "https://coverartarchive.org/release/$record->title_mbid/front-250.jpg";
        }
        $genres = Genre::orderBy('name', 'asc')
            ->has('records')
            ->withCount('records')
            ->get()
            ->transform(function ($item, $key) {
                // Set first letter of name to uppercase and add the counter
                $item->name = ucfirst($item->name) . ' (' . $item->records_count . ')';
                // Remove all fields that you don't use inside the view
                unset($item->created_at, $item->updated_at, $item->records_count);
                return $item;
            });
        $result = compact('genres', 'records');     // $result = ['genres' => $genres, 'records' => $records]
        Json::dump($result);                    // open http://vinyl_shop.test/shop?json
        return view('shop.index', $result);     // add $result as second parameter
    }

    // Detail Page: http://vinyl_shop.test/shop/{id} or http://localhost:3000/shop/{id}
    public function show($id)
    {
        $record = Record::with('genre')->findOrFail($id);
        $record->cover = $record->cover ?? "https://coverartarchive.org/release/$record->title_mbid/front-250.jpg";
        $record->title = $record->artist . ' - ' . $record->title;
        $record->recordUrl = 'https://musicbrainz.org/ws/2/release/' . $record->title_mbid . '?inc=recordings+url-rels&fmt=json';
        $record->btnClass = $record->stock > 0 ? 'btn-outline-success' : 'btn-outline-danger';
        $record->genreName = $record->genre->name;
        unset($record->genre_id, $record->artist, $record->created_at, $record->updated_at, $record->title_mbid, $record->genre);
        // get record info and convert it to json
        $response = Http::get($record->recordUrl)->json();
        $tracks = $response['media'][0]['tracks'];
        $tracks = collect($tracks)
            ->transform(function ($item, $key) {
                $item['length'] = gmdate('i:s', $item['length']/1000);
                unset($item['id'], $item['recording'], $item['number']);
                return $item;
            });

        $result = compact('tracks', 'record');
        Json::dump($result);
        return view('shop.show', $result);  // Pass $result to the view
    }

    public function alternative(){
        $genres = Genre::orderBy('name', 'asc')
            ->has('records')
            ->withCount('records')
            ->get()
            ->load(['records' => function ($q) {
                 $q->orderBy('artist', 'asc');
                }]);
        $result = compact('genres');
        Json::dump($result);
        return view('shop.alternative', $result);

    }


}
