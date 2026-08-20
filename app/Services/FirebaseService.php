<?php

// Firebase معلّق بالكامل - كل الكود الأصلي معلّق أدناه

// namespace App\Services;
//
// use Illuminate\Support\Facades\Log;
// use App\Models\User;
// use Kreait\Firebase\Contract\Messaging;
// use Kreait\Firebase\Messaging\CloudMessage;
// use Kreait\Firebase\Messaging\Notification;
//
// class FirebaseService
// {
//     protected Messaging $messaging;
//
//     public function __construct(Messaging $messaging)
//     {
//         $this->messaging = $messaging;
//     }
//
//     public function sendToToken(string $fcmToken, string $title, string $body, array $data = []): bool
//     {
//         if (empty($fcmToken)) {
//             return false;
//         }
//         try {
//             $message = CloudMessage::withTarget('token', $fcmToken)
//                 ->withNotification(Notification::create($title, $body))
//                 ->withData($data);
//             $this->messaging->send($message);
//             return true;
//         } catch (\Kreait\Firebase\Exception\MessagingException $e) {
//             Log::error('FCM: فشل إرسال الإشعار (MessagingException)', [
//                 'message' => $e->getMessage(),
//                 'token'   => substr($fcmToken, 0, 20) . '...',
//                 'code'    => $e->getCode(),
//                 'error_info' => $e->errors(),
//             ]);
//             if (str_contains($e->getMessage(), 'registration-token-not-registered') || str_contains($e->getMessage(), 'invalid-argument')) {
//                 User::where('fcm_token', $fcmToken)->update(['fcm_token' => null]);
//                 Log::warning("FCM: Invalid token removed from database: " . substr($fcmToken, 0, 20) . '...');
//             }
//             return false;
//         } catch (\Exception $e) {
//             Log::error('FCM: فشل إرسال الإشعار', [
//                 'message' => $e->getMessage(),
//                 'token'   => substr($fcmToken, 0, 20) . '...',
//             ]);
//             return false;
//         }
//     }
//
//     public function sendToMultipleTokens(array $fcmTokens, string $title, string $body, array $data = []): bool
//     {
//         $tokens = array_values(array_filter($fcmTokens));
//         if (empty($tokens)) {
//             return false;
//         }
//         try {
//             $message = CloudMessage::new()
//                 ->withNotification(Notification::create($title, $body))
//                 ->withData($data);
//             $report = $this->messaging->sendMulticast($message, $tokens);
//             if ($report->hasFailures()) {
//                 foreach ($report->failures()->getItems() as $failure) {
//                     $error = $failure->error();
//                     $token = $failure->target()->value();
//                     Log::warning('FCM Multicast: فشل إرسال إشعار لرمز معين', [
//                         'token' => substr($token, 0, 20) . '...',
//                         'reason' => $error->getMessage(),
//                         'code' => $error->getCode(),
//                     ]);
//                     if (str_contains($error->getMessage(), 'registration-token-not-registered') || str_contains($error->getMessage(), 'invalid-argument')) {
//                         User::where('fcm_token', $token)->update(['fcm_token' => null]);
//                         Log::warning("FCM Multicast: Invalid token removed from database: " . substr($token, 0, 20) . '...');
//                     }
//                 }
//                 return false;
//             }
//             return true;
//         } catch (\Kreait\Firebase\Exception\MessagingException $e) {
//             Log::error('FCM Multicast: فشل إرسال الإشعار (MessagingException)', [
//                 'message' => $e->getMessage(),
//                 'tokens_count' => count($tokens),
//                 'code'    => $e->getCode(),
//                 'error_info' => $e->errors(),
//             ]);
//             return false;
//         } catch (\Exception $e) {
//             Log::error('FCM Multicast: فشل إرسال الإشعار', ['message' => $e->getMessage()]);
//             return false;
//         }
//     }
// }
