<?php

namespace App\Http\Controllers;

use App\Models\Media;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Storage;

class MediaController extends Controller{

    public function storeMedia(Request $request)
    {
        $this->validate($request, [
            'name' => 'required|string',
            'url' => 'required|url'
        ]);

        try{
            Media::create(['name' => $request->name, 'url' => $request->url]);
        }catch(\Exception $exception){
            return response()->json(['status' => false, 'message' => $exception->getMessage()], 500);
        }
        return response()->json(['error' => false, 'message' => 'Media created successfully'], 201);           
            
    }

    public function fetchMedia()
    {
        try{
            
            $music_url = Media::all('url')->random(1);

        }catch(\Exception $exception)
        {
            return response()->json(['status' => false, 'message' => $exception->getMessage()], 500);
        }
        return $music_url;
    }

}