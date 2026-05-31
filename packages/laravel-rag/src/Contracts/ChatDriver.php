<?php

namespace RagStarter\Contracts;

interface ChatDriver
{
    /**
     * Answer a question grounded in the supplied context chunks.
     *
     * @param  array<int, string>  $context  Retrieved chunks, most relevant first.
     */
    public function answer(string $question, array $context): string;
}
