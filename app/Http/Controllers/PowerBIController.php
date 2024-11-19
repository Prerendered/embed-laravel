<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class PowerBIController extends Controller
{
    public function getEmbedInfo()
    {
        try {
            // Retrieve environment variables
            $tenantId = env('TENANT_ID');
            $clientId = env('CLIENT_ID');
            $clientSecret = env('CLIENT_SECRET');
            $groupId = env('GROUP_ID');
            $reportId = env('REPORT_ID');

            // Step 1: Get the Azure AD token
            $authUrl = "https://login.microsoftonline.com/{$tenantId}/oauth2/v2.0/token";

            $authResponse = Http::asForm()->post($authUrl, [
                'grant_type'    => 'client_credentials',
                'client_id'     => $clientId,
                'client_secret' => $clientSecret,
                'scope'         => 'https://analysis.windows.net/powerbi/api/.default',
            ]);

            if (!$authResponse->successful()) {
                $errorData = $authResponse->json();
                $errorMessage = $errorData['error_description'] ?? $errorData['error'] ?? 'Unknown error';
                throw new \Exception("Authentication error: $errorMessage");
            }

            $accessToken = $authResponse->json()['access_token'];

            // Step 2: Generate the embed token
            $embedUrl = "https://api.powerbi.com/v1.0/myorg/groups/{$groupId}/reports/{$reportId}/GenerateToken";

            $embedResponse = Http::withToken($accessToken)
                ->post($embedUrl, [
                    'accessLevel' => 'View',
                ]);

            if (!$embedResponse->successful()) {
                $errorData = $embedResponse->json();
                $errorMessage = $errorData['error']['message'] ?? json_encode($errorData);
                throw new \Exception("Embed token error: $errorMessage");
            }

            $embedToken = $embedResponse->json()['token'];

            // Step 3: Get the embed URL
            $reportUrl = "https://api.powerbi.com/v1.0/myorg/groups/{$groupId}/reports/{$reportId}";

            $reportResponse = Http::withToken($accessToken)
                ->get($reportUrl);

            if (!$reportResponse->successful()) {
                $errorData = $reportResponse->json();
                $errorMessage = $errorData['error']['message'] ?? json_encode($errorData);
                throw new \Exception("Report fetch error: $errorMessage");
            }

            $embedReportUrl = $reportResponse->json()['embedUrl'];

            // Step 4: Send the embed token and URL to the frontend
            return response()->json([
                'embedToken' => $embedToken,
                'embedUrl'   => $embedReportUrl,
            ]);
        } catch (\Exception $e) {
            \Log::error('Error generating embed token: ' . $e->getMessage());
            return response()->json(['error' => 'Error generating embed token'], 500);
        }
    }
}
