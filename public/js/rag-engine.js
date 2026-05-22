/**
 * AetherMind Client-Side RAG & Cosine Similarity TF-IDF Search Engine
 */

class RagEngine {
    constructor() {
        this.chunks = [];      // Holds all loaded database chunks
        this.vocabulary = [];  // Unique terms across all chunks
        this.idf = {};         // Inverse document frequencies of terms
    }

    /**
     * Set chunks and initialize TF-IDF vocabulary metrics
     */
    setChunks(chunksList) {
        this.chunks = chunksList.map(chunk => {
            return {
                id: chunk.id,
                document_id: chunk.document_id,
                document_title: chunk.document_title,
                chunk_index: chunk.chunk_index,
                content: chunk.content,
                tokens: this.tokenize(chunk.content)
            };
        });

        this.buildTfIdf();
    }

    /**
     * Tokenize text: lowercase, remove punctuation, split by space, remove short stop-words
     */
    tokenize(text) {
        if (!text) return [];
        const clean = text.toLowerCase().replace(/[^\w\s]/g, ' ');
        const words = clean.split(/\s+/);
        // Filter out empty strings and very common short words
        const stopWords = new Set(['the', 'a', 'an', 'and', 'or', 'but', 'is', 'are', 'was', 'to', 'of', 'in', 'on', 'at', 'by', 'for', 'with', 'about', 'as', 'that', 'this', 'these']);
        return words.filter(w => w.length > 2 && !stopWords.has(w));
    }

    /**
     * Compute TF-IDF vectors for all chunks
     */
    buildTfIdf() {
        const totalChunks = this.chunks.length;
        if (totalChunks === 0) return;

        const docFrequency = {};
        const allTerms = new Set();

        // 1. Calculate Term Frequencies (TF) for each chunk and tally document frequencies
        this.chunks.forEach(chunk => {
            const termCounts = {};
            const uniqueTerms = new Set(chunk.tokens);

            chunk.tokens.forEach(term => {
                termCounts[term] = (termCounts[term] || 0) + 1;
                allTerms.add(term);
            });

            // Normalized Term Frequency
            chunk.tf = {};
            const numTokens = chunk.tokens.length;
            for (const [term, count] of Object.entries(termCounts)) {
                chunk.tf[term] = count / numTokens;
            }

            // Increment Document Frequency count for terms seen in this chunk
            uniqueTerms.forEach(term => {
                docFrequency[term] = (docFrequency[term] || 0) + 1;
            });
        });

        this.vocabulary = Array.from(allTerms);

        // 2. Calculate Inverse Document Frequencies (IDF)
        this.idf = {};
        this.vocabulary.forEach(term => {
            const df = docFrequency[term] || 0;
            // Standard logarithmic IDF with smoothing
            this.idf[term] = Math.log(1 + (totalChunks / (1 + df)));
        });

        // 3. Compute final TF-IDF weights for each chunk
        this.chunks.forEach(chunk => {
            chunk.tfidf = {};
            for (const [term, tfVal] of Object.entries(chunk.tf)) {
                chunk.tfidf[term] = tfVal * this.idf[term];
            }
        });
    }

    /**
     * Calculate similarity between query and all chunks
     * Returns sorted chunks list with similarity coefficients [0.0 - 1.0]
     */
    search(queryText) {
        const queryTokens = this.tokenize(queryText);
        if (queryTokens.length === 0 || this.chunks.length === 0) {
            return [];
        }

        // 1. Build Query Term Frequency vector
        const queryTf = {};
        const termCounts = {};
        queryTokens.forEach(term => {
            termCounts[term] = (termCounts[term] || 0) + 1;
        });

        const totalQueryTokens = queryTokens.length;
        for (const [term, count] of Object.entries(termCounts)) {
            queryTf[term] = count / totalQueryTokens;
        }

        // 2. Build Query TF-IDF vector
        const queryTfIdf = {};
        for (const [term, tfVal] of Object.entries(queryTf)) {
            // If term isn't in our corpus, its IDF is 0 (or low), skip
            const idfVal = this.idf[term] || 0;
            queryTfIdf[term] = tfVal * idfVal;
        }

        // 3. Calculate Cosine Similarity for each chunk
        const results = [];

        this.chunks.forEach(chunk => {
            let dotProduct = 0;
            let queryMagnitudeSq = 0;
            let chunkMagnitudeSq = 0;

            // Dot Product and Magnitudes
            // We iterate over terms present in the query
            for (const [term, qWeight] of Object.entries(queryTfIdf)) {
                const cWeight = chunk.tfidf[term] || 0;
                dotProduct += qWeight * cWeight;
                queryMagnitudeSq += qWeight * qWeight;
            }

            // Chunk Magnitude (requires iterating over all terms in chunk)
            for (const cWeight of Object.values(chunk.tfidf)) {
                chunkMagnitudeSq += cWeight * cWeight;
            }

            const queryMagnitude = Math.sqrt(queryMagnitudeSq);
            const chunkMagnitude = Math.sqrt(chunkMagnitudeSq);

            let score = 0;
            if (queryMagnitude > 0 && chunkMagnitude > 0) {
                score = dotProduct / (queryMagnitude * chunkMagnitude);
            }

            results.push({
                chunk_id: chunk.id,
                document_id: chunk.document_id,
                document_title: chunk.document_title,
                chunk_index: chunk.chunk_index,
                content: chunk.content,
                score: parseFloat(score.toFixed(4))
            });
        });

        // Sort descending by score, filtering out zero-matches
        return results
            .filter(r => r.score > 0)
            .sort((a, b) => b.score - a.score);
    }
}
