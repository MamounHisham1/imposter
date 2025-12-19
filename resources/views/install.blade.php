<!DOCTYPE html>
<html lang="ar" dir="rtl" class="dark">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="theme-color" content="#3b82f6" />

    <title>تثبيت المخادع - لعبة الكلمة السرية</title>

    <link rel="icon" href="/favicon.ico" sizes="any">
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    <link rel="apple-touch-icon" href="/apple-touch-icon.png">

    <!-- PWA Meta Tags -->
    <link rel="manifest" href="/manifest.json">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="المخادع">

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        .install-hero {
            background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
        }

        .game-phase {
            border-right: 4px solid #3b82f6;
            transition: all 0.3s ease;
        }

        .game-phase:hover {
            transform: translateX(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }

        .phase-icon {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
        }

        .install-btn {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            transition: all 0.3s ease;
        }

        .install-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(16, 185, 129, 0.3);
        }

        .feature-card {
            transition: all 0.3s ease;
        }

        .feature-card:hover {
            transform: translateY(-5px);
        }
    </style>
</head>
<body class="min-h-screen bg-neutral-50 dark:bg-neutral-900 text-neutral-900 dark:text-neutral-100">
    <!-- Hero Section -->
    <div class="install-hero text-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
            <div class="text-center">
                <h1 class="text-4xl md:text-5xl font-bold mb-6">🎮 المخادع</h1>
                <p class="text-xl md:text-2xl mb-8 opacity-90">لعبة الكلمة السرية الاجتماعية باللغة العربية</p>
                <p class="text-lg mb-10 max-w-3xl mx-auto opacity-80">
                    لعبة اجتماعية ممتعة حيث يحاول لاعب واحد (المخادع) التخفي بين اللاعبين الآخرين الذين يعرفون الكلمة السرية!
                </p>

                <div class="flex flex-col sm:flex-row gap-4 justify-center">
                    <a href="/" class="bg-white text-blue-600 font-semibold py-3 px-8 rounded-lg hover:bg-blue-50 transition duration-200">
                        🎲 ابدأ اللعب الآن
                    </a>
                    <button onclick="installPWA()" class="install-btn text-white font-semibold py-3 px-8 rounded-lg">
                        📲 تثبيت التطبيق
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Game Explanation -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
        <div class="text-center mb-16">
            <h2 class="text-3xl font-bold text-gray-900 dark:text-white mb-4">كيف تلعب المخادع؟</h2>
            <p class="text-lg text-gray-600 dark:text-gray-300 max-w-3xl mx-auto">
                لعبة اجتماعية عربية تجمع بين الذكاء والمرح. كل جولة تستمر دقائق قليلة فقط!
            </p>
        </div>

        <!-- Game Phases -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-8 mb-16">
            @php
                $phases = [
                    ['icon' => '👥', 'title' => 'الانتظار', 'desc' => 'ينضم 3-8 لاعبين للغرفة', 'color' => 'bg-blue-100 dark:bg-blue-900'],
                    ['icon' => '🎭', 'title' => 'توزيع الأدوار', 'desc' => 'يختار النظام مخادعًا واحدًا بشكل عشوائي', 'color' => 'bg-purple-100 dark:bg-purple-900'],
                    ['icon' => '💡', 'title' => 'التلميحات', 'desc' => 'كل لاعب يعطي تلميحًا عن الكلمة السرية', 'color' => 'bg-yellow-100 dark:bg-yellow-900'],
                    ['icon' => '🗳️', 'title' => 'التصويت', 'desc' => 'اللاعبون يصوتون لمن يعتقدون أنه المخادع', 'color' => 'bg-red-100 dark:bg-red-900'],
                    ['icon' => '🏆', 'title' => 'النتائج', 'desc' => 'يكشف النظام المخادع ويحسب النقاط', 'color' => 'bg-green-100 dark:bg-green-900'],
                ];
            @endphp

            @foreach ($phases as $phase)
                <div class="game-phase bg-white dark:bg-gray-800 rounded-xl p-6">
                    <div class="phase-icon {{ $phase['color'] }} text-gray-800 dark:text-white mb-4 mx-auto">
                        {{ $phase['icon'] }}
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">{{ $phase['title'] }}</h3>
                    <p class="text-gray-600 dark:text-gray-300 text-sm">{{ $phase['desc'] }}</p>
                </div>
            @endforeach
        </div>

        <!-- Game Rules -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-8 mb-16">
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-8 text-center">قواعد اللعبة</h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <div class="space-y-6">
                    <div class="feature-card bg-blue-50 dark:bg-blue-900/20 rounded-xl p-6">
                        <div class="flex items-start space-x-4 space-x-reverse">
                            <div class="w-12 h-12 rounded-full bg-blue-100 dark:bg-blue-800 flex items-center justify-center">
                                <span class="text-blue-600 dark:text-blue-300 text-xl">🎯</span>
                            </div>
                            <div>
                                <h3 class="font-semibold text-gray-900 dark:text-white mb-2">هدف اللعبة</h3>
                                <p class="text-gray-600 dark:text-gray-300">
                                    على اللاعبين العاديين اكتشاف المخادع، وعلى المخادع التخفي دون أن ينكشف!
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="feature-card bg-green-50 dark:bg-green-900/20 rounded-xl p-6">
                        <div class="flex items-start space-x-4 space-x-reverse">
                            <div class="w-12 h-12 rounded-full bg-green-100 dark:bg-green-800 flex items-center justify-center">
                                <span class="text-green-600 dark:text-green-300 text-xl">⭐</span>
                            </div>
                            <div>
                                <h3 class="font-semibold text-gray-900 dark:text-white mb-2">النقاط</h3>
                                <ul class="text-gray-600 dark:text-gray-300 space-y-1">
                                    <li>• +1 نقطة لكل لاعب يصوت للمخادع</li>
                                    <li>• +1 نقطة للمخادع إذا لم ينكشف</li>
                                    <li>• 0 نقطة في الحالات الأخرى</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="space-y-6">
                    <div class="feature-card bg-purple-50 dark:bg-purple-900/20 rounded-xl p-6">
                        <div class="flex items-start space-x-4 space-x-reverse">
                            <div class="w-12 h-12 rounded-full bg-purple-100 dark:bg-purple-800 flex items-center justify-center">
                                <span class="text-purple-600 dark:text-purple-300 text-xl">💬</span>
                            </div>
                            <div>
                                <h3 class="font-semibold text-gray-900 dark:text-white mb-2">التلميحات</h3>
                                <p class="text-gray-600 dark:text-gray-300">
                                    يجب أن تكون التلميحات باللغة العربية، كلمة واحدة أو جملة قصيرة (3 كلمات كحد أقصى).
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="feature-card bg-orange-50 dark:bg-orange-900/20 rounded-xl p-6">
                        <div class="flex items-start space-x-4 space-x-reverse">
                            <div class="w-12 h-12 rounded-full bg-orange-100 dark:bg-orange-800 flex items-center justify-center">
                                <span class="text-orange-600 dark:text-orange-300 text-xl">⚡</span>
                            </div>
                            <div>
                                <h3 class="font-semibold text-gray-900 dark:text-white mb-2">الوقت الحقيقي</h3>
                                <p class="text-gray-600 dark:text-gray-300">
                                    تحديثات فورية بدون تحديث الصفحة. انضم من أي جهاز!
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- PWA Benefits -->
        <div class="bg-gradient-to-r from-blue-50 to-purple-50 dark:from-gray-800 dark:to-gray-900 rounded-2xl p-8">
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-6 text-center">لماذا تثبيت التطبيق؟</h2>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="text-center p-6">
                    <div class="w-16 h-16 rounded-full bg-blue-100 dark:bg-blue-800 flex items-center justify-center mx-auto mb-4">
                        <span class="text-blue-600 dark:text-blue-300 text-2xl">📱</span>
                    </div>
                    <h3 class="font-semibold text-gray-900 dark:text-white mb-2">تطبيق سريع</h3>
                    <p class="text-gray-600 dark:text-gray-300">يعمل مثل التطبيق الأصلي على هاتفك</p>
                </div>

                <div class="text-center p-6">
                    <div class="w-16 h-16 rounded-full bg-green-100 dark:bg-green-800 flex items-center justify-center mx-auto mb-4">
                        <span class="text-green-600 dark:text-green-300 text-2xl">⚡</span>
                    </div>
                    <h3 class="font-semibold text-gray-900 dark:text-white mb-2">عمل دون اتصال</h3>
                    <p class="text-gray-600 dark:text-gray-300">بعض الميزات تعمل بدون إنترنت</p>
                </div>

                <div class="text-center p-6">
                    <div class="w-16 h-16 rounded-full bg-purple-100 dark:bg-purple-800 flex items-center justify-center mx-auto mb-4">
                        <span class="text-purple-600 dark:text-purple-300 text-2xl">🔔</span>
                    </div>
                    <h3 class="font-semibold text-gray-900 dark:text-white mb-2">إشعارات</h3>
                    <p class="text-gray-600 dark:text-gray-300">إشعارات عند بدء الجولات الجديدة</p>
                </div>
            </div>

            <div class="text-center mt-8">
                <button onclick="installPWA()" class="install-btn text-white font-semibold py-3 px-8 rounded-lg text-lg">
                    📲 تثبيت التطبيق مجانًا
                </button>
                <p class="text-gray-600 dark:text-gray-300 mt-4">لا يتطلب متجر تطبيقات - تثبيت مباشر من المتصفح</p>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-gray-800 text-white py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center">
                <p class="mb-4">🎮 <strong>المخادع</strong> - لعبة الكلمة السرية الاجتماعية</p>
                <p class="text-gray-400">جميع الحقوق محفوظة © {{ date('Y') }}</p>
                <div class="mt-6">
                    <a href="/" class="text-blue-300 hover:text-white mx-4">الرئيسية</a>
                    <a href="/install" class="text-blue-300 hover:text-white mx-4">التثبيت</a>
                    <a href="/create-room" class="text-blue-300 hover:text-white mx-4">إنشاء غرفة</a>
                </div>
            </div>
        </div>
    </footer>

    <!-- PWA Installation Script -->
    <script>
        let deferredPrompt;
        const installButton = document.querySelectorAll('[onclick="installPWA()"]');

        // Listen for beforeinstallprompt event
        window.addEventListener('beforeinstallprompt', (e) => {
            // Prevent Chrome 67 and earlier from automatically showing the prompt
            e.preventDefault();
            // Stash the event so it can be triggered later
            deferredPrompt = e;

            // Show install buttons
            installButton.forEach(btn => {
                btn.style.display = 'inline-block';
            });
        });

        function installPWA() {
            if (!deferredPrompt) {
                // If beforeinstallprompt hasn't fired, show instructions
                showInstallInstructions();
                return;
            }

            // Show the install prompt
            deferredPrompt.prompt();

            // Wait for the user to respond to the prompt
            deferredPrompt.userChoice.then((choiceResult) => {
                if (choiceResult.outcome === 'accepted') {
                    console.log('User accepted the install prompt');
                    // Hide install buttons after installation
                    installButton.forEach(btn => {
                        btn.style.display = 'none';
                    });
                } else {
                    console.log('User dismissed the install prompt');
                }

                // Clear the saved prompt since it can't be used again
                deferredPrompt = null;
            });
        }

        function showInstallInstructions() {
            const isIOS = /iPad|iPhone|iPod/.test(navigator.userAgent) && !window.MSStream;
            const isAndroid = /Android/.test(navigator.userAgent);

            let message = '';

            if (isIOS) {
                message = 'لتثبيت التطبيق على iOS:\n1. افتح الموقع في Safari\n2. انقر على زر المشاركة (📤)\n3. اختر "أضف إلى الشاشة الرئيسية"\n4. انقر على "إضافة"';
            } else if (isAndroid) {
                message = 'لتثبيت التطبيق على Android:\n1. افتح القائمة (النقاط الثلاث)\n2. اختر "تثبيت التطبيق"\n3. انقر على "تثبيت"';
            } else {
                message = 'لتثبيت التطبيق:\n1. انقر على زر التثبيت في شريط العنوان\n2. اتبع التعليمات الظاهرة';
            }

            alert(message);
        }

        // Register service worker
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('/sw.js')
                    .then(registration => {
                        console.log('ServiceWorker registration successful:', registration.scope);
                    })
                    .catch(error => {
                        console.log('ServiceWorker registration failed:', error);
                    });
            });
        }
    </script>
</body>
</html>