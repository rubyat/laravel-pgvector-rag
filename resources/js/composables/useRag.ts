import { ref } from 'vue';

export interface Citation {
    source: string;
    chunk_index: number;
    content: string;
    score: number;
}

export interface AskResult {
    answer: string;
    citations: Citation[];
}

export interface IngestResult {
    message: string;
    source: string;
    chunks: number;
}

async function postJson<T>(
    url: string,
    body: Record<string, unknown>,
): Promise<T> {
    const response = await fetch(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            Accept: 'application/json',
        },
        body: JSON.stringify(body),
    });

    const data = await response.json();

    if (!response.ok) {
        const message =
            data?.message ??
            (data?.errors
                ? Object.values(data.errors).flat().join(' ')
                : 'Request failed.');

        throw new Error(message);
    }

    return data as T;
}

export function useRag() {
    const ingesting = ref(false);
    const asking = ref(false);
    const error = ref<string | null>(null);

    async function ingest(
        source: string,
        content: string,
    ): Promise<IngestResult> {
        ingesting.value = true;
        error.value = null;

        try {
            return await postJson<IngestResult>('/api/rag/ingest', {
                source,
                content,
            });
        } catch (e) {
            error.value = e instanceof Error ? e.message : String(e);

            throw e;
        } finally {
            ingesting.value = false;
        }
    }

    async function ask(question: string): Promise<AskResult> {
        asking.value = true;
        error.value = null;

        try {
            return await postJson<AskResult>('/api/rag/ask', { question });
        } catch (e) {
            error.value = e instanceof Error ? e.message : String(e);

            throw e;
        } finally {
            asking.value = false;
        }
    }

    return { ingest, ask, ingesting, asking, error };
}
