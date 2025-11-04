<?php

namespace App\Console\Commands;

use App\Models\Finance\Subscription;
use App\Models\User;
use App\Services\PaymentGateways\WompiService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ProcessTrialEndings extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'trial:process {--dry-run : Run without making changes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process trial subscriptions that are ending and charge users with Wompi';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->info('🔍 DRY RUN MODE - No se realizarán cambios');
        }

        $this->info('🔄 Procesando trials que terminan hoy...');

        // Buscar suscripciones en trial que terminan hoy o ya terminaron
        $endingTrials = Subscription::where('stripe_status', 'trialing')
            ->where('paid_with', 'wompi')
            ->where('trial_ends_at', '<=', Carbon::now())
            ->with(['user', 'plan'])
            ->get();

        if ($endingTrials->isEmpty()) {
            $this->info('✅ No hay trials que procesar');
            return 0;
        }

        $this->info("📊 Encontrados {$endingTrials->count()} trials para procesar");

        $processed = 0;
        $failed = 0;

        foreach ($endingTrials as $subscription) {
            $user = $subscription->user;
            $plan = $subscription->plan;

            $this->line("---");
            $this->info("👤 Usuario: {$user->name} ({$user->email})");
            $this->info("📦 Plan: {$plan->name} - \${$plan->price}");
            $this->info("📅 Trial terminó: {$subscription->trial_ends_at->format('Y-m-d H:i')}");

            if ($dryRun) {
                $this->warn('⏭️  SKIP (dry-run)');
                continue;
            }

            try {
                // Crear transacción de pago con Wompi
                $result = WompiService::subscribe($user, $plan, null);

                // Actualizar suscripción con el nuevo ID de transacción
                $subscription->update([
                    'stripe_status' => 'AwaitingPayment',
                    'stripe_id' => $result['transaction_id'],
                    'trial_ends_at' => null,
                ]);

                $this->info("✅ Transacción creada: {$result['transaction_id']}");
                $this->info("🔗 Link de pago: {$result['payment_link']}");

                // Enviar email al usuario con el link de pago
                // TODO: Implementar notificación por email

                $processed++;

                Log::info('Trial processed successfully', [
                    'user_id' => $user->id,
                    'subscription_id' => $subscription->id,
                    'transaction_id' => $result['transaction_id'],
                ]);

            } catch (\Exception $e) {
                $this->error("❌ Error: {$e->getMessage()}");
                $failed++;

                Log::error('Trial processing failed', [
                    'user_id' => $user->id,
                    'subscription_id' => $subscription->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->line("---");
        $this->info("📊 RESUMEN:");
        $this->info("✅ Procesados: {$processed}");
        if ($failed > 0) {
            $this->error("❌ Fallidos: {$failed}");
        }

        return 0;
    }
}
