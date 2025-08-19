<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>اغتنم - Ightanem</title>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                fontFamily: {
                    'sans': ['"Readex Pro"', '"Scheherazade New"', '"Amiri"', 'serif'],
                    'display': ['"Aref Ruqaa"', '"Scheherazade New"', 'serif'],
                    'quran': ['Amiri', 'serif'],
                    'modern': ['"Readex Pro"', 'sans-serif']
                },
                extend: {
                    colors: {
                        'primary': '#0C6E54',
                        'primary-dark': '#0a4a3a',
                        'secondary': '#C9A55C',
                        'secondary-light': '#E9D8AE',
                        'cream': '#F8F5EC',
                    },
                    animation: {
                        'float': 'float 3s ease-in-out infinite',
                        'pulse-slow': 'pulse 4s cubic-bezier(0.4, 0, 0.6, 1) infinite',
                    },
                    keyframes: {
                        float: {
                            '0%, 100%': { transform: 'translateY(0)' },
                            '50%': { transform: 'translateY(-10px)' },
                        }
                    }
                }
            }
        }
    </script>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Amiri:wght@400;700&family=Aref+Ruqaa:wght@400;700&family=Scheherazade+New:wght@400;700&family=Readex+Pro:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Icons -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <style>
        .geometric-divider {
            height: 12px;
            background-image: url("data:image/svg+xml,%3Csvg width='60' height='12' viewBox='0 0 60 12' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M0 6 L15 0 L30 6 L45 0 L60 6 L45 12 L30 6 L15 12 Z' fill='%23C9A55C'/%3E%3C/svg%3E");
            background-repeat: repeat-x;
            background-size: 60px 12px;
        }
        
        .islamic-pattern {
            background-image: url("data:image/svg+xml,%3Csvg width='100' height='100' viewBox='0 0 100 100' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath fill='%230C6E54' fill-opacity='0.03' d='M11 18c3.866 0 7-3.134 7-7s-3.134-7-7-7-7 3.134-7 7 3.134 7 7 7zm48 25c3.866 0 7-3.134 7-7s-3.134-7-7-7-7 3.134-7 7 3.134 7 7 7zm-43-7c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zm63 31c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zM34 90c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zm56-76c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zM12 86c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm28-65c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm23-11c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm-6 60c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm29 22c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zM32 63c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm57-13c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm-9-21c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM60 91c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM35 41c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM12 60c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2z'%3E%3C/path%3E%3C/svg%3E");
        }
        
        .elegant-shadow {
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
        }
        
        .feature-card {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .feature-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.07);
        }
        
        .quran-text {
            font-family: 'Amiri', serif;
            line-height: 2;
            letter-spacing: 0.02em;
        }
        
        .pattern-diagonal-lines {
            background-image: url("data:image/svg+xml,%3Csvg width='40' height='40' viewBox='0 0 40 40' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M0 40L40 0H20L0 20M40 40V20L20 40' fill='%23FFFFFF' fill-opacity='0.05'/%3E%3C/svg%3E");
        }
        
        .arabesque-border {
            position: relative;
        }
        
        .arabesque-border::before, 
        .arabesque-border::after {
            content: '✧';
            color: #C9A55C;
            font-size: 1.5rem;
            display: inline-block;
            margin: 0 0.5rem;
            opacity: 0.8;
        }
    </style>
</head>
<body class="bg-gray-50 text-gray-800 islamic-pattern font-modern">
    <!-- Header -->
    <header class="py-16 px-4 text-center relative overflow-hidden">
        <div class="absolute inset-0 bg-primary opacity-5 pattern-diagonal-lines"></div>
        <div class="max-w-4xl mx-auto relative">
            <h1 class="text-6xl font-bold mb-2 font-display text-primary tracking-wider animate-float">اغتنم</h1>
            <div class="text-xl text-secondary font-medium mb-2">Ightanem</div>
            
            <div class="geometric-divider my-6 mx-auto w-48"></div>
            
            <p class="text-2xl text-primary-dark font-display mb-10 arabesque-border">
                اغتنم وقتك في طاعة الله
            </p>
            
            <!-- Quran Verse -->
            <div class="max-w-2xl mx-auto mb-12 p-8 bg-white rounded-xl shadow-md elegant-shadow border border-gray-50 transform transition-all hover:shadow-lg">
                <p class="quran-text text-2xl leading-relaxed mb-6 tracking-wide text-right">
                    ﴿ وَسَارِعُوا إِلَىٰ مَغْفِرَةٍ مِّن رَّبِّكُمْ وَجَنَّةٍ عَرْضُهَا السَّمَاوَاتُ وَالْأَرْضُ أُعِدَّتْ لِلْمُتَّقِينَ ﴾
                </p>
                <p class="text-sm text-gray-600 font-modern text-right">
                    "And hasten to forgiveness from your Lord and a garden as wide as the heavens and earth, prepared for the righteous"
                </p>
            </div>
        </div>
    </header>

    <!-- Main Features -->
    <section class="py-20 px-4 bg-white">
        <div class="max-w-6xl mx-auto">
            <h2 class="text-4xl font-bold text-center mb-16 font-display text-primary-dark">مميزات التطبيق</h2>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <!-- Feature 1 -->
                <div class="bg-cream p-8 rounded-xl shadow-sm text-center feature-card border border-secondary-light border-opacity-20 hover:border-opacity-40">
                    <div class="w-16 h-16 flex items-center justify-center mx-auto mb-5 bg-primary text-white rounded-full animate-pulse-slow">
                        <i class="fas fa-book-quran text-xl"></i>
                    </div>
                    <h3 class="text-2xl font-bold mb-4 font-display text-primary-dark">الحفاظ على أورادك</h3>
                    <p class="text-gray-700 leading-relaxed">متابعة منتظمة للأوراد اليومية والأذكار من خلال تذكيرات مخصصة ومتابعة إحصائية</p>
                </div>

                <!-- Feature 2 -->
                <div class="bg-cream p-8 rounded-xl shadow-sm text-center feature-card border border-secondary-light border-opacity-20 hover:border-opacity-40">
                    <div class="w-16 h-16 flex items-center justify-center mx-auto mb-5 bg-primary text-white rounded-full animate-pulse-slow" style="animation-delay: 1s;">
                        <i class="fas fa-mosque text-xl"></i>
                    </div>
                    <h3 class="text-2xl font-bold mb-4 font-display text-primary-dark">مواقيت الصلاة</h3>
                    <p class="text-gray-700 leading-relaxed">تنبيهات دقيقة لأوقات الصلاة حسب موقعك مع إمكانية تعديل الإعدادات حسب احتياجاتك</p>
                </div>

                <!-- Feature 3 -->
                <div class="bg-cream p-8 rounded-xl shadow-sm text-center feature-card border border-secondary-light border-opacity-20 hover:border-opacity-40">
                    <div class="w-16 h-16 flex items-center justify-center mx-auto mb-5 bg-primary text-white rounded-full animate-pulse-slow" style="animation-delay: 2s;">
                        <i class="fas fa-graduation-cap text-xl"></i>
                    </div>
                    <h3 class="text-2xl font-bold mb-4 font-display text-primary-dark">دروس وفوائد</h3>
                    <p class="text-gray-700 leading-relaxed">محتوى إسلامي موثوق لزيادة معرفتك الدينية من مصادر موثوقة ومعتمدة</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Secondary Features -->
    <section class="py-20 px-4 bg-gray-50 relative">
        <div class="absolute inset-0 opacity-30 bg-gradient-to-b from-white via-transparent to-white"></div>
        <div class="max-w-6xl mx-auto relative">
            <h2 class="text-3xl font-bold text-center mb-16 font-display text-primary-dark arabesque-border">مميزات إضافية</h2>
            
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                <div class="bg-white p-6 rounded-xl shadow-md text-center feature-card border border-gray-100">
                    <div class="w-14 h-14 flex items-center justify-center mx-auto mb-4 bg-secondary-light text-secondary rounded-full">
                        <i class="fas fa-quran text-xl"></i>
                    </div>
                    <h3 class="text-xl font-bold mb-3 font-display text-primary">القرآن الكريم</h3>
                    <p class="text-gray-700">المصحف الكامل مع تفسير وترجمة معاني القرآن</p>
                </div>
                
                <div class="bg-white p-6 rounded-xl shadow-md text-center feature-card border border-gray-100">
                    <div class="w-14 h-14 flex items-center justify-center mx-auto mb-4 bg-secondary-light text-secondary rounded-full">
                        <i class="fas fa-user-friends text-xl"></i>
                    </div>
                    <h3 class="text-xl font-bold mb-3 font-display text-primary">قصص الأنبياء</h3>
                    <p class="text-gray-700">قصص الأنبياء والصالحين بأسلوب شيق ومبسط</p>
                </div>
                
                <div class="bg-white p-6 rounded-xl shadow-md text-center feature-card border border-gray-100">
                    <div class="w-14 h-14 flex items-center justify-center mx-auto mb-4 bg-secondary-light text-secondary rounded-full">
                        <i class="fas fa-microphone-alt text-xl"></i>
                    </div>
                    <h3 class="text-xl font-bold mb-3 font-display text-primary">تلاوات قرآنية</h3>
                    <p class="text-gray-700">تلاوات متنوعة لكبار القراء بجودة صوت عالية</p>
                </div>
                
                <div class="bg-white p-6 rounded-xl shadow-md text-center feature-card border border-gray-100">
                    <div class="w-14 h-14 flex items-center justify-center mx-auto mb-4 bg-secondary-light text-secondary rounded-full">
                        <i class="fas fa-calendar-alt text-xl"></i>
                    </div>
                    <h3 class="text-xl font-bold mb-3 font-display text-primary">التقويم الهجري</h3>
                    <p class="text-gray-700">متابعة التقويم الهجري مع المناسبات الإسلامية</p>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="py-24 px-4 bg-primary text-white text-center relative overflow-hidden">
        <div class="absolute inset-0 bg-black opacity-10 pattern-diagonal-lines"></div>
        <div class="max-w-3xl mx-auto relative">
            <h2 class="text-4xl font-bold mb-8 font-display tracking-wide arabesque-border">حمّل تطبيق اغتنم الآن</h2>
            <p class="text-xl mb-10 opacity-95 leading-relaxed">
                استفد من مجموعة كاملة من الميزات الإسلامية لمساعدتك في رحلتك الروحانية والتقرب إلى الله
            </p>
            
            <a href="#" class="inline-flex items-center px-8 py-4 bg-secondary hover:bg-secondary-light text-white font-medium rounded-lg transition-all shadow-lg hover:shadow-xl transform hover:-translate-y-1">
                <span class="text-lg">حمّل التطبيق الآن</span>
                <i class="fas fa-download mr-3"></i>
            </a>
            
            <div class="flex justify-center mt-16 space-x-6 space-x-reverse">
                <div class="w-20 h-44 bg-white bg-opacity-10 rounded-2xl transform -rotate-6 shadow-lg animate-float" style="animation-delay: 0.5s;"></div>
                <div class="w-28 h-52 bg-white bg-opacity-20 rounded-2xl shadow-lg z-10 animate-float"></div>
                <div class="w-20 h-44 bg-white bg-opacity-10 rounded-2xl transform rotate-6 shadow-lg animate-float" style="animation-delay: 1s;"></div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="py-16 px-4 bg-primary-dark text-white relative">
        <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-transparent via-secondary to-transparent opacity-60"></div>
        <div class="max-w-6xl mx-auto">
            <div class="text-center mb-12">
                <h2 class="text-3xl font-bold mb-3 font-display">اغتنم</h2>
                <div class="geometric-divider my-5 mx-auto w-36 opacity-50"></div>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-10 mb-12">
                <div>
                    <h3 class="text-xl font-semibold mb-6 font-display">روابط مفيدة</h3>
                    <ul class="space-y-3">
                        <li><a href="#" class="opacity-80 hover:opacity-100 transition-all font-medium hover:text-secondary">من نحن</a></li>
                        <li><a href="#" class="opacity-80 hover:opacity-100 transition-all font-medium hover:text-secondary">اتصل بنا</a></li>
                        <li><a href="#" class="opacity-80 hover:opacity-100 transition-all font-medium hover:text-secondary">الأسئلة الشائعة</a></li>
                        <li><a href="#" class="opacity-80 hover:opacity-100 transition-all font-medium hover:text-secondary">سياسة الخصوصية</a></li>
                    </ul>
                </div>
                
                <div>
                    <h3 class="text-xl font-semibold mb-6 font-display">تحميل التطبيق</h3>
                    <div class="flex flex-col space-y-4">
                        <a href="#" class="opacity-80 hover:opacity-100 transition-all flex items-center group">
                            <i class="fab fa-apple ml-3 text-xl group-hover:text-secondary"></i>
                            <span class="font-medium">App Store</span>
                        </a>
                        <a href="#" class="opacity-80 hover:opacity-100 transition-all flex items-center group">
                            <i class="fab fa-google-play ml-3 text-xl group-hover:text-secondary"></i>
                            <span class="font-medium">Google Play</span>
                        </a>
                    </div>
                </div>

                <div>
                    <h3 class="text-xl font-semibold mb-6 font-display">تواصل معنا</h3>
                    <p class="opacity-80 mb-4 flex items-center group hover:opacity-100"><i class="fas fa-envelope ml-3 text-secondary"></i> info@ightanem.com</p>
                    <p class="opacity-80 flex items-center group hover:opacity-100"><i class="fas fa-phone ml-3 text-secondary"></i> +966 XX XXX XXXX</p>
                </div>
            </div>

            <div class="pt-10 border-t border-primary text-center">
                <div class="flex justify-center space-x-8 space-x-reverse mb-8">
                    <a href="#" class="opacity-70 hover:opacity-100 transition-all text-xl hover:text-secondary transform hover:scale-110"><i class="fab fa-facebook-f"></i></a>
                    <a href="#" class="opacity-70 hover:opacity-100 transition-all text-xl hover:text-secondary transform hover:scale-110"><i class="fab fa-twitter"></i></a>
                    <a href="#" class="opacity-70 hover:opacity-100 transition-all text-xl hover:text-secondary transform hover:scale-110"><i class="fab fa-instagram"></i></a>
                    <a href="#" class="opacity-70 hover:opacity-100 transition-all text-xl hover:text-secondary transform hover:scale-110"><i class="fab fa-youtube"></i></a>
                </div>
                <p class="text-sm opacity-70 font-modern">
                    &copy; ٢٠٢٥ اغتنم. جميع الحقوق محفوظة
                </p>
                <p class="text-xs opacity-50 mt-2 font-modern">
                    بُني بكل ❤️ لخدمة الأمة الإسلامية
                </p>
            </div>
        </div>
    </footer>
</body>
</html>