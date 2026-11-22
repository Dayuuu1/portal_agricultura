<?php

namespace App\Support\MesaPartes;

use App\Mail\MesaPartes\SubmissionNotice;
use App\Models\CitizenNotification;
use App\Models\Submission;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Leaves messages in the citizen's inbox («casilla») and, when the citizen authorized electronic notifications
 * (TUO de la Ley N° 27444, art. 20.4), also e-mails them. A mail failure never loses the message: it stays in
 * the inbox with its deposit date.
 */
final class CitizenNotifier
{
    /**
     * @param  bool  $formal  Formal notices (observations, answers…) are e-mailed only with the citizen's authorization;
     *                        informative messages (such as the receipt) always are.
     */
    public static function deposit(Submission $submission, string $title, string $body, bool $formal = true): CitizenNotification
    {
        $citizen = $submission->citizen;

        $notification = CitizenNotification::query()->create([
            'citizen_id' => $citizen->id,
            'submission_id' => $submission->id,
            'title' => $title,
            'body' => $body,
            'deposited_at' => now(),
        ]);

        if (! $formal || $citizen->acceptsElectronicNotifications()) {
            try {
                Mail::to($citizen->email, $citizen->fullName())->send(new SubmissionNotice($notification));
                $notification->forceFill(['emailed_at' => now()])->save();
            } catch (Throwable $exception) {
                report($exception);
            }
        }

        return $notification;
    }
}
