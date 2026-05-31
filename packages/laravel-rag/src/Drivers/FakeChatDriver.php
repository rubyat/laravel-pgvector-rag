<?php

namespace RagStarter\Drivers;

use Illuminate\Support\Str;
use RagStarter\Contracts\ChatDriver;

/**
 * A deterministic, network-free chat driver for local use and tests. It echoes
 * how many context chunks it received and quotes the most relevant one, so
 * grounding behaviour is observable without calling a real LLM.
 */
class FakeChatDriver implements ChatDriver
{
    public function answer(string $question, array $context): string
    {
        if ($context === []) {
            return "I don't have enough information in the provided context to answer that.";
        }

        return sprintf(
            'Based on %d source(s): %s',
            count($context),
            Str::limit(trim($context[0]), 200),
        );
    }
}
