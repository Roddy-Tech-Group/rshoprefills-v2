<?php

namespace Tests\Unit;

use App\Domain\Payment\Providers\FlutterwavePaymentProvider;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;

class MobileMoneyInstructionTest extends TestCase
{
    private function instruction(array $data, string $phone = '+237600000000'): string
    {
        // Skip the constructor (config/secret) — we only exercise the pure
        // instruction-selection logic.
        $provider = (new ReflectionClass(FlutterwavePaymentProvider::class))->newInstanceWithoutConstructor();
        $method = new ReflectionMethod($provider, 'mobileMoneyInstruction');
        $method->setAccessible(true);

        return $method->invoke($provider, $data, $phone);
    }

    public function test_it_prefers_the_authorization_note_dial_instruction(): void
    {
        $data = [
            'message' => 'Charge initiated',
            'meta' => ['authorization' => ['note' => 'Please dial *126*14# to approve this payment on your phone.']],
        ];

        $this->assertSame('Please dial *126*14# to approve this payment on your phone.', $this->instruction($data));
    }

    public function test_it_falls_back_to_processor_response(): void
    {
        $data = [
            'message' => 'pending',
            'data' => ['processor_response' => 'Approve the Orange Money prompt sent to your handset.'],
        ];

        $this->assertSame('Approve the Orange Money prompt sent to your handset.', $this->instruction($data));
    }

    public function test_terse_status_words_are_ignored_in_favour_of_the_default_prompt(): void
    {
        // No note/processor_response, only a terse status -> actionable fallback.
        $result = $this->instruction(['message' => 'initiated']);

        $this->assertStringContainsString('Dial your mobile money menu', $result);
        $this->assertStringContainsString('+237600000000', $result);
    }
}
