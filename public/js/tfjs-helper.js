/**
 * AetherMind TensorFlow.js Helper & Custom Neural Network Console
 */

class TfjsHelper {
    constructor() {
        this.vocabulary = ["awesome", "great", "love", "good", "perfect", "bad", "slow", "error", "terrible", "worst", "broken", "fail"];
        this.customModel = null;
        this.isTraining = false;
        
        // Simple client-side sentiment score lookup dictionary (fallback if full model loads asynchronously)
        this.sentimentLexicon = {
            'great': 0.8, 'awesome': 0.9, 'excellent': 0.9, 'good': 0.6, 'love': 0.8, 'perfect': 0.9, 'amazing': 0.85,
            'cool': 0.5, 'nice': 0.4, 'happy': 0.7, 'glad': 0.6, 'secure': 0.5, 'fast': 0.6, 'beautiful': 0.7,
            'bad': -0.7, 'terrible': -0.8, 'error': -0.6, 'slow': -0.5, 'worst': -0.9, 'broken': -0.7, 'fail': -0.8,
            'crash': -0.9, 'hate': -0.8, 'sad': -0.5, 'angry': -0.7, 'useless': -0.8, 'bug': -0.5, 'warn': -0.3
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

        const words = text.toLowerCase().split(/[^\w]+/);
        let score = 0;
        let matchedCount = 0;

        words.forEach(word => {
            if (this.sentimentLexicon.hasOwnProperty(word)) {
                score += this.sentimentLexicon[word];
                matchedCount++;
            }
        });

        // Compute normalized score
        let finalScore = 0.5;
        if (matchedCount > 0) {
            // Map average score of matches from [-1.0, 1.0] to [0.0, 1.0]
            const average = score / matchedCount;
            finalScore = (average + 1) / 2;
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
        // Feature size = 4: ['awesome', 'great', 'bad', 'terrible']
        // positive label = 1, negative label = 0
        const vocabSize = 4;
        
        // Define training vectors (features matching [awesome, great, bad, terrible])
        const xData = [
            [1, 0, 0, 0], // "awesome build" -> positive
            [0, 1, 0, 0], // "great job" -> positive
            [1, 1, 0, 0], // "awesome great code" -> positive
            [0, 0, 1, 0], // "very bad speed" -> negative
            [0, 0, 0, 1], // "terrible crash" -> negative
            [0, 0, 1, 1], // "bad terrible error" -> negative
            [1, 0, 1, 0], // "awesome but bad" -> positive (weighted awesome)
            [0, 1, 0, 1]  // "great but terrible" -> negative (weighted terrible)
        ];

        const yData = [
            [1],
            [1],
            [1],
            [0],
            [0],
            [0],
            [1],
            [0]
        ];

        const xs = tf.tensor2d(xData);
        const ys = tf.tensor2d(yData);

        // Define a sequential model
        const model = tf.sequential();
        model.add(tf.layers.dense({
            units: 8, 
            activation: 'relu', 
            inputShape: [vocabSize]
        }));
        model.add(tf.layers.dense({
            units: 4, 
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
        // Construct feature vector for [awesome, great, bad, terrible]
        const vector = [
            t.includes("awesome") ? 1 : 0,
            t.includes("great") ? 1 : 0,
            t.includes("bad") ? 1 : 0,
            t.includes("terrible") ? 1 : 0
        ];

        // Perform tensor inference
        const inputTensor = tf.tensor2d([vector]);
        const outputTensor = this.customModel.predict(inputTensor);
        const predictionValue = outputTensor.dataSync()[0];

        inputTensor.dispose();
        outputTensor.dispose();

        return {
            classScore: parseFloat(predictionValue.toFixed(4)),
            classification: predictionValue > 0.5 ? 'Thiên hướng tích cực' : 'Thiên hướng tiêu cực'
        };
    }
}
