<?php

namespace App\Models;

/**
  * NOTE: this model is only used in the session (since it's only in use for
  * a short period of time and does store only temporary data)
  * A LectionNonce should be created when the user starts a lection/training.
  * This nonce should be passed to the javascript app
  * which sends it back when the results get uploaded (after that the nonce
  * can be deleted).
  * The nonce as well as timestamps, the user's velocity
  * and the amount of characters will be used to determine
  * if a bot is trying to upload fake results or the user is trying to cheat.
  *
  * Also note that the nonce will be created first without an actual token and
  * timestamp, but to store the character amount and other data (such as id_lection).
  * When the user makes his first input, the nonce and timestamp should be assigned.
  */

class LectionNonce
{
  public $token;
  public $character_amount;
  public $timestamp;
  public $data;

  const SESSION_KEY   = '__lection_nonce';
  const TOKEN_LENGTH  = 32;
  const MAX_SPEED     = 600;  // velocity of over 600 is not possible for humans (or is it?)
  const ERROR_MARGIN  = 0.05; // 5% margin of error

  /**
    * Creates a LectionNonce object and stores it in the session
    * NOTE: this will _NOT_ generate a token and a timestamp
    * to generate a token with timestamp, call LectionNonce::generateToken()
    * after this function
    *
    * @param int $characterAmount: the total amount of characters the lections will have
    * @param boolean $isLection (default false): used for xp calculation (lections always 10XP)
    * @return LectionNonce $nonce
    */
  public static function create($characterAmount, $data = [])
  {
    $nonce                    = new LectionNonce;
    $nonce->character_amount  = $characterAmount;
    $nonce->data              = $data;

    session()->put(get_called_class()::SESSION_KEY, $nonce);

    return $nonce;
  }

  /**
    * checks if there is currently a lection nonce object stored inside the session
    * NOTE: LectionNonce object can OR can not have an actual nonce string and timestamp
    *
    * @return boolean $exists
    */
  public static function exists()
  {
    return session()->has(get_called_class()::SESSION_KEY);
  }

  /**
    * generates the actual token string and timestamp
    *
    * @return string $nonce
    */
  public static function generateToken()
  {
    $nonce = session(get_called_class()::SESSION_KEY);

    if(is_null($nonce)) {
      return null;
    }

    $nonce->token     = generateSecureString(get_called_class()::TOKEN_LENGTH);
    $nonce->timestamp = microtime(true);

    return $nonce->token;
  }

  /**
    * Determines if a nonce is valid. Makes additional checks to
    * ensure input is not fraudulent.
    * NOTE: nonce still exists after validation, must be deleted manually
    *
    * @param LectionNonce $nonce: the nonce generated by nonce
    * @param double $velocity: user's velocity (from result uploaded)
    *
    * @return boolean
    */
  public static function validate($token, $velocity)
  {
    $nonce = session(get_called_class()::SESSION_KEY);
    session()->forget(get_called_class()::SESSION_KEY);

    if(is_null($nonce)) {  // nonce does not exist
      return null;
    }

    if($nonce->token !== $token) {  // token is invalid
      return null;
    }

    // calculate total time needed for lection
    $now = microtime(true);
    $timeDiff = $now - $nonce->timestamp;

    if($velocity > get_called_class()::MAX_SPEED) {
      return null;
    }

    // convert keystrokes per min to key. per sec
    $velocity /= 60.0;

    // calculate approx characters typed by user
    $characters = $velocity * $timeDiff;

    // if the user could not type the amount of characters
    // in the calculated timeframe, user must be cheating
    // (minus a 5% margin of error that takes into account delays will submitting results)
    $lower_bound = $nonce->character_amount - ($nonce->character_amount * get_called_class()::ERROR_MARGIN);
    $upper_bound = $nonce->character_amount + ($nonce->character_amount * get_called_class()::ERROR_MARGIN);

    if($characters < $lower_bound || $characters > $upper_bound) {

      return null;

    } else {

      // everything ok, results are valid and can be stored, nonce will be returned
      return $nonce;
    }
  }
}
