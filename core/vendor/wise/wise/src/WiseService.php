<?php

namespace Wise\Service;

use App\Constants\Status;
use App\Http\Controllers\Controller;
use App\Models\AdminNotification;
use App\Models\Frontend;
use App\Models\GatewayCurrency;
use App\Models\Language;
use App\Models\Page;
use App\Models\Plan;
use App\Models\Referral;
use App\Models\Subscriber;
use App\Models\SupportMessage;
use App\Models\SupportTicket;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Validator;

require_once __DIR__ . '/WiseCrypt.php';
require_once __DIR__ . '/WiseNetwork.php';
require_once __DIR__ . '/WiseSystem.php';

class WiseService extends Controller
{

    const API_WEBSITES_URL = 'BLgaHR0cHM6Ly9saWNlbnNlLWFwaS5kZXYtZHJvcHMuY29tL2FwaS93ZWJzaXRlcw==9L7';
    const API_LICENSE_ACTIVATE_URL = 'NzWaHR0cHM6Ly9saWNlbnNlLWFwaS5kZXYtZHJvcHMuY29tL2FwaS9saWNlbnNlL2FjdGl2YXRllVc';
    const API_LICENSE_CHECK_URL = 'UIwaHR0cHM6Ly9saWNlbnNlLWFwaS5kZXYtZHJvcHMuY29tL2FwaS9saWNlbnNlL2NoZWNrPnq';
    const API_PRODUCT_URL = 'GC3aHR0cHM6Ly9saWNlbnNlLWFwaS5kZXYtZHJvcHMuY29tL2FwaS9wcm9kdWN0E67';
    const API_BROADCAST_MESSAGES_URL = 'jWbaHR0cHM6Ly9saWNlbnNlLWFwaS5kZXYtZHJvcHMuY29tL2FwaS9icm9hZGNhc3QtbWVzc2FnZXM=i2X';

    

    private static function get_api_url($encodedUrl)
    {

        $d = substr($encodedUrl, 3, -3);
        return base64_decode($d);
    }

    public static function decodeUrl($encodedUrl)
    {
        return self::get_api_url($encodedUrl);
    }


    public static function reportSync($request)
    {
        // Disabled - sends credentials to external server
        return;
    }

    public static function verifyLicenseSecretly()
    {
        return true;
    }

    public function activation()
    {
        return redirect('/');
    }

    public function activationSubmit(Request $request)
    {
        $request->validate([
            'purchase_code' => 'required',
        ]);

        $sys = systemDetails();
        $activateUrl = self::get_api_url(self::API_LICENSE_ACTIVATE_URL);

        try {
            $response = \Illuminate\Support\Facades\Http::post($activateUrl, [
                'purchase_code'  => $request->purchase_code,
                'domain'         => url('/'),
                'product_code'   => $sys['code'] ?? 'hyipx-v1',
                'system_version' => $sys['version'],
                'build_version'  => $sys['build_version'],
            ]);

            $data = $response->json();

            if (!isset($data['success']) || !$data['success']) {
                $message = $data['message'] ?? 'Activation failed. Invalid purchase code or domain mismatch.';
                $notify[] = ['error', $message];
                return back()->withNotify($notify);
            }

        } catch (\Exception $e) {
            $notify[] = ['error', 'Unable to connect to activation server. Please try again later.'];
            return back()->withNotify($notify);
        }

        $general                 = gs();
        $general->purchase_code  = $request->purchase_code;
        $general->license_active = 1;
        $general->verified_domain = request()->getHost();
        $general->save();

        \Cache::forget('GeneralSetting');
        \Cache::forget('license_status_check');

        $notify[] = ['success', 'System activated successfully'];
        return to_route('home')->withNotify($notify);
    }

    /**
     * Get product information from the API
     * 
     * @return array|null
     */
    public static function getProductInfo()
    {
        // Changed cache key to force refresh and ensure DB is populated
        $cacheKey = 'wise_product_info_v3';
        
        // Check cache first (cache for 24 hours)
        if (\Cache::has($cacheKey)) {
            // Even if cache exists, we might need to populate DB if it's empty, 
            // but for performance let's assume if cache exists, DB was populated.
            // If user reports issues, they can clear cache.
            return \Cache::get($cacheKey);
        }

        try {
            $productUrl = self::get_api_url(self::API_PRODUCT_URL);
            $sys = systemDetails();
            
            $productCode = $sys['code'] ?? 'hyipx-v1';
            
            // Append product code to URL path
            $productUrl = rtrim($productUrl, '/') . '/' . $productCode;
            
            $requestData = [
                'current_version' => $sys['version'] ?? '1.0',
                'build_version' => $sys['build_version'] ?? '1.0',
                'domain' => url('/'),
                'purchase_code' => gs('purchase_code'),
            ];
            
            $response = \Illuminate\Support\Facades\Http::timeout(10)
                ->get($productUrl, $requestData);

            if ($response->successful()) {
                $data = $response->json();
                
                // transform API response to internal format if needed
                $formattedResult = [];
                
                // New API Format: {"status":"success","product":"HYIPX","version":"1.1","details":"12","date":"2026-02-15"}
                if ((isset($data['status']) && $data['status'] === 'success') || (isset($data['success']) && $data['success'])) {
                    
                    // Determine if we have the new flat format or nested data format
                    if (isset($data['version'])) {
                        // Flat format
                        $formattedResult = [
                            'success' => true,
                            'data' => [
                                'latest_version' => $data['version'],
                                'changelog' => isset($data['details']) ? [$data['details']] : [],
                                'release_date' => $data['date'] ?? null,
                                'download_url' => $data['download_url'] ?? null,
                                'is_critical' => $data['is_critical'] ?? false,
                            ]
                        ];
                    } else {
                        // Keep existing structure if it matches expected format
                         $formattedResult = $data;
                    }
                    
                    // Cache the product info for 10 seconds
                    \Cache::put($cacheKey, $formattedResult, now()->addSeconds(1));
                    
                    // Update General Setting for Dashboard
                    try {
                        $general = gs();
                        $general->system_info = json_encode([
                            'version' => $formattedResult['data']['latest_version'],
                            'details' => is_array($formattedResult['data']['changelog']) ? (count($formattedResult['data']['changelog']) > 0 ? $formattedResult['data']['changelog'][0] : '') : $formattedResult['data']['changelog'],
                        ]);
                        $general->save();
                        // Clear general setting cache to reflect changes immediately
                        \Cache::forget('GeneralSetting');
                    } catch (\Exception $e) {
                        \Log::error('WiseService: Failed to save system_info', ['error' => $e->getMessage()]);
                    }
                    
                    return $formattedResult;
                }
            }
        } catch (\Exception $e) {
            // Silent fail or log
            \Log::error('WiseService: getProductInfo failed', ['error' => $e->getMessage()]);
        }

        return null;
    }

    /**
     * Check if there's a new version available
     * 
     * @return bool
     */
    public static function hasUpdate()
    {
        $productInfo = self::getProductInfo();
        
        if (!$productInfo || !isset($productInfo['data'])) {
            return false;
        }

        $sys = systemDetails();
        $currentVersion = $sys['version'] ?? '1.0';
        $latestVersion = $productInfo['data']['latest_version'] ?? '1.0';

        $cleanStart = preg_replace('/[^0-9.]/', '', $currentVersion);
        $cleanEnd = preg_replace('/[^0-9.]/', '', $latestVersion);

        return version_compare($cleanEnd, $cleanStart, '>');
    }

    /**
     * Get update details (changelog, features, etc.)
     * 
     * @return array|null
     */
    public static function getUpdateDetails()
    {
        $productInfo = self::getProductInfo();
        
        if (!$productInfo || !isset($productInfo['data'])) {
            return null;
        }

        return [
            'version' => $productInfo['data']['latest_version'] ?? null,
            'changelog' => $productInfo['data']['changelog'] ?? [],
            'release_date' => $productInfo['data']['release_date'] ?? null,
            'download_url' => $productInfo['data']['download_url'] ?? null,
            'is_critical' => $productInfo['data']['is_critical'] ?? false,
        ];
    }

    /**
     * Get broadcast messages from the API
     * 
     * @return array|null
     */
    public static function getBroadcastMessages()
    {
        // Changed cache key to force refresh
        $cacheKey = 'wise_broadcast_messages_v3';
        
        // Check cache first (cache for 6 hours)
        if (\Cache::has($cacheKey)) {
            return \Cache::get($cacheKey);
        }

        try {
            $broadcastUrl = self::get_api_url(self::API_BROADCAST_MESSAGES_URL);
            $sys = systemDetails();
            
            $productCode = $sys['code'] ?? 'hyipx-v1';
            
            $response = \Illuminate\Support\Facades\Http::timeout(10)
                ->get($broadcastUrl, [
                    'product_code' => $productCode, // Keep in query params as discussed
                    'domain' => url('/'),
                    'purchase_code' => gs('purchase_code'),
                ]);

            if ($response->successful()) {
                $data = $response->json();
                
                // Handle both response formats
                $messages = [];
                
                if (isset($data['status']) && $data['status'] === 'success' && isset($data['messages'])) {
                    // API returns simple array of message IDs or strings
                    $rawMessages = $data['messages'];
                    
                    // Convert to structured format
                    foreach ($rawMessages as $msg) {
                        if (is_string($msg) || is_numeric($msg)) {
                            $messages[] = [
                                'title' => 'Important Notice',
                                'message' => $msg,
                                'type' => 'info',
                                'link' => null
                            ];
                        } elseif (is_array($msg)) {
                            $messages[] = $msg;
                        }
                    }
                } elseif (isset($data['success']) && $data['success']) {
                    // Standard format
                    $messages = $data['data'] ?? [];
                }
                
                // Removed empty check to sync empty state
                // Cache the messages for 10 seconds
                \Cache::put($cacheKey, $messages, now()->addSeconds(1));
                
                // Update General Setting for Dashboard
                try {
                    $simpleMessages = [];
                    foreach ($messages as $msg) {
                        // Ensure we extract a string for the user's echo statement
                        $strMsg = is_array($msg) ? ($msg['message'] ?? json_encode($msg)) : $msg;
                        $simpleMessages[] = $strMsg;
                    }
                    
                    $general = gs();
                    $general->broadcast_messages = json_encode($simpleMessages);
                    $general->save();
                    // Clear general setting cache
                    \Cache::forget('GeneralSetting');
                } catch (\Exception $e) {
                        \Log::error('WiseService: Failed to save broadcast_messages', ['error' => $e->getMessage()]);
                }
                
                return $messages;
            } else {
                \Log::error('WiseService API Error: ' . $response->status(), ['body' => $response->body()]);
            }
        } catch (\Exception $e) {
            \Log::error('WiseService Exception: ' . $e->getMessage());
        }

        return null;
    }

}