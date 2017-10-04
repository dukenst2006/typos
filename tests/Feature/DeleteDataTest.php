<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;

use App\Models\User;
use Auth;

class DeleteDataTest extends TestCase
{
  use DatabaseTransactions;

  const URI_PREFERENCES     = '/preferences';
  const URI_PARAMS          = '?view=account';
  const URI_DELETE_ACCOUNT  = '/preferences/account/delete';
  const PASSWORD            = 'testtest';

  /**
    * prepares session and testing env
    *
    * @return User (the user that is currently logged in)
    */
  protected function prepareSession()
  {
    # prepare session and mail
    session()->start();

    # log test user in
    $user = User::getTestUser();
    Auth::login($user);
    $this->assertTrue(Auth::check());

    # make GET request to preferences
    $response = $this->get(self::URI_PREFERENCES . self::URI_PARAMS);
    $response->assertStatus(200);

    return $user;
  }

  /**
   * test deleting user account
   *
   */
  public function testDeleteUserAccount()
  {
    $user = $this->prepareSession();

    $response = $this->call('POST', self::URI_DELETE_ACCOUNT, [
      '_token'    => csrf_token(),
      'password'  => self::PASSWORD,
    ]);

    $response->assertStatus(302);
    $response->assertRedirect('/');
    $response->assertSessionHas('notification-success');

    $this->assertFalse(Auth::check());

    $this->assertDatabaseMissing('users', [
      'username' => $user->username
    ]);
  }

  /**
   * test deleting user account with wrong password
   *
   */
  public function testDeleteUserAccountWithWrongPassword()
  {
    $user = $this->prepareSession();

    $response = $this->call('POST', self::URI_DELETE_ACCOUNT, [
      '_token'    => csrf_token(),
      'password'  => 'wrongPass',
    ]);

    $response->assertStatus(302);
    $response->assertRedirect(self::URI_PREFERENCES . self::URI_PARAMS);
    $response->assertSessionHasErrors(['password'], null, 'account');

    $this->assertTrue(Auth::check());

    $this->assertDatabaseHas('users', [
      'username' => $user->username
    ]);
  }

  /**
   * test deleting user account with no password
   *
   */
  public function testDeleteUserAccountWithNoPassword()
  {
    $user = $this->prepareSession();

    $response = $this->call('POST', self::URI_DELETE_ACCOUNT, [
      '_token'    => csrf_token(),
      'password'  => '',
    ]);

    $response->assertStatus(302);
    $response->assertRedirect(self::URI_PREFERENCES . self::URI_PARAMS);
    $response->assertSessionHasErrors(['password'], null, 'account');

    $this->assertTrue(Auth::check());

    $this->assertDatabaseHas('users', [
      'username' => $user->username
    ]);
  }
}
