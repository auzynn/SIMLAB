<?php

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withSchedule(function (Schedule $schedule): void {
        // Pengingat pengembalian perangkat tiap pagi 07.00 (perlu `schedule:run` via cron/Task Scheduler/schedule:work).
        $schedule->command('pengingat:pengembalian')->dailyAt('07:00');
        // Pengingat tenggat tugas terlewati — dicek tiap jam agar mendekati waktu deadline.
        $schedule->command('pengingat:deadline')->hourly();
    })
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );
    })->create();
