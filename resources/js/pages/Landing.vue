<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Accordion, AccordionContent, AccordionItem, AccordionTrigger } from '@/components/ui/accordion';
import { 
    MessageSquare, 
    Zap, 
    Shield, 
    Palette, 
    Moon, 
    Smartphone,
    Code2,
    Globe,
    ArrowRight,
    CheckCircle,
    Star,
    Github,
    Twitter,
    ChevronUp
} from 'lucide-vue-next';

const features = [
    {
        icon: Zap,
        title: 'Instant Start',
        description: 'Zero setup required - start coding immediately with everything pre-configured'
    },
    {
        icon: Code2,
        title: 'Live Preview',
        description: 'See your changes instantly with hot module replacement and real-time updates'
    },
    {
        icon: MessageSquare,
        title: 'AI-Ready Conversations',
        description: 'Claude AI integration ready to assist with intelligent, context-aware responses'
    },
    {
        icon: Globe,
        title: 'Always Available',
        description: 'Cloud-based environment accessible from anywhere, anytime, on any device'
    },
    {
        icon: Shield,
        title: 'Production Ready',
        description: 'Built-in authentication, security, and scalability from day one'
    },
    {
        icon: Palette,
        title: 'Beautiful by Default',
        description: 'Pre-styled components with Tailwind CSS 4 and shadcn/ui ready to use'
    },
    {
        icon: Moon,
        title: 'Adaptive Interface',
        description: 'Automatic theme switching with system preference detection'
    },
    {
        icon: Smartphone,
        title: 'Works Everywhere',
        description: 'PWA capabilities for seamless desktop and mobile experience'
    }
];

const benefits = [
    {
        title: 'Start Instantly',
        description: 'No installation, no configuration - just open and start building immediately',
        points: ['Pre-configured environment', 'Instant hot reload', 'Live preview window']
    },
    {
        title: 'Code with Confidence',
        description: 'Everything you need is ready - from AI assistance to instant feedback',
        points: ['Real-time preview', 'AI-powered suggestions', 'Integrated debugging']
    },
    {
        title: 'Ship Faster',
        description: 'From idea to production with zero friction and maximum velocity',
        points: ['One-click deployment', 'Built-in CI/CD', 'Production-ready code']
    }
];

const testimonials = [
    {
        name: 'Sarah Chen',
        role: 'CTO at TechStart',
        content: 'LaraChat transformed how our team communicates. The AI integration is a game-changer.',
        avatar: 'SC',
        rating: 5
    },
    {
        name: 'Michael Rodriguez',
        role: 'Full Stack Developer',
        content: 'Best Laravel + Vue starter I\'ve used. Clean code, great documentation, and amazing performance.',
        avatar: 'MR',
        rating: 5
    },
    {
        name: 'Emily Watson',
        role: 'Product Manager',
        content: 'Our users love the interface. The dark mode and PWA support make it feel like a native app.',
        avatar: 'EW',
        rating: 5
    }
];

const faqs = [
    {
        question: 'What is LaraChat?',
        answer: 'LaraChat is a modern chat application built with Laravel 12 and Vue.js 3, featuring AI-powered conversations through Claude integration, real-time messaging, and a beautiful user interface.'
    },
    {
        question: 'How does the AI integration work?',
        answer: 'LaraChat seamlessly integrates with Claude AI to provide intelligent conversation assistance, smart suggestions, and context-aware responses. The AI enhances your messaging experience without compromising privacy.'
    },
    {
        question: 'Is LaraChat suitable for production use?',
        answer: 'Absolutely! LaraChat is built on production-ready technologies including Laravel 12, Vue 3, and Inertia.js. It includes authentication, security features, and is optimized for performance and scalability.'
    },
    {
        question: 'Can I customize the appearance?',
        answer: 'Yes! LaraChat comes with built-in theme support including dark and light modes. The UI is built with Tailwind CSS and shadcn/ui components, making it easy to customize colors, fonts, and overall styling.'
    },
    {
        question: 'Does it work on mobile devices?',
        answer: 'LaraChat is a Progressive Web App (PWA) with full mobile support. It works seamlessly on all devices and can be installed as a native-like app on smartphones and tablets.'
    },
    {
        question: 'What are the system requirements?',
        answer: 'LaraChat requires PHP 8.2+, Node.js 18+, and a database (SQLite by default, but supports MySQL, PostgreSQL). It runs on any modern web server and is optimized for cloud deployment.'
    }
];

const showBackToTop = ref(false);

const scrollToTop = () => {
    window.scrollTo({ top: 0, behavior: 'smooth' });
};

const handleScroll = () => {
    showBackToTop.value = window.scrollY > 500;
};

onMounted(() => {
    window.addEventListener('scroll', handleScroll);
    
    const observer = new IntersectionObserver(
        (entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('animate-in');
                }
            });
        },
        { threshold: 0.1 }
    );
    
    document.querySelectorAll('.observe-animate').forEach((el) => {
        observer.observe(el);
    });
});

onUnmounted(() => {
    window.removeEventListener('scroll', handleScroll);
});
</script>

<template>
    <Head title="LaraChat - AI-Powered Chat Application" />
    
    <div class="min-h-screen bg-gradient-to-b from-background to-muted/20">
        <!-- Hero Section -->
        <section class="relative overflow-hidden px-6 py-24 sm:py-32 lg:px-8">
            <div class="absolute inset-0 -z-10 bg-[radial-gradient(45rem_50rem_at_top,theme(colors.indigo.100),white)] dark:bg-[radial-gradient(45rem_50rem_at_top,theme(colors.indigo.900/20),transparent)]" />
            <div class="mx-auto max-w-7xl">
                <div class="observe-animate mx-auto max-w-3xl text-center opacity-0 translate-y-10 transition-all duration-700 ease-out">
                    <h1 class="text-4xl font-bold tracking-tight text-foreground sm:text-6xl lg:text-7xl">
                        Always Ready to
                        <span class="bg-gradient-to-r from-indigo-600 to-purple-600 bg-clip-text text-transparent">
                            Start Coding
                        </span>
                    </h1>
                    <p class="mt-6 text-lg leading-8 text-muted-foreground sm:text-xl">
                        Jump straight into conversations and coding with instant preview. No setup, no waiting - 
                        LaraChat is always ready when inspiration strikes, with real-time feedback at your fingertips.
                    </p>
                    <div class="mt-10 flex flex-col sm:flex-row items-center justify-center gap-4">
                        <Link href="/register">
                            <Button size="lg" class="gap-2 px-8">
                                Get Started Free
                                <ArrowRight class="h-4 w-4" />
                            </Button>
                        </Link>
                        <Link href="/login">
                            <Button variant="outline" size="lg" class="px-8">
                                Try Demo
                            </Button>
                        </Link>
                    </div>
                </div>
            </div>
        </section>

        <!-- Hero Image Section -->
        <section class="py-12">
            <div class="mx-auto max-w-7xl px-6 lg:px-8">
                <div class="observe-animate mx-auto max-w-6xl opacity-0 translate-y-10 transition-all duration-700 ease-out">
                    <img 
                        src="https://www.dropbox.com/scl/fi/u93zkurmzxkdxulyoa3da/Monosnap-Laravel-2025-08-16-12-41-58.png?rlkey=a0p6anklyr2adso07d4iyhdtk&raw=1"
                        alt="LaraChat Dashboard"
                        class="w-full rounded-2xl shadow-2xl ring-1 ring-gray-900/10 dark:ring-gray-100/10"
                    />
                </div>
            </div>
        </section>

        <!-- Features Section -->
        <section class="py-24 sm:py-32">
            <div class="mx-auto max-w-7xl px-6 lg:px-8">
                <div class="observe-animate mx-auto max-w-2xl text-center opacity-0 translate-y-10 transition-all duration-700 ease-out">
                    <h2 class="text-3xl font-bold tracking-tight text-foreground sm:text-4xl">
                        Ready When You Are
                    </h2>
                    <p class="mt-4 text-lg text-muted-foreground">
                        Everything pre-configured and instantly available - just start coding
                    </p>
                </div>
                <div class="mx-auto mt-16 grid max-w-7xl grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">
                    <Card 
                        v-for="(feature, index) in features" 
                        :key="index"
                        class="observe-animate group relative overflow-hidden opacity-0 translate-y-10 transition-all duration-700 ease-out hover:shadow-lg hover:-translate-y-1"
                        :style="`animation-delay: ${index * 100}ms`"
                    >
                        <CardHeader>
                            <div class="mb-2 inline-flex h-12 w-12 items-center justify-center rounded-lg bg-primary/10 text-primary transition-colors group-hover:bg-primary group-hover:text-primary-foreground">
                                <component :is="feature.icon" class="h-6 w-6" />
                            </div>
                            <CardTitle class="text-lg">{{ feature.title }}</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <CardDescription>{{ feature.description }}</CardDescription>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </section>

        <!-- Benefits Section -->
        <section class="bg-muted/50 py-24 sm:py-32">
            <div class="mx-auto max-w-7xl px-6 lg:px-8">
                <div class="observe-animate mx-auto max-w-2xl text-center opacity-0 translate-y-10 transition-all duration-700 ease-out">
                    <h2 class="text-3xl font-bold tracking-tight text-foreground sm:text-4xl">
                        Zero to Production in Minutes
                    </h2>
                    <p class="mt-4 text-lg text-muted-foreground">
                        Skip the setup, jump straight to building with instant preview
                    </p>
                </div>
                <div class="mx-auto mt-16 grid max-w-6xl grid-cols-1 gap-8 lg:grid-cols-3">
                    <div 
                        v-for="(benefit, index) in benefits" 
                        :key="index"
                        class="observe-animate opacity-0 translate-y-10 transition-all duration-700 ease-out"
                        :style="`animation-delay: ${index * 150}ms`"
                    >
                        <div class="rounded-2xl bg-background p-8 shadow-sm ring-1 ring-border">
                            <h3 class="text-xl font-semibold text-foreground">{{ benefit.title }}</h3>
                            <p class="mt-4 text-muted-foreground">{{ benefit.description }}</p>
                            <ul class="mt-6 space-y-3">
                                <li v-for="point in benefit.points" :key="point" class="flex items-start gap-2">
                                    <CheckCircle class="h-5 w-5 shrink-0 text-green-500" />
                                    <span class="text-sm text-muted-foreground">{{ point }}</span>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Testimonials Section -->
        <section class="py-24 sm:py-32">
            <div class="mx-auto max-w-7xl px-6 lg:px-8">
                <div class="observe-animate mx-auto max-w-2xl text-center opacity-0 translate-y-10 transition-all duration-700 ease-out">
                    <h2 class="text-3xl font-bold tracking-tight text-foreground sm:text-4xl">
                        Loved by Developers & Teams
                    </h2>
                    <p class="mt-4 text-lg text-muted-foreground">
                        See what our users are saying about LaraChat
                    </p>
                </div>
                <div class="mx-auto mt-16 grid max-w-6xl grid-cols-1 gap-8 lg:grid-cols-3">
                    <Card 
                        v-for="(testimonial, index) in testimonials" 
                        :key="index"
                        class="observe-animate opacity-0 translate-y-10 transition-all duration-700 ease-out"
                        :style="`animation-delay: ${index * 100}ms`"
                    >
                        <CardHeader>
                            <div class="flex items-center gap-4">
                                <div class="flex h-12 w-12 items-center justify-center rounded-full bg-primary/10 text-sm font-semibold text-primary">
                                    {{ testimonial.avatar }}
                                </div>
                                <div>
                                    <CardTitle class="text-base">{{ testimonial.name }}</CardTitle>
                                    <CardDescription>{{ testimonial.role }}</CardDescription>
                                </div>
                            </div>
                            <div class="mt-2 flex gap-1">
                                <Star v-for="i in testimonial.rating" :key="i" class="h-4 w-4 fill-yellow-400 text-yellow-400" />
                            </div>
                        </CardHeader>
                        <CardContent>
                            <p class="text-muted-foreground">"{{ testimonial.content }}"</p>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </section>

        <!-- FAQ Section -->
        <section class="bg-muted/50 py-24 sm:py-32">
            <div class="mx-auto max-w-4xl px-6 lg:px-8">
                <div class="observe-animate mx-auto max-w-2xl text-center opacity-0 translate-y-10 transition-all duration-700 ease-out">
                    <h2 class="text-3xl font-bold tracking-tight text-foreground sm:text-4xl">
                        Frequently Asked Questions
                    </h2>
                    <p class="mt-4 text-lg text-muted-foreground">
                        Everything you need to know about LaraChat
                    </p>
                </div>
                <div class="observe-animate mx-auto mt-16 opacity-0 translate-y-10 transition-all duration-700 ease-out">
                    <Accordion type="single" collapsible class="w-full">
                        <AccordionItem v-for="(faq, index) in faqs" :key="index" :value="`item-${index}`">
                            <AccordionTrigger class="text-left">{{ faq.question }}</AccordionTrigger>
                            <AccordionContent class="text-muted-foreground">
                                {{ faq.answer }}
                            </AccordionContent>
                        </AccordionItem>
                    </Accordion>
                </div>
            </div>
        </section>

        <!-- CTA Section -->
        <section class="py-24 sm:py-32">
            <div class="mx-auto max-w-7xl px-6 lg:px-8">
                <div class="observe-animate relative isolate overflow-hidden rounded-3xl bg-gradient-to-br from-indigo-600 to-purple-600 px-6 py-24 text-center shadow-2xl sm:px-16 opacity-0 scale-95 transition-all duration-700 ease-out">
                    <h2 class="mx-auto max-w-2xl text-3xl font-bold tracking-tight text-white sm:text-4xl">
                        Start Coding in Seconds, Not Hours
                    </h2>
                    <p class="mx-auto mt-6 max-w-xl text-lg leading-8 text-indigo-100">
                        Everything is ready - your development environment, AI assistant, and live preview await
                    </p>
                    <div class="mt-10 flex items-center justify-center gap-x-6">
                        <Link href="/register">
                            <Button size="lg" variant="secondary" class="gap-2">
                                Start Free Trial
                                <ArrowRight class="h-4 w-4" />
                            </Button>
                        </Link>
                        <Link href="https://github.com/CodingCab/LaraChat" class="text-sm font-semibold leading-6 text-white hover:text-indigo-100">
                            View on GitHub <span aria-hidden="true">→</span>
                        </Link>
                    </div>
                </div>
            </div>
        </section>

        <!-- Footer -->
        <footer class="border-t bg-background">
            <div class="mx-auto max-w-7xl px-6 py-12 lg:px-8">
                <div class="grid grid-cols-2 gap-8 md:grid-cols-4">
                    <div>
                        <h3 class="text-sm font-semibold text-foreground">Product</h3>
                        <ul class="mt-4 space-y-2">
                            <li><Link href="/features" class="text-sm text-muted-foreground hover:text-foreground">Features</Link></li>
                            <li><Link href="/pricing" class="text-sm text-muted-foreground hover:text-foreground">Pricing</Link></li>
                            <li><Link href="/roadmap" class="text-sm text-muted-foreground hover:text-foreground">Roadmap</Link></li>
                        </ul>
                    </div>
                    <div>
                        <h3 class="text-sm font-semibold text-foreground">Resources</h3>
                        <ul class="mt-4 space-y-2">
                            <li><Link href="/docs" class="text-sm text-muted-foreground hover:text-foreground">Documentation</Link></li>
                            <li><Link href="/api" class="text-sm text-muted-foreground hover:text-foreground">API Reference</Link></li>
                            <li><Link href="/blog" class="text-sm text-muted-foreground hover:text-foreground">Blog</Link></li>
                        </ul>
                    </div>
                    <div>
                        <h3 class="text-sm font-semibold text-foreground">Company</h3>
                        <ul class="mt-4 space-y-2">
                            <li><Link href="/about" class="text-sm text-muted-foreground hover:text-foreground">About</Link></li>
                            <li><Link href="/contact" class="text-sm text-muted-foreground hover:text-foreground">Contact</Link></li>
                            <li><Link href="/privacy" class="text-sm text-muted-foreground hover:text-foreground">Privacy</Link></li>
                        </ul>
                    </div>
                    <div>
                        <h3 class="text-sm font-semibold text-foreground">Connect</h3>
                        <div class="mt-4 flex space-x-4">
                            <a href="https://github.com/CodingCab/LaraChat" class="text-muted-foreground hover:text-foreground">
                                <Github class="h-5 w-5" />
                            </a>
                            <a href="https://twitter.com/larachat" class="text-muted-foreground hover:text-foreground">
                                <Twitter class="h-5 w-5" />
                            </a>
                        </div>
                    </div>
                </div>
                <div class="mt-8 border-t pt-8">
                    <p class="text-center text-sm text-muted-foreground">
                        © 2024 LaraChat. All rights reserved. Built with ❤️ using Laravel + Vue.js
                    </p>
                </div>
            </div>
        </footer>

        <!-- Back to Top Button -->
        <Transition
            enter-active-class="transition-opacity duration-300"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition-opacity duration-300"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <button
                v-if="showBackToTop"
                @click="scrollToTop"
                class="fixed bottom-8 right-8 z-50 flex h-12 w-12 items-center justify-center rounded-full bg-primary text-primary-foreground shadow-lg transition-transform hover:scale-110"
            >
                <ChevronUp class="h-5 w-5" />
            </button>
        </Transition>
    </div>
</template>

<style scoped>
@keyframes animate-in {
    from {
        opacity: 0;
        transform: translateY(2rem);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.animate-in {
    animation: animate-in 0.7s ease-out forwards;
}

.observe-animate {
    will-change: transform, opacity;
}
</style>