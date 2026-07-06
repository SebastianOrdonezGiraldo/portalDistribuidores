<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schedule;

Schedule::command('inventree:sync', ['--type' => 'stock'])
    ->everyMinute()
    ->withoutOverlapping(5)
    ->environments(['production'])
    ->appendOutputTo(storage_path('logs/inventree-stock-sync.log'));

Schedule::command('inventree:sync', ['--type' => 'prices'])
    ->everyThirtyMinutes()
    ->withoutOverlapping(10)
    ->environments(['production'])
    ->appendOutputTo(storage_path('logs/inventree-price-sync.log'));

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('mail:diagnose {--to=}', function () {
    if (! app()->environment(['local', 'staging'])) {
        $this->error('mail:diagnose solo esta disponible en local o staging.');

        return 1;
    }

    $target = trim((string) ($this->option('to') ?: config('mail.order_notification_to')));

    $this->line('Mail config:');
    $this->line('  mailer='.config('mail.default'));
    $this->line('  host='.config('mail.mailers.smtp.host'));
    $this->line('  port='.config('mail.mailers.smtp.port'));
    $this->line('  scheme='.config('mail.mailers.smtp.scheme'));
    $this->line('  local_domain='.config('mail.mailers.smtp.local_domain'));
    $this->line('  from='.config('mail.from.address'));
    $this->line('  order_notification_to='.config('mail.order_notification_to'));
    $this->line('  app_url='.config('app.url'));

    if ($target === '') {
        $this->error('No hay destinatario. Define --to=... o ORDER_NOTIFICATION_EMAIL_TO.');

        return 1;
    }

    $this->line('Intentando envío de prueba a: '.$target);

    try {
        Mail::raw('Diagnóstico SMTP desde Railway', function ($message) use ($target): void {
            $message->to($target)->subject('Diagnóstico SMTP');
        });
    } catch (Throwable $exception) {
        $this->error('SMTP ERROR: '.get_class($exception).' :: '.$exception->getMessage());

        if ((bool) config('app.debug')) {
            $this->line('Stack:');
            $this->line($exception->getTraceAsString());
        }

        return 1;
    }

    $this->info('MAIL_OK: envío completado.');

    return 0;
})->purpose('Diagnostica el envío SMTP y muestra el error exacto');
