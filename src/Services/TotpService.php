<?php

declare(strict_types=1);

namespace App\Services;

use App\Config\App;
use RobThree\Auth\TwoFactorAuth;

final class TotpService
{
    private static ?TwoFactorAuth $tfa = null;

    private static function getTfa(): TwoFactorAuth
    {
        if (self::$tfa === null) {
            self::$tfa = new TwoFactorAuth(
                App::get('APP_NAME', 'Ollama Gateway')
            );
        }
        return self::$tfa;
    }

    public static function generateSecret(): string
    {
        return self::getTfa()->createSecret();
    }

    public static function getQrCodeUrl(string $label, string $secret): string
    {
        return self::getTfa()->getQRCodeImageAsDataUri($label, $secret);
    }

    public static function verifyCode(string $secret, string $code): bool
    {
        return self::getTfa()->verifyCode($secret, $code, 2);
    }

    public static function generateRecoveryCodes(int $count = 8): array
    {
        $codes = [];
        for ($i = 0; $i < $count; $i++) {
            $codes[] = bin2hex(random_bytes(4)) . '-' . bin2hex(random_bytes(4));
        }
        return $codes;
    }

    public static function verifyRecoveryCode(string $code, array &$storedCodes): bool
    {
        $index = array_search($code, $storedCodes);
        if ($index !== false) {
            array_splice($storedCodes, $index, 1);
            return true;
        }
        return false;
    }
}
