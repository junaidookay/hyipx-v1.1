<?php

namespace Wise\Service;

use App\Constants\Status;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class WiseService extends Controller
{
    public static function reportSync($request)
    {
        return;
    }

    public static function verifyLicenseSecretly()
    {
        return true;
    }

    public static function getProductInfo()
    {
        return null;
    }

    public static function hasUpdate()
    {
        return false;
    }

    public static function getUpdateDetails()
    {
        return null;
    }

    public static function getBroadcastMessages()
    {
        return null;
    }

    public function activation()
    {
        return redirect('/');
    }

    public function activationSubmit(Request $request)
    {
        return redirect('/');
    }
}
