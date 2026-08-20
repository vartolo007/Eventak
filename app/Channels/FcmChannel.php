<?php

// Firebase معلّق بالكامل - كل الكود الأصلي معلّق أدناه

// namespace App\Channels;
//
// use App\Services\FirebaseService;
// use Illuminate\Notifications\Notification;
// use Illuminate\Support\Facades\Log;
// use Kreait\Firebase\Exception\MessagingException;
//
// class FcmChannel
// {
//     protected FirebaseService $firebaseService;
//
//     public function __construct(FirebaseService $firebaseService)
//     {
//         $this->firebaseService = $firebaseService;
//     }
//
//     public function send($notifiable, Notification $notification)
//     {
//         if (!method_exists($notification, 'toFcm')) {
//             Log::warning('Notification ' . get_class($notification) . ' is missing toFcm method for FcmChannel.');
//             return;
//         }
//         $fcmData = $notification->toFcm($notifiable);
//         if (empty($fcmData['title']) || empty($fcmData['message'])) {
//             Log::warning('FCM Notification data is incomplete for ' . get_class($notification), ['data' => $fcmData]);
//             return;
//         }
//         if ($notifiable instanceof \Illuminate\Support\Collection) {
//             $fcmTokens = $notifiable->pluck('fcm_token')->filter()->toArray();
//             if (empty($fcmTokens)) { return; }
//             try {
//                 $this->firebaseService->sendToMultipleTokens(
//                     $fcmTokens, $fcmData['title'], $fcmData['message'], $fcmData['data'] ?? []
//                 );
//             } catch (MessagingException $e) {
//                 Log::error('FCM Channel: Firebase Messaging Exception (Multicast)', [
//                     'message' => $e->getMessage(), 'tokens_count' => count($fcmTokens),
//                     'notification' => get_class($notification), 'code' => $e->getCode(),
//                 ]);
//             } catch (\Exception $e) {
//                 Log::error('FCM Channel: General Exception (Multicast)', [
//                     'message' => $e->getMessage(), 'tokens_count' => count($fcmTokens),
//                     'notification' => get_class($notification),
//                 ]);
//             }
//         } else {
//             if (empty($notifiable->fcm_token)) { return; }
//             try {
//                 $this->firebaseService->sendToToken(
//                     $notifiable->fcm_token, $fcmData['title'], $fcmData['message'], $fcmData['data'] ?? []
//                 );
//             } catch (MessagingException $e) {
//                 Log::error('FCM Channel: Firebase Messaging Exception (Single)', [
//                     'message' => $e->getMessage(),
//                     'token'   => substr($notifiable->fcm_token, 0, 20) . '...',
//                     'notification' => get_class($notification), 'code' => $e->getCode(),
//                 ]);
//             } catch (\Exception $e) {
//                 Log::error('FCM Channel: General Exception (Single)', [
//                     'message' => $e->getMessage(),
//                     'token'   => substr($notifiable->fcm_token, 0, 20) . '...',
//                     'notification' => get_class($notification),
//                 ]);
//             }
//         }
//     }
// }
