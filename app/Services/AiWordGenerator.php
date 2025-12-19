<?php

namespace App\Services;

class AiWordGenerator
{
    private array $categories = [
        'حيوان' => ['قطة', 'كلب', 'أسد', 'نمر', 'فيل', 'زرافة', 'حصان', 'بقرة', 'دجاجة', 'نحلة'],
        'طعام' => ['تفاح', 'موز', 'خبز', 'جبن', 'لحم', 'سمك', 'أرز', 'معكرونة', 'شاي', 'قهوة'],
        'مكان' => ['مدرسة', 'مستشفى', 'مطعم', 'حديقة', 'سوق', 'مطار', 'ميناء', 'ملعب', 'مسجد', 'كنيسة'],
        'مهنة' => ['طبيب', 'مهندس', 'معلم', 'شرطي', 'نجار', 'حداد', 'خباز', 'سائق', 'ممرض', 'محامي'],
        'شيء' => ['كتاب', 'قلم', 'سيارة', 'منزل', 'هاتف', 'كمبيوتر', 'ساعة', 'نظارة', 'مفتاح', 'كوب'],
    ];

    public function generateWord(string $category = 'شيء'): string
    {
        // For MVP, use mock data
        // In production, replace with Neuron AI API call

        $words = $this->categories[$category] ?? $this->categories['شيء'];

        return $words[array_rand($words)];
    }

    public function getCategories(): array
    {
        return array_keys($this->categories);
    }

    public function isValidArabicWord(string $word): bool
    {
        // Basic validation for Arabic words
        // 1. Must be Arabic characters only
        // 2. Length between 2-12 characters
        // 3. No spaces (single word)

        $arabicPattern = '/^[\p{Arabic}]{2,12}$/u';

        return preg_match($arabicPattern, $word) === 1;
    }

    /**
     * This method would call Neuron AI API in production
     */
    private function callNeuronAi(string $category): string
    {
        // Example of how to call Neuron AI with Mistral model
        // $client = new \Neuron\Client(config('services.neuron.api_key'));
        // $response = $client->chat()->create([
        //     'model' => 'mistral',
        //     'messages' => [
        //         [
        //             'role' => 'user',
        //             'content' => $this->buildPrompt($category)
        //         ]
        //     ]
        // ]);
        //
        // $word = trim($response->choices[0]->message->content);
        //
        // if (!$this->isValidArabicWord($word)) {
        //     return $this->callNeuronAi($category); // Retry
        // }
        //
        // return $word;

        return $this->generateWord($category); // Fallback to mock
    }

    private function buildPrompt(string $category): string
    {
        return <<<PROMPT
أنت مولد كلمات للعبة اجتماعية عربية اسمها "المخادع".

المطلوب:
- أعطني كلمة عربية واحدة فقط
- الكلمة يجب أن تكون:
  - معروفة
  - قابلة للوصف
  - غير غامضة
- لا تكتب شرحًا
- لا تكتب أكثر من كلمة واحدة

التصنيف: {$category}

أمثلة صحيحة:
- قطة
- مستشفى
- مهندس
- شاي

أعطني الكلمة الآن:
PROMPT;
    }
}
