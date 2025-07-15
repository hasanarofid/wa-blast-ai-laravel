<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Services\WhatsAppService;
use Illuminate\Support\Facades\Log;

class WhatsAppWebhookController extends Controller
{
    protected $whatsappService;

    public function __construct(WhatsAppService $whatsappService)
    {
        $this->whatsappService = $whatsappService;
    }

    public function verify(Request $request)
    {
        $mode = $request->query('hub_mode');
        $token = $request->query('hub_verify_token');
        $challenge = $request->query('hub_challenge');

        $challengeResponse = $this->whatsappService->verifyWebhook($mode, $token, $challenge);

        if ($challengeResponse) {
            return response($challengeResponse, 200);
        }

        return response('Forbidden', 403);
    }

    public function webhook(Request $request)
    {
        try {
            $data = $request->all();
            
            Log::info('WhatsApp webhook received', [
                'data' => $data
            ]);

            $this->whatsappService->processWebhook($data);

            return response('OK', 200);
        } catch (\Exception $e) {
            Log::error('Error processing WhatsApp webhook', [
                'error' => $e->getMessage(),
                'data' => $request->all()
            ]);

            return response('Error', 500);
        }
    }
} 