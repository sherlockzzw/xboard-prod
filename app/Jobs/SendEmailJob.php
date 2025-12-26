<?php

namespace App\Jobs;

use App\Services\MailService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    protected $params;

    public $tries = 3;
    public $timeout = 60; // 增加超时时间到60秒，因为SMTP连接可能需要更长时间
    public $maxExceptions = 3;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($params, $queue = 'send_email')
    {
        $this->onQueue($queue);
        $this->params = $params;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $startTime = microtime(true);
        $email = $this->params['email'] ?? 'unknown';
        $subject = $this->params['subject'] ?? 'unknown';
        $attempt = $this->attempts();

        Log::channel('daily')->info('SendEmailJob started', [
            'email' => $email,
            'subject' => $subject,
            'attempt' => $attempt,
            'max_tries' => $this->tries,
        ]);

        try {
            $mailLog = MailService::sendEmail($this->params);
            $duration = round((microtime(true) - $startTime) * 1000, 2);

            if (!empty($mailLog['error'])) {
                Log::channel('daily')->warning('SendEmailJob failed', [
                    'email' => $email,
                    'subject' => $subject,
                    'attempt' => $attempt,
                    'error' => $mailLog['error'],
                    'duration_ms' => $duration,
                ]);

                // 如果还有重试次数，抛出异常触发重试
                if ($attempt < $this->tries) {
                    throw new \Exception('邮件发送失败: ' . $mailLog['error']);
                } else {
                    // 已达到最大重试次数，记录失败
                    Log::channel('daily')->error('SendEmailJob max attempts reached', [
                        'email' => $email,
                        'subject' => $subject,
                        'attempt' => $attempt,
                        'error' => $mailLog['error'],
                    ]);
                }
            } else {
                Log::channel('daily')->info('SendEmailJob completed successfully', [
                    'email' => $email,
                    'subject' => $subject,
                    'attempt' => $attempt,
                    'duration_ms' => $duration,
                ]);
            }
        } catch (\Exception $e) {
            $duration = round((microtime(true) - $startTime) * 1000, 2);
            Log::channel('daily')->error('SendEmailJob exception', [
                'email' => $email,
                'subject' => $subject,
                'attempt' => $attempt,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'duration_ms' => $duration,
            ]);
            throw $e; // 重新抛出异常以触发重试
        }
    }

    /**
     * 任务失败时的处理
     */
    public function failed(\Throwable $exception): void
    {
        Log::channel('daily')->error('SendEmailJob permanently failed', [
            'email' => $this->params['email'] ?? 'unknown',
            'subject' => $this->params['subject'] ?? 'unknown',
            'error' => $exception->getMessage(),
            'attempts' => $this->attempts(),
        ]);
    }
}
