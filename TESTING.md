# 🧪 Laravel pgvector RAG — Run & Manual Test Guide

This guide walks through running the app and manually exercising **every** RAG
feature, with the exact input and expected output for each step.

> Defaults assumed: drivers `fake`/`fake` (deterministic, no API key/network),
> `top_k=4`, chunking `1000/200` chars, queued ingestion **off**. The fake chat
> driver answers with `"Based on N source(s): <top chunk>"`, which makes
> retrieval easy to verify. Switch to `openai` for real prose (Section 8).

## 1. What you're testing (architecture)

```
┌─────────────────────────────────────────────────────────────────────────┐
│                          Browser  (Vue + Inertia)                          │
│   /login ──► /rag page:  [Ingest card]      [Ask card]                      │
└───────────┬───────────────────────────────┬───────────────────────────────┘
            │ fetch POST /api/rag/ingest     │ fetch POST /api/rag/ask
            ▼                                 ▼
┌─────────────────────────────────────────────────────────────────────────┐
│                      Laravel app (rubyat/laravel-rag)                       │
│                                                                            │
│  INGEST:  content ─► DocumentChunker ─► EmbeddingDriver ─► documents table  │
│                       (1000/200 chars)   (fake or OpenAI)   (vector 1536)   │
│                                                                            │
│  ASK:     question ─► EmbeddingDriver ─► VectorRetriever ─► RagPipeline     │
│                                          (cosine top-k=4)   + ChatDriver    │
│                                                ▲                 │           │
│                                          PostgreSQL             ▼           │
│                                          + pgvector       answer + citations │
│                                          (HNSW index)                       │
└─────────────────────────────────────────────────────────────────────────┘
```

## 2. Prerequisites

| Need | Check command | Expected |
|---|---|---|
| PHP 8.3+ | `php -v` | `PHP 8.3.x` |
| Composer | `composer --version` | `2.x` |
| Node 20+ | `node -v` | `v20+` |
| PostgreSQL + pgvector | `psql --version` | `psql 16/18` |

## 3. One-time setup

```bash
cd /Applications/MAMP/htdocs/laravel-pgvector-rag

# 3.1 PHP + JS deps
composer install
npm install

# 3.2 Environment
cp .env.example .env           # (skip if .env already exists)
php artisan key:generate

# 3.3 Make sure these lines are in .env:
#   DB_CONNECTION=pgsql
#   DB_URL="postgresql://rubyat@localhost:5432/laravel-pgvector-rag"
#   RAG_EMBEDDING_DRIVER=fake
#   RAG_CHAT_DRIVER=fake

# 3.4 Create the database (once) + enable pgvector
createdb -h localhost -U rubyat laravel-pgvector-rag
psql "postgresql://rubyat@localhost:5432/laravel-pgvector-rag" -c "CREATE EXTENSION IF NOT EXISTS vector;"

# 3.5 Migrate (users, documents w/ vector(1536) + HNSW index, etc.)
php artisan migrate

# 3.6 Build frontend assets
npm run build
```

**Expected (3.5):** a list ending with
`2026_06_01_000000_create_documents_table .......... DONE`

## 4. Create a test user

```bash
php artisan tinker --execute='\App\Models\User::firstOrCreate(["email"=>"demo@example.com"],["name"=>"Demo","password"=>bcrypt("password"),"email_verified_at"=>now()]);'
```

Login: **`demo@example.com` / `password`**. Or register a fresh one in Test B0.

## 5. Run the app

```bash
php artisan serve --host=127.0.0.1 --port=8000
```

Open **http://127.0.0.1:8000**.

> Or `composer dev` to run server + queue worker + log tailer + Vite together
> (use this if you set `RAG_QUEUE_INGESTION=true`).

## 6. Manual test scenarios (UI)

### UI layout you should see at `/rag`

```
┌─ Sidebar ────────┐  ┌─ RAG ────────────────────────────────────────┐
│ • Dashboard      │  │  Ingest a document                            │
│ • RAG  ◄ here    │  │   Source:  [______________]                   │
│                  │  │   Content: [                ]                  │
│ Demo ▼           │  │            [ Ingest ]   "Ingested … N chunks" │
│                  │  │                                                │
│                  │  │  Ask a question                                │
│                  │  │   [__________________________]  [ Ask ]        │
│                  │  │   ┌ answer ─────────────────────────────────┐ │
│                  │  │   │ Based on N source(s): …                  │ │
│                  │  │   └──────────────────────────────────────────┘│
│                  │  │   SOURCES                                      │
│                  │  │   • file.md #0      score 0.283               │
│                  │  │     ‹chunk text›                              │
└──────────────────┘  └────────────────────────────────────────────────┘
```

### Test B0 — Registration (optional)

| | |
|---|---|
| **Do** | Go to `/register`, fill Name/Email/Password, submit |
| **Expect** | Redirect to `/dashboard`, logged in |

### Test B1 — Login

| | |
|---|---|
| **Input** | `/login` → `demo@example.com` / `password` → **Log in** |
| **Expect** | Redirect to `/dashboard`; sidebar shows **Dashboard** + **RAG** |

### Test B2 — RAG page loads

| | |
|---|---|
| **Do** | Click **RAG** in sidebar (or visit `/rag`) |
| **Expect** | Two cards ("Ingest a document", "Ask a question"); **Ingest** and **Ask** buttons **disabled** while fields are empty |

### Test B3 — Ingest document #1

| | |
|---|---|
| **Source** | `pgvector-guide.md` |
| **Content** | `Postgres pgvector stores vector embeddings and supports cosine similarity search. The package uses an HNSW index for high recall.` |
| **Do** | Click **Ingest** |
| **Expect** | **`Ingested "pgvector-guide.md" into 1 chunk(s).`** Content box clears. |

> Why 1 chunk? Content < 1000 chars → single chunk. Paste ~3000+ chars to see
> multiple chunks.

### Test B4 — Ingest document #2 (different topic)

| | |
|---|---|
| **Source** | `baking.md` |
| **Content** | `Sourdough bread is made from flour, water, salt, and a live yeast starter, fermented for hours before baking.` |
| **Expect** | `Ingested "baking.md" into 1 chunk(s).` |

### Test B5 — Ask (relevant) → citation + ranking

| | |
|---|---|
| **Question** | `How does pgvector similarity search work?` |
| **Do** | Type → **Ask** (or press Enter) |
| **Expect answer** | `Based on 2 source(s): Postgres pgvector stores vector embeddings …` |
| **Expect SOURCES** | `pgvector-guide.md #0` **first**, higher score (~0.1–0.3); `baking.md #0` **second**, score `0.000` |

### Test B6 — Ask (other topic) → ranking flips ✅ key test

| | |
|---|---|
| **Question** | `What ingredients does sourdough bread need?` |
| **Expect** | Now **`baking.md #0` is first** (score > 0), `pgvector-guide.md` drops to `0.000`. Answer quotes the baking chunk. |

> This flip is the proof that retrieval is **semantic**, not fixed order.

### Test B7 — Validation (empty ask)

| | |
|---|---|
| **Do** | Clear the question box |
| **Expect** | **Ask** button is disabled (client guard). |

## 7. Manual test scenarios (HTTP API, via curl)

The endpoints are public JSON (no auth needed), so you can hit them directly.

### Test C1 — Ingest

```bash
curl -s -X POST http://127.0.0.1:8000/api/rag/ingest \
  -H "Content-Type: application/json" -H "Accept: application/json" \
  -d '{"source":"api-doc.md","content":"pgvector enables cosine similarity search over embeddings in Postgres."}'
```

**Expected (HTTP 201):**

```json
{"message":"Document ingested.","source":"api-doc.md","chunks":1}
```

### Test C2 — Ask

```bash
curl -s -X POST http://127.0.0.1:8000/api/rag/ask \
  -H "Content-Type: application/json" -H "Accept: application/json" \
  -d '{"question":"how does cosine search work","top_k":2}'
```

**Expected (HTTP 200):**

```json
{
  "answer": "Based on 1 source(s): pgvector enables cosine similarity search ...",
  "citations": [
    {"source":"api-doc.md","chunk_index":0,"content":"pgvector enables ...","score":0.41}
  ]
}
```

### Test C3 — Validation errors

```bash
# Ask with no question
curl -s -o /dev/null -w "%{http_code}\n" -X POST http://127.0.0.1:8000/api/rag/ask \
  -H "Accept: application/json" -H "Content-Type: application/json" -d '{}'
```

**Expected:** `422` (body: `{"message":"...","errors":{"question":["The question field is required."]}}`)

```bash
# Ingest missing fields
curl -s -X POST http://127.0.0.1:8000/api/rag/ingest \
  -H "Accept: application/json" -H "Content-Type: application/json" -d '{}'
```

**Expected:** `422` with errors for `source` and `content`.

### Test C4 — Empty knowledge base (fallback)

```bash
psql "postgresql://rubyat@localhost:5432/laravel-pgvector-rag" -c "TRUNCATE documents;"
curl -s -X POST http://127.0.0.1:8000/api/rag/ask \
  -H "Content-Type: application/json" -H "Accept: application/json" \
  -d '{"question":"anything?"}'
```

**Expected:**

```json
{"answer":"I don't have enough information in the provided context to answer that.","citations":[]}
```

## 8. Test with a REAL LLM (optional)

```bash
# .env
OPENAI_API_KEY=sk-...your-key...
RAG_EMBEDDING_DRIVER=openai
RAG_CHAT_DRIVER=openai
```

```bash
php artisan config:clear
php artisan serve   # re-run
```

Re-do Test B3 → B5. **Difference:** the answer is now **real prose written by
the LLM** instead of `"Based on N source(s):"`. Citations/retrieval behave
identically.

> ⚠️ Re-ingest your documents after switching embedding drivers — fake and
> OpenAI vectors aren't comparable.

## 9. Automated test suite (proves all logic at once)

```bash
php artisan test                 # full suite — expect "Tests: 63 passed"
php artisan test --filter=Rag    # only RAG tests
./vendor/bin/pest tests/Unit     # chunker + fake driver units
```

**Expected:** `Tests: 63 passed (224 assertions)`.

## 10. Verify the data in PostgreSQL (optional, deeper)

```bash
# Rows: one per chunk, each with a 1536-dim vector
psql "postgresql://rubyat@localhost:5432/laravel-pgvector-rag" \
  -c "SELECT source, chunk_index, vector_dims(embedding) AS dims, length(content) FROM documents ORDER BY id;"

# The HNSW cosine index exists
psql "postgresql://rubyat@localhost:5432/laravel-pgvector-rag" \
  -c "SELECT indexname FROM pg_indexes WHERE tablename='documents';"
```

**Expected:** `documents_embedding_hnsw_idx` present; every row `dims = 1536`.

## 11. End-to-end flow diagrams

**Ingest path**

```
type Source+Content ─► [Ingest] ─► POST /api/rag/ingest
  └► DocumentChunker (split 1000 chars / 200 overlap)
     └► EmbeddingDriver.embed() → [1536 floats] per chunk
        └► INSERT into documents (source, chunk_index, content, embedding::vector)
           └► 201 {chunks: N}  ──► UI: "Ingested … N chunk(s)."
```

**Ask path**

```
type Question ─► [Ask] ─► POST /api/rag/ask
  └► EmbeddingDriver.embed(question) → query vector
     └► VectorRetriever: ORDER BY embedding <=> query LIMIT top_k(4)
        └► top chunks + similarity = 1 - cosine_distance
           └► RagPipeline → ChatDriver.answer(question, chunks)
              └► 200 {answer, citations[]}  ──► UI: answer box + SOURCES list
```

## 12. Troubleshooting

| Symptom | Cause | Fix |
|---|---|---|
| `vendor/autoload.php` not found | deps not installed | `composer install` |
| `vite: command not found` / "Vite manifest not found" | assets not built | `npm install && npm run build` |
| `relation "documents" does not exist` | not migrated | `php artisan migrate` |
| `could not find driver` / connection refused | Postgres not running / wrong `DB_URL` | start Postgres; check `.env` `DB_URL` |
| `extension "vector" is not available` | pgvector not installed | install pgvector or use the `pgvector/pgvector` Docker image |
| Ask always says *"don't have enough information"* | nothing ingested, **or** ingestion queued with no worker | ingest first; keep `RAG_QUEUE_INGESTION=false` (or run `php artisan queue:work`) |
| 401 / redirect to `/login` on `/rag` | session expired | log in again (`demo@example.com` / `password`) |
| `RuntimeException: OPENAI_API_KEY is not set` | switched to `openai` driver without a key | set the key, or revert to `fake` |

## 13. Quick smoke test (copy-paste, ~30s)

```bash
php artisan migrate --force
php artisan serve --host=127.0.0.1 --port=8000 &     # start
sleep 3
curl -s -X POST http://127.0.0.1:8000/api/rag/ingest -H "Content-Type: application/json" -H "Accept: application/json" -d '{"source":"s.md","content":"pgvector cosine search with HNSW."}'
curl -s -X POST http://127.0.0.1:8000/api/rag/ask -H "Content-Type: application/json" -H "Accept: application/json" -d '{"question":"what index?","top_k":1}'
```

Expect a `201 {…chunks:1}` then a `200 {answer…, citations:[…]}`.
