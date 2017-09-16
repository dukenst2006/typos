<?php

namespace App\Http\Controllers\Training;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

use DB, Auth;

use App\Traits\CreateLectionNonce;

class TrainingController extends Controller
{
  use CreateLectionNonce;

  /**
    * Middlewares:
    *  - auth
    *
    */
  public function __construct()
  {
    $this->middleware('auth');
  }

  /**
    * show training view
    *
    * @return view
    */
  public function showTraining()
  {
    return view('training.app', ['dataURI' => '/training', 'keyboardLayout' => 'de-de']);
  }

  /**
    * return words for training
    *
    * @return JSON
    */
  public function getWords()
  {
    $number_of_rows = DB::table('words_de')->select(DB::raw('COUNT(*) as count'))->first()->count;

    // create a list of 10 random positions
    $position_list = [];

    $word_amount = 10;
    for($i = 0; $i < $word_amount; $i++) {

      $position_list[] = rand(1, $number_of_rows);
    }

    // get 10 random words from database
    $query = 'SELECT word FROM words_de WHERE id IN (' . implode(',', $position_list) . ') LIMIT ' . $word_amount;
    $words_raw = DB::select($query);
    $words = [];

    foreach($words_raw as $word_raw) {

      $words[] = $word_raw->word;
    }

    // store Lection nonce in session
    $characterAmount = strlen(implode($words, ''));
    $this->createLectionNonce(Auth::user()->id_user, $characterAmount);

    // return array (laravel will automatically encode it to JSON)
    return
    [
      'meta' => [
          'mode' => 'expand', // valid modes: expand, prepared and block (@see resources/assets/js/app/sequence.js)
        ],
      'lines' => $words,
    ];
  }
}
