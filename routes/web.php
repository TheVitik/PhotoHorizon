<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::get('/replace-uuids', function (Request $request) {
    $filePath = base_path('script.txt');

    if (!file_exists($filePath)) {
        return response()->json(['error' => 'Файл script.txt не знайдено'], 404);
    }

    $content = file_get_contents($filePath);

    // Масив для збереження замін
    $replacements = [];

    // Регулярка шукає слова, що починаються на "uu" і містять літери, цифри, підкреслення
    $pattern = '/\buu\w*\b/';

    $newContent = preg_replace_callback($pattern, function ($matches) use (&$replacements) {
        $oldKey = $matches[0];
        if (!isset($replacements[$oldKey])) {
            $replacements[$oldKey] = (string) Str::uuid();
        }
        return $replacements[$oldKey];
    }, $content);

    file_put_contents($filePath, $newContent);

    return response()->json([
      'message' => 'Заміна uu-ключів завершена',
      'replacements' => $replacements,
    ]);
});
