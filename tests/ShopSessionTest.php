<?php
use PHPUnit\Framework\TestCase;

final class ShopSessionTest extends TestCase
{
    private function user($id)
    {
        return ['USID' => $id, 'UserName' => 'user' . $id, 'UserPwd' => 'hash', 'PwdChange' => 'token', 'UserType' => 0];
    }

    public function test_same_user_enters_the_shop_and_keeps_the_session()
    {
        $session = ['user_id' => 5, 'user' => [['USID' => 5]], 'remember_me' => 1, 'csrf_token' => 'abc'];

        $switched = shop_session_enter($session, $this->user(5), 2);

        $this->assertFalse($switched);
        $this->assertSame(2, $session['shop_id']);
        $this->assertSame(1, $session['remember_me']);
        $this->assertSame(1, $session['loading']);
        $this->assertSame(1, $session['toast']);
    }

    public function test_another_user_takes_over_with_a_clean_session()
    {
        $session = ['user_id' => 5, 'user' => [['USID' => 5]], 'remember_me' => 1, 'csrf_token' => 'abc', 'status' => 1];

        $switched = shop_session_enter($session, $this->user(7), '3');

        $this->assertTrue($switched);
        $this->assertSame(7, $session['user_id']);
        $this->assertSame('user7', $session['user'][0]['UserName']);
        $this->assertArrayNotHasKey('UserPwd', $session['user'][0]);
        $this->assertArrayNotHasKey('PwdChange', $session['user'][0]);
        $this->assertArrayNotHasKey('remember_me', $session);
        $this->assertArrayNotHasKey('status', $session);
        $this->assertSame('abc', $session['csrf_token']);
        $this->assertSame(3, $session['shop_id']);
    }

    public function test_works_on_the_real_session_array()
    {
        $_SESSION = ['user_id' => 5];
        shop_session_enter($_SESSION, $this->user(7), 4);
        $this->assertSame(7, $_SESSION['user_id']);
        $this->assertSame(4, $_SESSION['shop_id']);
        $_SESSION = [];
    }
}
