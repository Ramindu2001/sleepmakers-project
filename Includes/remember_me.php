<?php
//"Remember me" cookies that cannot be forged.
//
//The remember-me cookies used to hold the plain user id and shop id (remember_me_synnex=5), and
//authcheck.php logged the browser in as whoever the cookie named - so anyone could become any
//user, Admin included, just by setting that cookie. They also used path "/", so every other
//application on this domain received them and was logged in by them too.
//
//The cookies now carry a token signed with a secret that only this server knows (HMAC-SHA256):
//
//  remember_me_token       v1.<user id>.<expires>.<signature over the user id, expiry and password hash>
//  remember_me_shop_token  v1.<shop id>.<expires>.<signature over the user id, shop id and expiry>
//
//- a token cannot be created or altered without the secret, and cannot be extended
//- changing a user's password, or disabling the user, signs every remembered browser out
//- a remembered shop is restored only while that user may still open that shop
//- the cookies are HttpOnly and scoped to this application's folder
//- the old cookies are never read again: those browsers simply log in once more
//
//The secret is Includes/remember_me_secret.php. It is created on first use and kept out of git;
//deleting it signs every remembered browser out.
class RememberMe extends Dbh
{
    const USER_COOKIE = 'remember_me_token';
    const SHOP_COOKIE = 'remember_me_shop_token';
    const LIFETIME    = 2592000;    //30 days, as before

    //the cookies this replaces - only ever cleared, never trusted
    const LEGACY_COOKIES = ['remember_me_synnex', 'remember_me_synnex_shop'];

    private static $secret = null;

    //remember this user in this browser. Returns false when nothing was stored.
    public function rememberUser($user_id)
    {
        $user = $this->getActiveUser($user_id);
        if($user === null)
        {
            return false;
        }//no such active user

        $expires = time() + self::LIFETIME;
        $signature = $this->sign("user|" . $user['USID'] . "|" . $expires . "|" . $user['UserPwd']);
        if($signature === null)
        {
            return false;
        }//no secret available: remember-me stays off

        return self::setCookie(self::USER_COOKIE, "v1." . $user['USID'] . "." . $expires . "." . $signature, $expires);
    }//remember user

    //the user id this browser is remembered as, or null
    public function userFromCookie()
    {
        $token = self::readToken(self::USER_COOKIE);
        if($token === null)
        {
            return null;
        }//no valid looking token

        $user = $this->getActiveUser($token['id']);
        if($user === null)
        {
            return null;
        }//user removed or disabled

        $expected = $this->sign("user|" . $token['id'] . "|" . $token['expires'] . "|" . $user['UserPwd']);
        if($expected === null || !hash_equals($expected, $token['signature']))
        {
            return null;
        }//forged, altered, or signed before a password change

        return (int)$user['USID'];
    }//user from cookie

    //remember the shop this user works in, in this browser
    public function rememberShop($user_id, $shop_id)
    {
        if(!$this->canAccessShop($user_id, $shop_id))
        {
            return false;
        }//not a shop this user may open

        $expires = time() + self::LIFETIME;
        $signature = $this->sign("shop|" . (int)$user_id . "|" . (int)$shop_id . "|" . $expires);
        if($signature === null)
        {
            return false;
        }//no secret available

        return self::setCookie(self::SHOP_COOKIE, "v1." . (int)$shop_id . "." . $expires . "." . $signature, $expires);
    }//remember shop

    //the shop this user was working in, or null
    public function shopFromCookie($user_id)
    {
        $token = self::readToken(self::SHOP_COOKIE);
        if($token === null)
        {
            return null;
        }//no valid looking token

        $expected = $this->sign("shop|" . (int)$user_id . "|" . $token['id'] . "|" . $token['expires']);
        if($expected === null || !hash_equals($expected, $token['signature']))
        {
            return null;
        }//forged, altered, or remembered for another user

        if(!$this->canAccessShop($user_id, $token['id']))
        {
            return null;
        }//no longer allowed into that shop

        return $token['id'];
    }//shop from cookie

    //may this user open this shop? The same rule that builds the shop list on the dashboard
    //(Company::getCompanyByUser): UserType 1 opens every shop, everyone else only the active
    //shops they are assigned to.
    public function canAccessShop($user_id, $shop_id)
    {
        $user_id = filter_var($user_id, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        $shop_id = filter_var($shop_id, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if($user_id === false || $shop_id === false)
        {
            return false;
        }//not ids

        $stmt = $this->connect()->prepare("SELECT UserType FROM user WHERE USID = ? AND UserStat = 1;");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if($user === false)
        {
            return false;
        }//no such active user

        if($user['UserType'] == 1)
        {
            $sql = "SELECT shop.SHID FROM shop
            INNER JOIN company ON company.CMID = shop.Company_CMID
            WHERE shop.SHID = ? LIMIT 1;";
            $params = [$shop_id];
        }//every shop
        else
        {
            $sql = "SELECT shop.SHID FROM shopusers
            INNER JOIN shop ON shop.SHID = shopusers.shop_SHID
            INNER JOIN company ON company.CMID = shop.Company_CMID
            WHERE shopusers.user_USID = ? AND shopusers.shop_SHID = ? AND shop.ShopStat = 1 LIMIT 1;";
            $params = [$user_id, $shop_id];
        }//assigned active shops

        $stmt = $this->connect()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch(PDO::FETCH_ASSOC) !== false;
    }//can access shop

    //sign this browser out: the remember-me cookies, and the old ones they replace
    public static function forget()
    {
        self::forgetShop();
        self::clearCookie(self::USER_COOKIE);
        self::clearCookie(self::LEGACY_COOKIES[0]);
    }//forget

    //forget only the remembered shop (switching shop)
    public static function forgetShop()
    {
        self::clearCookie(self::SHOP_COOKIE);
        self::clearCookie(self::LEGACY_COOKIES[1]);
    }//forget shop

    //------------------------------------------------------------------------------------------

    private function getActiveUser($user_id)
    {
        $user_id = filter_var($user_id, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if($user_id === false)
        {
            return null;
        }//not an id

        $stmt = $this->connect()->prepare("SELECT USID, UserPwd FROM user WHERE USID = ? AND UserStat = 1;");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        return $user === false ? null : $user;
    }//get active user

    //the token in a cookie, when it is well formed and not expired - NOT yet verified
    private static function readToken($cookie)
    {
        if(!isset($_COOKIE[$cookie]) || !is_string($_COOKIE[$cookie]))
        {
            return null;
        }//no cookie

        if(!preg_match('/^v1\.([1-9][0-9]{0,9})\.([0-9]{10})\.([0-9a-f]{64})$/', $_COOKIE[$cookie], $match))
        {
            return null;
        }//not a token

        if((int)$match[2] < time())
        {
            return null;
        }//expired

        return ['id' => (int)$match[1], 'expires' => (int)$match[2], 'signature' => $match[3]];
    }//read token

    private function sign($data)
    {
        $secret = self::secret();
        return $secret === null ? null : hash_hmac('sha256', $data, $secret);
    }//sign

    //the server secret, created on first use. Null when it cannot be read or created, in which
    //case remember-me is simply off: nobody is logged in by a cookie.
    private static function secret()
    {
        if(self::$secret !== null)
        {
            return self::$secret;
        }//already loaded

        $file = __DIR__ . '/remember_me_secret.php';
        if(!is_file($file))
        {
            //'x' fails when another request created the file first; that request's secret is used
            $handle = @fopen($file, 'x');
            if($handle !== false)
            {
                fwrite($handle, "<?php\n"
                    . "//Signs the remember-me cookies (Includes/remember_me.php). Private: never commit or share it.\n"
                    . "//Deleting this file signs every remembered browser out.\n"
                    . "return '" . bin2hex(random_bytes(32)) . "';\n");
                fclose($handle);
            }//created
        }//first use

        try
        {
            $value = is_file($file) ? (include $file) : null;
        }
        catch (Throwable $e)
        {
            $value = null;   //being written by another request right now
        }//catch

        if(!is_string($value) || !preg_match('/^[0-9a-f]{64}$/', $value))
        {
            return null;
        }//no usable secret

        self::$secret = $value;
        return self::$secret;
    }//secret

    private static function setCookie($name, $value, $expires)
    {
        if(headers_sent())
        {
            return false;
        }//too late to send a cookie

        return setcookie($name, $value, [
            'expires'  => $expires,
            'path'     => self::cookiePath(),
            'secure'   => self::isHttps(),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }//set cookie

    private static function clearCookie($name)
    {
        if(headers_sent())
        {
            return;
        }//too late to send a cookie

        //the legacy cookies were set on "/", the new ones on this application's folder
        foreach(array_unique([self::cookiePath(), '/']) as $path)
        {
            setcookie($name, '', ['expires' => time() - 3600, 'path' => $path]);
        }//each path
    }//clear cookie

    //this application's folder in the URL, e.g. /sleepmakers/Cloud_POS/ - worked out from the
    //running script so it is right wherever the application is deployed. Falls back to "/".
    private static function cookiePath()
    {
        $root   = realpath(dirname(__DIR__));
        $file   = isset($_SERVER['SCRIPT_FILENAME']) ? realpath($_SERVER['SCRIPT_FILENAME']) : false;
        $script = isset($_SERVER['SCRIPT_NAME']) ? str_replace('\\', '/', $_SERVER['SCRIPT_NAME']) : '';
        if($root === false || $file === false || $script === '')
        {
            return '/';
        }//cannot tell

        $root = str_replace('\\', '/', $root);
        $file = str_replace('\\', '/', $file);
        if(stripos($file, $root . '/') !== 0)
        {
            return '/';
        }//script outside the application

        $relative = substr($file, strlen($root));   //e.g. /Controller/userController.php
        if(strlen($script) <= strlen($relative) || strcasecmp(substr($script, -strlen($relative)), $relative) !== 0)
        {
            return '/';
        }//URL does not end with the script's path

        return substr($script, 0, -strlen($relative)) . '/';
    }//cookie path

    private static function isHttps()
    {
        return (!empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off')
            || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443);
    }//is https

}//class remember me
