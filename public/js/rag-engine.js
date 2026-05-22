/**
 * AetherMind Client-Side RAG & Cosine Similarity TF-IDF Search Engine
 */

class RagEngine {
    constructor() {
        this.chunks = [];      // Holds all loaded database chunks
        this.model = null;     // USE model
        this.isReady = false;
        
        // Initialize USE model
        this.initModel();
    }

    async initModel() {
        try {
            console.log("Loading Universal Sentence Encoder...");
            // use.load() comes from the CDN script
            if (typeof use !== 'undefined') {
                this.model = await use.load();
                this.isReady = true;
                console.log("Universal Sentence Encoder loaded successfully!");
                // Re-build embeddings if chunks were loaded before model was ready
                if (this.chunks.length > 0 && !this.chunks[0].embedding) {
                    this.buildEmbeddings();
                }
            } else {
                console.error("USE script not found. Semantic RAG cannot be used.");
            }
        } catch (e) {
            console.error("Failed to load USE model.", e);
        }
    }

    /**
     * Set chunks and build embeddings
     */
    async setChunks(chunksList) {
        this.chunks = chunksList.map(chunk => {
            return {
                id: chunk.id,
                document_id: chunk.document_id,
                document_title: chunk.document_title,
                chunk_index: chunk.chunk_index,
                content: chunk.content,
                embedding: null // Will hold the 512D array
            };
        });

        if (this.isReady) {
            await this.buildEmbeddings();
        }
    }

    /**
     * Compute Vector Embeddings using USE
     */
    async buildEmbeddings() {
        if (!this.model || this.chunks.length === 0) return;
        
        console.log("Building Vector Embeddings for chunks...");
        const sentences = this.chunks.map(c => c.content);
        
        // embed() returns a 2D tensor [num_sentences, 512]
        const embeddingsTensor = await this.model.embed(sentences);
        const embeddingsArray = await embeddingsTensor.array();
        
        for (let i = 0; i < this.chunks.length; i++) {
            this.chunks[i].embedding = embeddingsArray[i];
        }
        
        embeddingsTensor.dispose();
        console.log("Embeddings built successfully!");
    }

    /**
     * Calculate Cosine Similarity between query embedding and chunk embeddings
     * Returns sorted chunks list with similarity coefficients
     */
    async search(queryText) {
        if (!this.isReady || !queryText || this.chunks.length === 0) {
            return [];
        }

        // Make sure embeddings exist before searching
        if (!this.chunks[0].embedding) {
            await this.buildEmbeddings();
        }

        // 1. Embed the query
        const queryTensor = await this.model.embed([queryText]);
        const queryArray = await queryTensor.array();
        const qVec = queryArray[0];
        queryTensor.dispose();

        // 2. Calculate Cosine Similarity for each chunk
        const results = [];

        for (let i = 0; i < this.chunks.length; i++) {
            const chunk = this.chunks[i];
            const cVec = chunk.embedding;
            if (!cVec) continue;

            let dotProduct = 0;
            let qMagSq = 0;
            let cMagSq = 0;

            for (let d = 0; d < 512; d++) {
                dotProduct += qVec[d] * cVec[d];
                qMagSq += qVec[d] * qVec[d];
                cMagSq += cVec[d] * cVec[d];
            }

            let score = 0;
            if (qMagSq > 0 && cMagSq > 0) {
                score = dotProduct / (Math.sqrt(qMagSq) * Math.sqrt(cMagSq));
            }

            results.push({
                chunk_id: chunk.id,
                document_id: chunk.document_id,
                document_title: chunk.document_title,
                chunk_index: chunk.chunk_index,
                content: chunk.content,
                score: parseFloat(score.toFixed(4))
            });
        }

        // 3. Sort descending by score, filtering out zero-matches
        // Embeddings always have some cosine similarity, so we set a semantic threshold (e.g., > 0.15)
        return results
            .filter(r => r.score > 0.15)
            .sort((a, b) => b.score - a.score);
    }
}
