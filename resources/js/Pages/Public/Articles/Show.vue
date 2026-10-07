<script setup lang="ts">
import { ref, onMounted, onUnmounted, computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import AppLayout from '../../../Layouts/AppLayout.vue';
import SeoHead from '../../../Components/SeoHead.vue';
import {
    Calendar,
    Eye,
    Clock,
    User,
    BookOpen,
    Share2,
    Check,
    ChevronLeft,
    Tag,
    Layers,
    ArrowRight
} from 'lucide-vue-next';

interface Category {
    id: number;
    name: string;
    slug: string;
}

interface Issue {
    id: number;
    issue_number: number;
    title: string;
    slug: string;
    cover_image?: string | null;
}

interface Author {
    id: number;
    name: string;
}

interface ArtistProfile {
    id: number;
    full_name: string;
    slug: string;
    art_category: string;
    avatar_url?: string | null;
    biography?: string | null;
}

interface Article {
    id: number;
    title: string;
    slug: string;
    excerpt?: string | null;
    content: string;
    featured_image?: string | null;
    views_count: number;
    published_at?: string | null;
    category?: Category | null;
    issue?: Issue | null;
    author?: Author | null;
    artist_profile?: ArtistProfile | null;
}

const props = defineProps<{
    article: Article;
    relatedArticles: Article[];
}>();

// Reading Progress Bar State
const readingProgress = ref(0);

const updateReadingProgress = () => {
    const el = document.documentElement;
    const scrollTop = el.scrollTop || document.body.scrollTop;
    const scrollHeight = el.scrollHeight - el.clientHeight;
    if (scrollHeight > 0) {
        readingProgress.value = Math.min(100, Math.max(0, (scrollTop / scrollHeight) * 100));
    }
};

onMounted(() => {
    window.addEventListener('scroll', updateReadingProgress, { passive: true });
});

onUnmounted(() => {
    window.removeEventListener('scroll', updateReadingProgress);
});

// Calculate Estimated Reading Time (approx. 200 words per minute in Arabic)
const estimatedReadingTime = computed(() => {
    const wordCount = (props.article.content || '').trim().split(/\s+/).length;
    const minutes = Math.max(1, Math.ceil(wordCount / 200));
    return `${minutes} دقائق قراءة`;
});

// Social Share State
const isCopied = ref(false);

const shareArticle = (platform: 'twitter' | 'facebook' | 'whatsapp' | 'copy') => {
    const url = window.location.href;
    const title = props.article.title;

    if (platform === 'twitter') {
        window.open(`https://twitter.com/intent/tweet?text=${encodeURIComponent(title)}&url=${encodeURIComponent(url)}`, '_blank');
    } else if (platform === 'facebook') {
        window.open(`https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(url)}`, '_blank');
    } else if (platform === 'whatsapp') {
        window.open(`https://api.whatsapp.com/send?text=${encodeURIComponent(title + ' ' + url)}`, '_blank');
    } else if (platform === 'copy') {
        navigator.clipboard.writeText(url).then(() => {
            isCopied.value = true;
            setTimeout(() => {
                isCopied.value = false;
            }, 2500);
        });
    }
};

const formatDate = (dateStr?: string | null) => {
    if (!dateStr) return '';
    try {
        const d = new Date(dateStr);
        return d.toLocaleDateString('ar-LY', { year: 'numeric', month: 'long', day: 'numeric' });
    } catch {
        return dateStr;
    }
};
</script>

<template>
    <AppLayout>
        <SeoHead
            :title="article.title"
            :description="article.excerpt || `${article.title} - مقال ودراسة نقدية منشورة في مجلة الفنون والسينما.`"
            :image="article.featured_image ? (article.featured_image.startsWith('http') ? article.featured_image : `/storage/${article.featured_image}`) : null"
            type="article"
            :published-at="article.published_at"
            :author-name="article.author?.name"
            :category-name="article.category?.name"
        />

        <!-- Sticky Reading Progress Bar -->
        <div class="fixed top-20 right-0 left-0 h-1 bg-neutral-900 z-30">
            <div
                class="h-full bg-gradient-to-l from-amber-400 to-amber-600 transition-all duration-100 ease-out"
                :style="{ width: `${readingProgress}%` }"
            ></div>
        </div>

        <article class="py-10 bg-neutral-950 min-h-screen">
            <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 space-y-10">
                
                <!-- Breadcrumbs & Category Badge -->
                <div class="flex flex-wrap items-center justify-between gap-4">
                    <nav class="flex items-center gap-2 text-xs font-heading text-neutral-400">
                        <Link href="/" class="hover:text-amber-400 transition-colors">الرئيسية</Link>
                        <span>/</span>
                        <Link v-if="article.issue" :href="`/issues/${article.issue.slug}`" class="hover:text-amber-400 transition-colors">
                            العدد #{{ article.issue.issue_number }}
                        </Link>
                        <span>/</span>
                        <span class="text-amber-500 font-bold truncate max-w-[200px]">{{ article.category?.name || 'مقال' }}</span>
                    </nav>

                    <span
                        v-if="article.category"
                        class="px-3 py-1 rounded-full bg-amber-500/10 border border-amber-500/20 text-amber-400 font-heading font-semibold text-xs"
                    >
                        {{ article.category.name }}
                    </span>
                </div>

                <!-- Article Header Title & Metadata -->
                <header class="space-y-6 text-right">
                    <h1 class="text-3xl sm:text-4xl md:text-5xl font-heading font-black text-white leading-tight">
                        {{ article.title }}
                    </h1>

                    <p v-if="article.excerpt" class="text-lg sm:text-xl text-neutral-300 font-light leading-relaxed border-r-4 border-amber-500 pr-4 py-1">
                        {{ article.excerpt }}
                    </p>

                    <!-- Author & Editorial Info Strip -->
                    <div class="pt-4 flex flex-wrap items-center justify-between gap-4 border-y border-neutral-800/80 py-4 text-xs sm:text-sm text-neutral-400">
                        <div class="flex items-center gap-6 flex-wrap">
                            <span v-if="article.author" class="flex items-center gap-2 text-neutral-200 font-medium">
                                <User class="w-4 h-4 text-amber-500" />
                                <span>بقلم: {{ article.author.name }}</span>
                            </span>

                            <span class="flex items-center gap-1.5">
                                <Calendar class="w-4 h-4 text-neutral-400" />
                                {{ formatDate(article.published_at) }}
                            </span>

                            <span class="flex items-center gap-1.5">
                                <Clock class="w-4 h-4 text-neutral-400" />
                                {{ estimatedReadingTime }}
                            </span>

                            <span class="flex items-center gap-1.5">
                                <Eye class="w-4 h-4 text-neutral-400" />
                                {{ article.views_count }} قراءة
                            </span>
                        </div>

                        <!-- Social Share Action Buttons -->
                        <div class="flex items-center gap-2">
                            <button
                                type="button"
                                @click="shareArticle('twitter')"
                                class="p-2 rounded-lg bg-neutral-900 border border-neutral-800 text-neutral-300 hover:text-white hover:border-neutral-700 transition-colors"
                                title="مشاركة عبر X"
                            >
                                <span class="font-bold text-xs">X</span>
                            </button>
                            <button
                                type="button"
                                @click="shareArticle('facebook')"
                                class="p-2 rounded-lg bg-neutral-900 border border-neutral-800 text-neutral-300 hover:text-white hover:border-neutral-700 transition-colors"
                                title="مشاركة عبر فيسبوك"
                            >
                                <span class="font-bold text-xs">f</span>
                            </button>
                            <button
                                type="button"
                                @click="shareArticle('whatsapp')"
                                class="p-2 rounded-lg bg-neutral-900 border border-neutral-800 text-neutral-300 hover:text-emerald-400 hover:border-emerald-700 transition-colors"
                                title="مشاركة عبر واتساب"
                            >
                                <span class="font-bold text-xs">WA</span>
                            </button>
                            <button
                                type="button"
                                @click="shareArticle('copy')"
                                class="flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg bg-neutral-900 border border-neutral-800 text-neutral-300 hover:text-amber-400 hover:border-neutral-700 text-xs transition-colors"
                                title="نسخ رابط المقال"
                            >
                                <Check v-if="isCopied" class="w-3.5 h-3.5 text-emerald-400" />
                                <Share2 v-else class="w-3.5 h-3.5" />
                                <span>{{ isCopied ? 'تم النسخ!' : 'نسخ' }}</span>
                            </button>
                        </div>
                    </div>
                </header>

                <!-- Featured Image -->
                <div v-if="article.featured_image" class="rounded-3xl overflow-hidden bg-neutral-900 border border-neutral-800 shadow-xl aspect-[16/9] relative">
                    <img
                        :src="article.featured_image.startsWith('http') ? article.featured_image : `/storage/${article.featured_image}`"
                        :alt="article.title"
                        class="w-full h-full object-cover"
                    />
                </div>

                <!-- Main Arabic Editorial Body -->
                <div class="prose-editorial text-neutral-200">
                    <div v-if="article.content.includes('<p>') || article.content.includes('<br')" v-html="article.content"></div>
                    <div v-else class="space-y-6">
                        <p v-for="(paragraph, idx) in article.content.split('\n\n')" :key="idx">
                            {{ paragraph }}
                        </p>
                    </div>
                </div>

                <!-- Linked Issue Banner -->
                <div v-if="article.issue" class="rounded-2xl p-6 bg-neutral-900/80 border border-neutral-800 flex items-center justify-between gap-4">
                    <div class="space-y-1">
                        <span class="text-xs text-amber-500 font-heading">نُشر هذا المقال ضمن</span>
                        <h4 class="font-heading font-bold text-white text-base">
                            {{ article.issue.title }} (العدد #{{ article.issue.issue_number }})
                        </h4>
                    </div>

                    <Link
                        :href="`/issues/${article.issue.slug}`"
                        class="px-4 py-2.5 rounded-xl bg-amber-500 hover:bg-amber-400 text-neutral-950 font-heading font-bold text-xs transition-colors shrink-0"
                    >
                        تصفح العدد بالكامل
                    </Link>
                </div>

                <!-- Linked Artist Profile Card -->
                <div v-if="article.artist_profile" class="rounded-2xl p-6 bg-neutral-900/60 border border-neutral-800 flex flex-col sm:flex-row items-center gap-6 text-right">
                    <div class="w-20 h-20 rounded-full overflow-hidden bg-neutral-950 border-2 border-amber-500/40 shrink-0">
                        <img
                            v-if="article.artist_profile.avatar_url"
                            :src="article.artist_profile.avatar_url.startsWith('http') ? article.artist_profile.avatar_url : `/storage/${article.artist_profile.avatar_url}`"
                            :alt="article.artist_profile.full_name"
                            class="w-full h-full object-cover"
                        />
                        <div v-else class="w-full h-full flex items-center justify-center font-heading font-bold text-amber-400 text-lg">
                            {{ article.artist_profile.full_name.charAt(0) }}
                        </div>
                    </div>

                    <div class="space-y-2 flex-1">
                        <div class="flex items-center gap-2">
                            <h4 class="font-heading font-bold text-white text-base">
                                {{ article.artist_profile.full_name }}
                            </h4>
                            <span class="text-xs px-2 py-0.5 rounded bg-neutral-800 text-amber-400 font-heading">
                                {{ article.artist_profile.art_category }}
                            </span>
                        </div>
                        <p v-if="article.artist_profile.biography" class="text-xs text-neutral-400 line-clamp-2">
                            {{ article.artist_profile.biography }}
                        </p>
                    </div>

                    <Link
                        :href="`/artists/${article.artist_profile.slug}`"
                        class="px-4 py-2 rounded-xl bg-neutral-800 hover:bg-neutral-700 text-white font-heading font-semibold text-xs shrink-0"
                    >
                        الملف الكامل للرائد
                    </Link>
                </div>

                <!-- Related Articles Section -->
                <section v-if="relatedArticles && relatedArticles.length" class="pt-12 border-t border-neutral-800/80 space-y-6">
                    <div class="flex items-center justify-between">
                        <h3 class="text-xl font-heading font-bold text-white">
                            دراسات ومقالات ذات صلة
                        </h3>
                        <span class="text-xs text-neutral-400">في ذات التخصص الثقافي</span>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                        <Link
                            v-for="rel in relatedArticles"
                            :key="rel.id"
                            :href="`/articles/${rel.slug}`"
                            class="group p-5 rounded-2xl bg-neutral-900/70 border border-neutral-800 hover:border-amber-500/40 hover:bg-neutral-900 transition-all flex flex-col justify-between space-y-3"
                        >
                            <div class="space-y-2">
                                <span v-if="rel.category" class="text-xs text-amber-500 font-heading font-semibold">
                                    {{ rel.category.name }}
                                </span>
                                <h4 class="font-heading font-bold text-base text-white group-hover:text-amber-400 transition-colors line-clamp-2">
                                    {{ rel.title }}
                                </h4>
                                <p v-if="rel.excerpt" class="text-xs text-neutral-400 line-clamp-2">
                                    {{ rel.excerpt }}
                                </p>
                            </div>

                            <div class="pt-3 border-t border-neutral-800/60 flex items-center justify-between text-xs text-neutral-500">
                                <span v-if="rel.author">{{ rel.author.name }}</span>
                                <span class="flex items-center gap-1 text-amber-500 font-medium">
                                    قراءة
                                    <ChevronLeft class="w-3.5 h-3.5" />
                                </span>
                            </div>
                        </Link>
                    </div>
                </section>

            </div>
        </article>
    </AppLayout>
</template>
