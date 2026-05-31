<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { ref } from 'vue';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useRag } from '@/composables/useRag';
import type { AskResult } from '@/composables/useRag';
import { rag } from '@/routes';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'RAG',
                href: rag(),
            },
        ],
    },
});

const { ingest, ask, ingesting, asking, error } = useRag();

const source = ref('');
const content = ref('');
const ingestStatus = ref<string | null>(null);

const question = ref('');
const result = ref<AskResult | null>(null);

async function onIngest() {
    ingestStatus.value = null;

    try {
        const res = await ingest(source.value, content.value);
        ingestStatus.value = `Ingested "${res.source}" into ${res.chunks} chunk(s).`;
        content.value = '';
    } catch {
        // error surfaced via the shared error ref
    }
}

async function onAsk() {
    result.value = null;

    try {
        result.value = await ask(question.value);
    } catch {
        // error surfaced via the shared error ref
    }
}
</script>

<template>
    <Head title="RAG" />

    <div class="flex h-full flex-1 flex-col gap-6 p-4">
        <p
            v-if="error"
            class="rounded-md bg-destructive/10 px-4 py-2 text-sm text-destructive"
        >
            {{ error }}
        </p>

        <Card>
            <CardHeader>
                <CardTitle>Ingest a document</CardTitle>
                <CardDescription>
                    Text is chunked, embedded and stored in pgvector.
                </CardDescription>
            </CardHeader>
            <CardContent class="space-y-4">
                <div class="space-y-2">
                    <Label for="source">Source</Label>
                    <Input
                        id="source"
                        v-model="source"
                        placeholder="e.g. handbook.md"
                    />
                </div>
                <div class="space-y-2">
                    <Label for="content">Content</Label>
                    <textarea
                        id="content"
                        v-model="content"
                        rows="6"
                        class="flex w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                        placeholder="Paste the document text to make searchable…"
                    />
                </div>
                <Button
                    :disabled="ingesting || !source || !content"
                    @click="onIngest"
                >
                    {{ ingesting ? 'Ingesting…' : 'Ingest' }}
                </Button>
                <p v-if="ingestStatus" class="text-sm text-muted-foreground">
                    {{ ingestStatus }}
                </p>
            </CardContent>
        </Card>

        <Card>
            <CardHeader>
                <CardTitle>Ask a question</CardTitle>
                <CardDescription>
                    Answers are grounded in your ingested documents.
                </CardDescription>
            </CardHeader>
            <CardContent class="space-y-4">
                <div class="flex gap-2">
                    <Input
                        v-model="question"
                        placeholder="Ask something about your documents…"
                        @keyup.enter="onAsk"
                    />
                    <Button :disabled="asking || !question" @click="onAsk">
                        {{ asking ? 'Asking…' : 'Ask' }}
                    </Button>
                </div>

                <div v-if="result" class="space-y-4">
                    <div class="rounded-md border border-sidebar-border/70 p-4">
                        <p class="text-sm whitespace-pre-wrap">
                            {{ result.answer }}
                        </p>
                    </div>

                    <div v-if="result.citations.length" class="space-y-2">
                        <p
                            class="text-xs font-medium text-muted-foreground uppercase"
                        >
                            Sources
                        </p>
                        <ul class="space-y-2">
                            <li
                                v-for="(citation, index) in result.citations"
                                :key="index"
                                class="rounded-md border border-sidebar-border/70 p-3 text-sm"
                            >
                                <div
                                    class="mb-1 flex items-center justify-between"
                                >
                                    <span class="font-medium">
                                        {{ citation.source }} #{{
                                            citation.chunk_index
                                        }}
                                    </span>
                                    <span class="text-xs text-muted-foreground">
                                        score {{ citation.score.toFixed(3) }}
                                    </span>
                                </div>
                                <p class="text-muted-foreground">
                                    {{ citation.content }}
                                </p>
                            </li>
                        </ul>
                    </div>
                </div>
            </CardContent>
        </Card>
    </div>
</template>
