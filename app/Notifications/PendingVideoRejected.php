<?php

namespace App\Notifications;

use App\Models\Video;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PendingVideoRejected extends Notification
{

    public function __construct(
        protected Video $video
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('تنبيه بخصوص الفيديو الخاص بك')
            ->greeting('أهلاً ' . ($notifiable->name ?? ''))
            ->line('شكرًا على مساهمتك معنا. بعد مراجعة الفيديو، تبيّن أنه لا يتماشى مع توجه ورسالة التطبيق، لذا تم رفضه في هذه المرحلة.')
            ->line('عنوان الفيديو: "' . $this->video->title . '"')
            ->line('نحن نقدّر إبداعك ونتطلع إلى استقبال محتوى جديد ينسجم مع معايير المنصة. للحصول على أفضل فرصة للقبول، يُرجى التأكد من أن المحتوى يدعم أهداف التطبيق التعليمية ويحافظ على القيم التي نعمل عليها.')
            ->line('يسعدنا دائماً تعاونك، وأي استفسار لا تتردد بالتواصل معنا.');
    }
}

