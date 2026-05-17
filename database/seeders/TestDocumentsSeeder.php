<?php

namespace Database\Seeders;

use App\Models\Address;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\ProofOfLocation;
use App\Models\Receipt;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class TestDocumentsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Creates test documents for user id=4
     */
    public function run(): void
    {
        $user = User::find(4);

        if (!$user) {
            $this->command->error('User with id=4 not found!');
            return;
        }

        // Get user's addresses or create one if none exists
        $addresses = $user->addresses;

        if ($addresses->isEmpty()) {
            $this->command->info('Creating a test address for user...');
            $address = Address::create([
                'user_id' => $user->id,
                'sw_address' => '@123 Rue 3.0001',
                'latitude' => 4.0511,
                'longitude' => 9.7679,
                'quarter' => 'Bonamoussadi',
                'sub_quarter' => 'Denver',
                'verification_status' => 'approved',
            ]);
            $addresses = collect([$address]);
        }

        $address = $addresses->first();

        $this->command->info("Creating test documents for user {$user->full_name} (id={$user->id})...");

        // 1. Create a Location Plan (active)
        $payment1 = Payment::create([
            'user_id' => $user->id,
            'address_id' => $address->id,
            'type' => 'location_plan',
            'amount' => 2000,
            'currency' => 'XAF',
            'status' => 'successful',
            'transaction_id' => 'TEST_' . Str::random(10),
            'paid_at' => now()->subDays(5),
        ]);

        $locationPlan = ProofOfLocation::create([
            'user_id' => $user->id,
            'address_id' => $address->id,
            'payment_id' => $payment1->id,
            'document_type' => ProofOfLocation::TYPE_LOCATION_PLAN,
            'document_number' => ProofOfLocation::generateDocumentNumber($user, $address, ProofOfLocation::TYPE_LOCATION_PLAN),
            'verification_code' => ProofOfLocation::generateVerificationCode(),
            'price' => 2000,
            'file_path' => '',
            'status' => 'active',
            'issued_at' => now()->subDays(5),
            'expires_at' => now()->addMonths(3)->subDays(5),
        ]);

        $this->command->info("  - Created Location Plan: {$locationPlan->document_number}");

        // 2. Create a Proof of Residence (active)
        $payment2 = Payment::create([
            'user_id' => $user->id,
            'address_id' => $address->id,
            'type' => 'proof_of_residence',
            'amount' => 3000,
            'currency' => 'XAF',
            'status' => 'successful',
            'transaction_id' => 'TEST_' . Str::random(10),
            'paid_at' => now()->subDays(10),
        ]);

        $proofOfResidence = ProofOfLocation::create([
            'user_id' => $user->id,
            'address_id' => $address->id,
            'payment_id' => $payment2->id,
            'document_type' => ProofOfLocation::TYPE_PROOF_OF_RESIDENCE,
            'document_number' => ProofOfLocation::generateDocumentNumber($user, $address, ProofOfLocation::TYPE_PROOF_OF_RESIDENCE),
            'verification_code' => ProofOfLocation::generateVerificationCode(),
            'price' => 3000,
            'file_path' => '',
            'status' => 'active',
            'issued_at' => now()->subDays(10),
            'expires_at' => now()->addMonths(3)->subDays(10),
        ]);

        $this->command->info("  - Created Proof of Residence: {$proofOfResidence->document_number}");

        // 3. Create an expired Location Plan
        $payment3 = Payment::create([
            'user_id' => $user->id,
            'address_id' => $address->id,
            'type' => 'location_plan',
            'amount' => 2000,
            'currency' => 'XAF',
            'status' => 'successful',
            'transaction_id' => 'TEST_' . Str::random(10),
            'paid_at' => now()->subMonths(6),
        ]);

        $expiredPlan = ProofOfLocation::create([
            'user_id' => $user->id,
            'address_id' => $address->id,
            'payment_id' => $payment3->id,
            'document_type' => ProofOfLocation::TYPE_LOCATION_PLAN,
            'document_number' => 'SW-LOC-' . $user->id . '-' . $address->id . '-' . strtoupper(Str::random(8)),
            'verification_code' => ProofOfLocation::generateVerificationCode(),
            'price' => 2000,
            'file_path' => '',
            'status' => 'expired',
            'issued_at' => now()->subMonths(6),
            'expires_at' => now()->subMonths(3),
        ]);

        $this->command->info("  - Created Expired Location Plan: {$expiredPlan->document_number}");

        // 4. Create an Invoice
        $invoice = Invoice::create([
            'user_id' => $user->id,
            'payment_id' => $payment1->id,
            'invoice_number' => 'INV-' . now()->format('Ymd') . '-0001',
            'verification_code' => 'SW-INV-' . strtoupper(Str::random(8)),
            'description' => 'Plan de Localisation - ' . $address->sw_address,
            'amount' => 2000,
            'tax_amount' => 0,
            'total_amount' => 2000,
            'currency' => 'XAF',
            'invoice_date' => now()->subDays(5),
            'paid_at' => now()->subDays(5),
            'company_name' => config('documents.company.name', 'Ket-Up Sarl'),
            'company_address' => config('documents.company.address', 'Douala, Cameroun'),
            'company_phone' => config('documents.company.phone'),
            'company_email' => config('documents.company.email'),
        ]);

        $this->command->info("  - Created Invoice: {$invoice->invoice_number}");

        // 5. Create a Receipt
        $receipt = Receipt::create([
            'user_id' => $user->id,
            'payment_id' => $payment1->id,
            'invoice_id' => $invoice->id,
            'receipt_number' => Receipt::generateReceiptNumber(),
            'verification_code' => Receipt::generateVerificationCode(),
            'description' => 'Paiement Plan de Localisation',
            'amount' => 2000,
            'currency' => 'XAF',
            'payment_method' => 'Mobile Money',
            'transaction_reference' => $payment1->transaction_id,
            'paid_at' => now()->subDays(5),
        ]);

        $this->command->info("  - Created Receipt: {$receipt->receipt_number}");

        // 6. Create another Receipt for proof of residence
        $invoice2 = Invoice::create([
            'user_id' => $user->id,
            'payment_id' => $payment2->id,
            'invoice_number' => 'INV-' . now()->format('Ymd') . '-0002',
            'verification_code' => 'SW-INV-' . strtoupper(Str::random(8)),
            'description' => 'Attestation de Résidence - ' . $address->sw_address,
            'amount' => 3000,
            'tax_amount' => 0,
            'total_amount' => 3000,
            'currency' => 'XAF',
            'invoice_date' => now()->subDays(10),
            'paid_at' => now()->subDays(10),
            'company_name' => config('documents.company.name', 'Ket-Up Sarl'),
            'company_address' => config('documents.company.address', 'Douala, Cameroun'),
            'company_phone' => config('documents.company.phone'),
            'company_email' => config('documents.company.email'),
        ]);

        $receipt2 = Receipt::create([
            'user_id' => $user->id,
            'payment_id' => $payment2->id,
            'invoice_id' => $invoice2->id,
            'receipt_number' => Receipt::generateReceiptNumber(),
            'verification_code' => Receipt::generateVerificationCode(),
            'description' => 'Paiement Attestation de Résidence',
            'amount' => 3000,
            'currency' => 'XAF',
            'payment_method' => 'Orange Money',
            'transaction_reference' => $payment2->transaction_id,
            'paid_at' => now()->subDays(10),
        ]);

        $this->command->info("  - Created Invoice: {$invoice2->invoice_number}");
        $this->command->info("  - Created Receipt: {$receipt2->receipt_number}");

        $this->command->info('');
        $this->command->info('Test documents created successfully!');
        $this->command->info('');
        $this->command->info('Summary:');
        $this->command->info('  - 2 Location Plans (1 active, 1 expired)');
        $this->command->info('  - 1 Proof of Residence (active)');
        $this->command->info('  - 2 Invoices');
        $this->command->info('  - 2 Receipts');
        $this->command->info('');
        $this->command->info('Verification codes for testing:');
        $this->command->info("  - Location Plan: {$locationPlan->verification_code}");
        $this->command->info("  - Proof of Residence: {$proofOfResidence->verification_code}");
    }
}
