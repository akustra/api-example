<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

/*
LIX = A/B + (C x 100)/A, where

A = Number of words
B = Number of periods (defined by period, colon or capital first letter)
C = Number of long words (More than 6 letters)

*/

class LixController extends Controller
{
    const MAX_LENGTH = 100;

    public function calculate(Request $request)
    {
        $text = $request->get('text', '');
        $appid = $request->get('appid', '');

        // Is user authorized
        if (\Cache::has($appid)) {
            $user = \Cache::get($appid);
        } else {
            $user = User::where('appid', '=', $appid)->first();
        }

        if ($user == null) {
            return response()->json([
                'error' => ['message' => 'Invalid API key.'],
                'meta' => []
            ], 400);
        }

        // Put user to the cache
        if (!\Cache::has($appid)) {
            \Cache::put($appid, $user);
        }

        $lix = $this->lix($text);
        if ($lix <= 0) {
            return response()->json([
                'error' => ['message' => 'Please add at least one full sentence.'],
                'meta' => []
            ], 400);
        }

        $data = [
            'data' => [
                'original_text' => \Str::limit($text, self::MAX_LENGTH),
                'text_length' => \mb_strlen($text),
                'lix_readability' => round($lix, 2)
            ],
            'meta' => []
        ];

        return response()->json($data);
    }

    private function lix(string $text = ''): float
    {
        $words = array_filter(explode(' ', $text));

        // Filter out special characters
        $words = array_map(function ($ith) {
            return trim(str_replace(['.', ';', ',', '-'], ' ', $ith));
        }, $words);

        // Number of words
        $a = count($words);

        $temp = strtolower($text);
        $splitted = str_split($temp);

        // Number of periods
        $b = count(array_filter($splitted, function ($ith) {
            return $ith == '.' || $ith == ';';
        }));

        // Number of long words (More than 6 letters)
        $c = count(array_filter($words, function ($ith) {
            return mb_strlen($ith) > 6;
        }));

        if ($a == 0 || $b == 0)
            return -1;

        $lix = $a / $b + ($c * 100) / $a;

        return $lix;
    }
}
