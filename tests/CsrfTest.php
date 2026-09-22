<?php
use PHPUnit\Framework\TestCase;

final class CsrfTest extends TestCase
{
    protected function setUp(): void { $_SESSION = []; }
    protected function tearDown(): void { $_SESSION = []; }

    public function test_token_is_stable_within_a_session()
    {
        $token = csrf_token();
        $this->assertMatchesRegularExpression('/^[0-9a-f]{64}$/', $token);
        $this->assertSame($token, csrf_token());
    }

    public function test_only_the_sessions_token_validates()
    {
        $token = csrf_token();
        $this->assertTrue(csrf_validate($token));
        $this->assertFalse(csrf_validate(str_repeat('0', 64)));
        $this->assertFalse(csrf_validate(''));
        $this->assertFalse(csrf_validate(null));
        $this->assertFalse(csrf_validate(['x']));
    }

    public function test_nothing_validates_before_a_token_was_issued()
    {
        $this->assertFalse(csrf_validate(''));
        $this->assertFalse(csrf_validate(str_repeat('0', 64)));
    }
}
