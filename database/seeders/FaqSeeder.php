<?php

namespace Database\Seeders;

use App\Models\Faq;
use Illuminate\Database\Seeder;

class FaqSeeder extends Seeder
{
    public function run(): void
    {
        $faqs = [
            ['Orders & Delivery', 'How fast is delivery?', 'Most digital orders such as gift cards, eSIMs and top-ups are delivered instantly to your email and dashboard once payment is confirmed. A small number of items may take a few minutes while the item is being prepared.'],
            ['Orders & Delivery', 'Where do I find my codes?', 'Open your dashboard, go to Orders and select the order. Delivered codes and PINs appear there, and a copy is sent to your delivery email.'],
            ['Orders & Delivery', 'My order says processing. What now?', 'Processing means your payment cleared and we are preparing your item. It usually completes within minutes. If it stays in processing for a long time, contact support with your order number.'],

            ['Payments & Wallet', 'What payment methods can I use?', 'You can pay by card, bank transfer, mobile money, crypto, or your wallet balance. The methods shown depend on the currency you select at checkout.'],
            ['Payments & Wallet', 'How do I fund my wallet?', 'Go to Wallet in your dashboard, choose Add funds, pick an amount and a payment method, then confirm. Your balance updates as soon as the payment is confirmed.'],
            ['Payments & Wallet', 'Can I hold more than one currency?', 'Yes. You can open a wallet in each supported currency from the Wallet page and fund whichever you need.'],

            ['Gift Cards & eSIMs', 'Are gift cards region locked?', 'Some brands are sold per country. The store shows the countries each brand is available in based on the region you select.'],
            ['Gift Cards & eSIMs', 'How do I activate an eSIM?', 'After purchase, your eSIM QR code and activation details appear in your order. Scan the QR code in your phone settings to install and activate the plan.'],

            ['Account & Verification', 'Why do I need to verify my identity?', 'Verification helps keep your account and funds secure and can unlock higher limits. Upload your documents from the Verify Identity page in your dashboard.'],
            ['Account & Verification', 'How long does verification take?', 'Most submissions are reviewed within a short window. You will be notified once your verification is approved or if anything further is needed.'],

            ['Transaction PIN & Security', 'What is a transaction PIN?', 'It is a 4-digit PIN that authorizes payments from your wallet balance. You can set or change it under Security in your dashboard.'],
            ['Transaction PIN & Security', 'I forgot my transaction PIN.', 'You can change it from the Security page. For your safety the PIN locks after several wrong attempts and unlocks automatically after a short cooldown.'],
            ['Transaction PIN & Security', 'How do I keep my account safe?', 'Use a strong password, set a transaction PIN, and never share codes or PINs with anyone. We will never ask you for your password or PIN.'],

            ['Refunds & Disputes', 'Can I get a refund?', 'Digital items that have been delivered and revealed generally cannot be refunded. If an item failed to deliver or is faulty, contact support and we will make it right.'],
            ['Refunds & Disputes', 'An order failed but I was charged.', 'Failed payments are reversed automatically and any reserved wallet funds are released. If you still see a charge after some time, contact support with your order number.'],
        ];

        $sortByTopic = [];
        foreach ($faqs as [$topic, $question, $answer]) {
            $sortByTopic[$topic] = ($sortByTopic[$topic] ?? -1) + 1;

            Faq::updateOrCreate(
                ['topic' => $topic, 'question' => $question],
                [
                    'answer' => $answer,
                    'is_published' => true,
                    'sort_order' => $sortByTopic[$topic],
                ],
            );
        }
    }
}
