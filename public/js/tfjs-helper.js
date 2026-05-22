/**
 * AetherMind TensorFlow.js Helper & Custom Neural Network Console
 */

class TfjsHelper {
    constructor() {
        this.vocabulary = ["awesome", "great", "bad", "terrible", "tốt", "tuyệt", "tệ", "lỗi", "vui", "buồn", "thích", "ghét", "chán", "sướng", "đỉnh", "dở"];
        this.customModel = null;
        this.isTraining = false;
        
        // Simple client-side sentiment score lookup dictionary (fallback if full model loads asynchronously)
        this.sentimentLexicon = {
            'great': 0.8, 'awesome': 0.9, 'excellent': 0.9, 'good': 0.6, 'love': 0.8, 'perfect': 0.9, 'amazing': 0.85,
            'cool': 0.5, 'nice': 0.4, 'happy': 0.7, 'glad': 0.6, 'secure': 0.5, 'fast': 0.6, 'beautiful': 0.7,
            'bad': -0.7, 'terrible': -0.8, 'error': -0.6, 'slow': -0.5, 'worst': -0.9, 'broken': -0.7, 'fail': -0.8,
            'crash': -0.9, 'hate': -0.8, 'sad': -0.5, 'angry': -0.7, 'useless': -0.8, 'bug': -0.5, 'warn': -0.3,
            
            // Vietnamese Lexicon (Extended - 45+ items)
            'tốt': 0.7, 'tuyệt': 0.9, 'nhanh': 0.6, 'mượt': 0.8, 'thích': 0.7, 'yêu': 0.8, 'hay': 0.6, 'ổn': 0.5,
            'đẹp': 0.7, 'chuẩn': 0.6, 'ngon': 0.6, 'dễ': 0.5, 'sạch': 0.5, 'vui': 0.8, 'sướng': 0.8, 'đỉnh': 0.9,
            'đúng': 0.6, 'giỏi': 0.7, 'hài': 0.6, 'cười': 0.6, 'tài': 0.7, 'đỉnh': 0.9, 'vip': 0.8, 'phê': 0.8,
            'tệ': -0.8, 'lỗi': -0.7, 'chậm': -0.6, 'hỏng': -0.8, 'chán': -0.7, 'kém': -0.6, 'yếu': -0.5,
            'sập': -0.9, 'đơ': -0.7, 'lag': -0.7, 'giật': -0.6, 'khó': -0.5, 'ghét': -0.8, 'tức': -0.7,
            'bực': -0.7, 'xấu': -0.6, 'mất': -0.5, 'buồn': -0.8, 'sai': -0.6, 'dở': -0.7, 'tồi': -0.8,
            'ngu': -0.9, 'ngốc': -0.7, 'điên': -0.8, 'hận': -0.9, 'đau': -0.7, 'xui': -0.6
        };
    }

    /**
     * Compute sentiment of a text block locally in real-time
     * Returns a score between 0.0 (Extremely Negative) and 1.0 (Extremely Positive)
     */
    analyzeSentiment(text) {
        if (!text || text.trim() === '') {
            return { score: 0.5, label: 'Trung tính', emoji: '😐' };
        }

        // Unicode-aware word split: matches any letter or digit, strips other symbols
        const words = text.toLowerCase().replace(/[^\p{L}\p{N}\s]/gu, ' ').split(/\s+/).filter(w => w);
        let score = 0;
        let matchedCount = 0;

        const negationWords = new Set(['không', 'chưa', 'chẳng', 'đừng', 'not', 'never']);

        for (let i = 0; i < words.length; i++) {
            const word = words[i];
            if (this.sentimentLexicon.hasOwnProperty(word)) {
                let wordScore = this.sentimentLexicon[word];
                
                // Look-back for negation logic up to 3 words
                let isNegated = false;
                for (let k = 1; k <= 3; k++) {
                    if (i - k >= 0) {
                        const prevWord = words[i - k];
                        if (negationWords.has(prevWord)) {
                            isNegated = true;
                            break;
                        }
                        // If the word in between is not an intensifier/modifier, stop looking further back
                        if (!['thực', 'sự', 'hề', 'quá', 'lắm', 'rất', 'thật', 'hết', 'sức'].includes(prevWord)) {
                            break;
                        }
                    }
                }

                if (isNegated) {
                    wordScore = wordScore * -1; // Invert the sentiment score (e.g. "tốt" 0.7 -> "không tốt" -0.7)
                }
                
                score += wordScore;
                matchedCount++;
            }
        }

        // Compute normalized score
        let finalScore = 0.5;
        if (matchedCount > 0) {
            // Map average score of matches from [-1.0, 1.0] to [0.0, 1.0]
            const average = score / matchedCount;
            finalScore = (average + 1) / 2;
        }

        // If the custom sequential model is trained, blend it into the sentiment score!
        if (this.customModel) {
            const nnResult = this.predict(text);
            if (nnResult && typeof nnResult.classScore === 'number' && !nnResult.isAllZeros) {
                if (matchedCount > 0) {
                    // Blend 60% Lexicon and 40% Sequential Model
                    finalScore = (finalScore * 0.6) + (nnResult.classScore * 0.4);
                } else {
                    // If no lexicon words match, use the sequential model prediction directly!
                    finalScore = nnResult.classScore;
                }
            }
        }

        // Clip bounds
        finalScore = Math.max(0.0, Math.min(1.0, finalScore));

        // Determine label & emoji
        let label = 'Trung tính';
        let emoji = '😐';
        if (finalScore > 0.6) {
            label = 'Tích cực';
            emoji = '😊';
        } else if (finalScore < 0.4) {
            label = 'Tiêu cực';
            emoji = '😠';
        }

        return {
            score: parseFloat(finalScore.toFixed(2)),
            label: label,
            emoji: emoji
        };
    }

    /**
     * Set up and train a custom TF.js model right in the user's browser
     */
    async trainBrowserNetwork(epochs, onEpochCallback) {
        if (this.isTraining) return;
        this.isTraining = true;

        // Seeding vocabulary-based bag of words training set
        // Feature size = 16: ["awesome", "great", "bad", "terrible", "tốt", "tuyệt", "tệ", "lỗi", "vui", "buồn", "thích", "ghét", "chán", "sướng", "đỉnh", "dở"]
        // positive label = 1, negative label = 0
        const vocabSize = 16;
        
        // Define training vectors (features matching the 16 vocabulary dimensions)
        const xData = [
            [1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0], // awesome
            [0, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0], // great
            [0, 0, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0], // bad
            [0, 0, 0, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0], // terrible
            [0, 0, 0, 0, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0], // tốt
            [0, 0, 0, 0, 0, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0], // tuyệt
            [0, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0], // tệ
            [0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0, 0, 0, 0, 0], // lỗi
            [0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0, 0, 0, 0], // vui
            [0, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0, 0, 0], // buồn
            [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0, 0], // thích
            [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0], // ghét
            [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0], // chán
            [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0], // sướng
            [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0], // đỉnh
            [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1], // dở
            [1, 1, 0, 0, 1, 1, 0, 0, 1, 0, 1, 0, 0, 1, 1, 0], // positives
            [0, 0, 1, 1, 0, 0, 1, 1, 0, 1, 0, 1, 1, 0, 0, 1], // negatives
            [1, 0, 0, 0, 1, 0, 0, 0, 1, 0, 0, 0, 0, 0, 1, 0], // awesome tốt vui đỉnh
            [0, 0, 1, 0, 0, 0, 1, 0, 0, 1, 0, 1, 1, 0, 0, 1]  // bad tệ buồn ghét chán dở
        ];

        const yData = [
            [1], [1], [0], [0], [1], [1], [0], [0],
            [1], [0], [1], [0], [0], [1], [1], [0],
            [1], [0], [1], [0]
        ];

        const xs = tf.tensor2d(xData);
        const ys = tf.tensor2d(yData);

        // Define a sequential model
        const model = tf.sequential();
        model.add(tf.layers.dense({
            units: 16, 
            activation: 'relu', 
            inputShape: [vocabSize]
        }));
        model.add(tf.layers.dense({
            units: 8, 
            activation: 'relu'
        }));
        model.add(tf.layers.dense({
            units: 1, 
            activation: 'sigmoid'
        }));

        // Compile the model using Adam optimizer and binary crossentropy
        model.compile({
            optimizer: tf.train.adam(0.05),
            loss: 'binaryCrossentropy',
            metrics: ['accuracy']
        });

        const historyPoints = [];

        await model.fit(xs, ys, {
            epochs: epochs,
            shuffle: true,
            callbacks: {
                onEpochEnd: (epoch, logs) => {
                    historyPoints.push({ epoch: epoch + 1, loss: logs.loss });
                    if (onEpochCallback) {
                        onEpochCallback(epoch + 1, logs.loss, logs.acc, historyPoints);
                    }
                }
            }
        });

        this.customModel = model;
        this.isTraining = false;
        
        // Clean up tensors
        xs.dispose();
        ys.dispose();
        
        return model;
    }

    /**
     * Run predictions using our trained model
     */
    predict(text) {
        if (!this.customModel) {
            return { error: "Mô hình chưa được huấn luyện." };
        }

        const t = text.toLowerCase();
        // Construct feature vector dynamically based on vocabulary
        // Ignore the word if it is preceded by a negation within 3 words
        const vector = this.vocabulary.map(word => {
            let negated = false;
            const wordIdx = t.indexOf(word);
            if (wordIdx > 0) {
                const subStr = t.substring(0, wordIdx).trim();
                const subWords = subStr.split(/\s+/);
                const lastFewWords = subWords.slice(-4); // Look back at preceding words
                for (let k = 0; k < lastFewWords.length; k++) {
                    const w = lastFewWords[k];
                    if (["không", "chưa", "chẳng", "đừng", "not", "never"].includes(w)) {
                        negated = true;
                        break;
                    }
                }
            }
            return (t.includes(word) && !negated) ? 1 : 0;
        });

        const isAllZeros = vector.every(v => v === 0);

        // Perform tensor inference
        const inputTensor = tf.tensor2d([vector]);
        const outputTensor = this.customModel.predict(inputTensor);
        const predictionValue = outputTensor.dataSync()[0];

        inputTensor.dispose();
        outputTensor.dispose();

        return {
            classScore: parseFloat(predictionValue.toFixed(4)),
            classification: predictionValue > 0.5 ? 'Thiên hướng tích cực' : 'Thiên hướng tiêu cực',
            isAllZeros: isAllZeros
        };
    }
}
