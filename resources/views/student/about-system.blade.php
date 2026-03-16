@extends('layouts.app')

@section('title', 'About QuizSnap')
@section('body_class', '')

@push('styles')
<style>
    body { background: #f8fafc !important; }
    .logo-text { 
        font-size: 1.75rem; 
        font-weight: 700; 
        letter-spacing: -0.02em; 
        display: inline-flex; 
        align-items: center; 
        gap: 0.5rem; 
    }
    .logo-mark { 
        width: 3rem; 
        height: 3rem; 
        flex-shrink: 0; 
    }
    .section-icon-box {
        width: 64px;
        height: 64px;
        border-radius: 16px;
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        flex-shrink: 0;
    }
    .section-icon-box svg {
        width: 32px;
        height: 32px;
        color: white;
        stroke-width: 2;
    }
</style>
@endpush

@section('content')
<div class="min-h-screen flex flex-col font-sans antialiased">
    <header class="shrink-0 bg-white border-b border-slate-200 sticky top-0 z-50 shadow-sm">
        <div class="mx-auto max-w-7xl px-6">
            <div class="flex h-20 md:h-24 items-center justify-between">
                <a href="{{ route('student.landing') }}" class="logo-text no-underline flex items-center gap-2">
                    <img src="{{ asset('assets/mainlogo-quizsnap.png') }}" alt="QuizSnap" class="logo-mark h-12 w-auto object-contain">
                </a>
                <nav class="flex items-center gap-6">
                    <a href="{{ route('about-system') }}" class="text-sm font-medium text-slate-700 hover:text-blue-600 transition-colors no-underline" style="text-decoration: none; color: #475569;">
                        About System
                    </a>
                    @if(isset($student) && $student)
                        <a href="{{ route('dashboard') }}" style="display: inline-block; padding: 0.625rem 1.5rem; background-color: #2563eb; color: #ffffff !important; font-weight: 600; font-size: 0.875rem; border-radius: 0.5rem; text-decoration: none; transition: background-color 0.2s;" onmouseover="this.style.backgroundColor='#1d4ed8'" onmouseout="this.style.backgroundColor='#2563eb'">
                            Dashboard
                        </a>
                    @else
                        <a href="{{ route('student.account.login.form') }}" style="display: inline-block; padding: 0.625rem 1.5rem; background-color: #2563eb; color: #ffffff !important; font-weight: 600; font-size: 0.875rem; border-radius: 0.5rem; text-decoration: none; transition: background-color 0.2s;" onmouseover="this.style.backgroundColor='#1d4ed8'" onmouseout="this.style.backgroundColor='#2563eb'">
                            Student Login
                        </a>
                    @endif
                </nav>
            </div>
        </div>
    </header>

    <main class="flex-1 px-6 py-12">
        <div class="max-w-4xl mx-auto">
            <div class="mb-8">
                <a href="{{ route('student.landing') }}" class="text-sm text-blue-600 hover:text-blue-800 font-medium inline-flex items-center gap-1">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    Back to Home
                </a>
            </div>

            <div class="bg-white rounded-2xl border border-slate-200 p-8 md:p-12">
                <h1 class="text-3xl sm:text-4xl font-bold text-slate-900 mb-6">How QuizSnap Works</h1>
                
                <p class="text-lg text-slate-700 mb-8 leading-relaxed">
                    QuizSnap is a secure online assessment platform designed for educational institutions. 
                    Here's everything you need to know about taking quizzes on QuizSnap.
                </p>

                <div class="space-y-10">
                    <!-- Getting Started -->
                    <section>
                        <div class="flex items-start gap-4 mb-4">
                            <div class="section-icon-box" style="background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);">
                                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                </svg>
                            </div>
                            <div class="flex-1">
                                <h2 class="text-2xl font-semibold text-slate-900 mb-1">Getting Started</h2>
                                <p class="text-sm text-slate-500">Your first steps to taking a quiz</p>
                            </div>
                        </div>
                        <div class="ml-20 space-y-3 text-slate-700">
                            <p><strong>Receive your token:</strong> Your lecturer or examiner will provide you with a unique quiz token (e.g., KTdie54-3Sx9).</p>
                            <p><strong>Enter the token:</strong> On the homepage, enter the token in the input field and click "Start Quiz".</p>
                            <p><strong>Login to your account:</strong> You can also log in to your student account to see all available quizzes on your dashboard.</p>
                        </div>
                    </section>

                    <!-- Verification Process -->
                    <section>
                        <div class="flex items-start gap-4 mb-4">
                            <div class="section-icon-box" style="background: linear-gradient(135deg, #a855f7 0%, #9333ea 100%);">
                                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                                </svg>
                            </div>
                            <div class="flex-1">
                                <h2 class="text-2xl font-semibold text-slate-900 mb-1">Verification Process</h2>
                                <p class="text-sm text-slate-500">Secure identity verification steps</p>
                            </div>
                        </div>
                        <div class="ml-20 space-y-3 text-slate-700">
                            <p><strong>Index number:</strong> Enter your student index number for verification.</p>
                            <p><strong>Phone verification:</strong> If it's your first time, you'll verify your phone number with an OTP.</p>
                            <p><strong>Pre-quiz photo:</strong> Take a clear photo of your face using your device camera. This helps verify your identity.</p>
                        </div>
                    </section>

                    <!-- Taking the Quiz -->
                    <section>
                        <div class="flex items-start gap-4 mb-4">
                            <div class="section-icon-box" style="background: linear-gradient(135deg, #14b8a6 0%, #0d9488 100%);">
                                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                            </div>
                            <div class="flex-1">
                                <h2 class="text-2xl font-semibold text-slate-900 mb-1">Taking the Quiz</h2>
                                <p class="text-sm text-slate-500">Important guidelines during the quiz</p>
                            </div>
                        </div>
                        <div class="ml-20 space-y-3 text-slate-700">
                            <p><strong>Timer:</strong> Once you start, a countdown timer begins. You must complete the quiz before time runs out.</p>
                            <p><strong>Answer questions:</strong> Questions are displayed one screen at a time. Select your answers carefully.</p>
                            <p><strong>Auto-save:</strong> Your answers are automatically saved as you progress.</p>
                            <p><strong>Stay focused:</strong> Remain on the quiz tab. Switching tabs may be logged as a violation.</p>
                            <p><strong>Desktop only:</strong> QuizSnap is optimized for desktop browsers. Mobile devices are not supported.</p>
                        </div>
                    </section>

                    <!-- Proctoring & Security -->
                    <section>
                        <div class="flex items-start gap-4 mb-4">
                            <div class="section-icon-box" style="background: linear-gradient(135deg, #f97316 0%, #ea580c 100%);">
                                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                </svg>
                            </div>
                            <div class="flex-1">
                                <h2 class="text-2xl font-semibold text-slate-900 mb-1">Proctoring & Security</h2>
                                <p class="text-sm text-slate-500">What is monitored and when your quiz may auto-submit</p>
                            </div>
                        </div>
                        <div class="ml-20 space-y-4 text-slate-700">
                            <p class="text-sm text-slate-500 uppercase tracking-wide font-semibold">What is monitored during a proctored quiz</p>
                            <ul class="list-disc pl-5 space-y-2">
                                <li><strong>Face verification & camera:</strong> A pre-quiz photo (and sometimes post-quiz photo) confirms it is really you. If camera proctoring is enabled for your quiz, your camera must stay on and your face should remain clearly visible and centered.</li>
                                <li><strong>Screen focus & tab changes:</strong> The system can detect when you leave the quiz tab, minimize the window, or resize it in suspicious ways.</li>
                                <li><strong>Multiple faces & environment:</strong> If more than one face is detected for a proctored quiz, or your face repeatedly leaves the frame, these events are logged for your examiner.</li>
                                <li><strong>Clipboard & screenshots:</strong> Attempts to copy/paste quiz content or take screenshots are recorded as serious violations.</li>
                                <li><strong>Devices & network:</strong> For protected quizzes, using a second device or a different network (IP address) for the same attempt can be treated as a violation.</li>
                            </ul>

                            <p class="text-sm text-slate-500 uppercase tracking-wide font-semibold mt-4">When the system may auto-submit your quiz</p>
                            <ul class="list-disc pl-5 space-y-2">
                                <li><strong>Serious violations:</strong> Certain actions (for example: using a phone on camera, clear screenshot attempts, obvious copy/paste of quiz content, multiple faces or multiple devices for the same attempt, or repeated tab switching/blur events) can cause your quiz to be <span class="font-semibold text-red-700">automatically submitted and flagged</span> for review.</li>
                                <li><strong>Too many warnings:</strong> If you repeatedly break proctoring rules (for example, your face is out of frame many times, or there are many warning-level violations), the system may automatically submit your quiz and mark the attempt as risky for the examiner.</li>
                                <li><strong>Time running out:</strong> When the quiz timer reaches zero, QuizSnap will submit your attempt automatically using the last answers we successfully saved.</li>
                            </ul>

                            <p class="text-sm text-slate-500 uppercase tracking-wide font-semibold mt-4">What does <span class="font-semibold text-slate-800 normal-case">not</span> auto-submit your quiz</p>
                            <ul class="list-disc pl-5 space-y-2">
                                <li><strong>Network problems:</strong> If your connection drops, your answers are stored safely in your browser and will sync when you come back online. Network errors alone do <span class="font-semibold">not</span> auto-submit your quiz.</li>
                                <li><strong>Short camera glitches:</strong> Brief camera interruptions are treated as warnings. You will see on-screen messages and should fix the issue quickly, but a single glitch does not immediately submit your quiz.</li>
                            </ul>

                            <p><strong>Fair assessment:</strong> Proctoring is designed to protect honest students. If you stay on the quiz page, keep your camera on (when required), avoid external help, and follow the rules, you can complete your quiz without interruption.</p>
                        </div>
                    </section>

                    <!-- Submitting & Results -->
                    <section>
                        <div class="flex items-start gap-4 mb-4">
                            <div class="section-icon-box" style="background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);">
                                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <div class="flex-1">
                                <h2 class="text-2xl font-semibold text-slate-900 mb-1">Submitting & Results</h2>
                                <p class="text-sm text-slate-500">Complete and view your results</p>
                            </div>
                        </div>
                        <div class="ml-20 space-y-3 text-slate-700">
                            <p><strong>Final photo:</strong> After completing all questions, take a final photo to submit your quiz.</p>
                            <p><strong>Instant results:</strong> Your score is calculated immediately after submission.</p>
                            <p><strong>Review answers:</strong> You can review your answers and see correct answers (if enabled by your lecturer).</p>
                            <p><strong>Results history:</strong> Your scores are saved forever. Detailed question reviews are available for 21 days.</p>
                        </div>
                    </section>

                    <!-- Technical Requirements -->
                    <section>
                        <div class="flex items-start gap-4 mb-4">
                            <div class="section-icon-box" style="background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);">
                                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                </svg>
                            </div>
                            <div class="flex-1">
                                <h2 class="text-2xl font-semibold text-slate-900 mb-1">Technical Requirements</h2>
                                <p class="text-sm text-slate-500">What you need to get started</p>
                            </div>
                        </div>
                        <div class="ml-20 space-y-3 text-slate-700">
                            <p><strong>Device:</strong> Desktop or laptop computer (mobile devices not supported).</p>
                            <p><strong>Browser:</strong> Modern browser with JavaScript enabled (Chrome, Firefox, Safari, Edge).</p>
                            <p><strong>Camera:</strong> Working webcam for face verification photos.</p>
                            <p><strong>Internet:</strong> Stable internet connection throughout the quiz.</p>
                            <p><strong>Environment:</strong> Quiet space with minimal distractions.</p>
                        </div>
                    </section>

                    <!-- Tips for Success -->
                    <section class="bg-blue-50 border border-blue-100 rounded-xl p-6">
                        <h2 class="text-xl font-semibold text-blue-900 mb-4">Tips for Success</h2>
                        <ul class="space-y-2 text-slate-700">
                            <li class="flex items-start gap-2">
                                <svg class="w-5 h-5 text-blue-600 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                                <span>Test your camera and internet connection before starting.</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <svg class="w-5 h-5 text-blue-600 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                                <span>Keep your face visible during photo capture for best results.</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <svg class="w-5 h-5 text-blue-600 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                                <span>Don't switch tabs during the quiz - it may be flagged as a violation.</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <svg class="w-5 h-5 text-blue-600 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                                <span>Read each question carefully and manage your time wisely.</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <svg class="w-5 h-5 text-blue-600 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                                <span>Ensure you're in a quiet environment with good lighting.</span>
                            </li>
                        </ul>
                    </section>

                    <!-- Need Help? -->
                    <section class="bg-slate-50 border border-slate-200 rounded-xl p-6">
                        <h2 class="text-xl font-semibold text-slate-900 mb-3">Need Help?</h2>
                        <p class="text-slate-700 mb-3">
                            If you encounter any issues during your quiz, contact your lecturer or examiner immediately.
                        </p>
                        <p class="text-sm text-slate-600">
                            Common issues: camera not working, token invalid, connection problems, or technical errors.
                        </p>
                    </section>
                </div>

                <div class="mt-10 pt-8 border-t border-slate-200 text-center">
                    <a href="{{ route('student.landing') }}" class="inline-flex items-center px-6 py-3 bg-blue-600 text-white text-sm font-semibold rounded-lg hover:bg-blue-700 transition-colors">
                        Go to Homepage
                        <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path>
                        </svg>
                    </a>
                </div>
            </div>
        </div>
    </main>

    <footer class="shrink-0 border-t border-slate-200 bg-white py-8">
        <div class="mx-auto max-w-7xl px-6 text-center">
            <p class="text-sm text-slate-500">
                &copy; {{ date('Y') }} QuizSnap. All rights reserved -
                <a href="https://www.ausweblabs.com" target="_blank" rel="noopener noreferrer" class="no-underline hover:no-underline text-slate-500 hover:text-slate-600">ausweblabs</a>
            </p>
        </div>
    </footer>
</div>
@endsection
