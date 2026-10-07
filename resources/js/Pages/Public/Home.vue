<script setup lang="ts">
import { ref } from 'vue';
import { Link } from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';
import SeoHead from '../../Components/SeoHead.vue';
import {
    BookOpen,
    Film,
    Sparkles,
    Calendar,
    Eye,
    ChevronLeft,
    Clock,
    User,
    ArrowLeft,
    Layers,
    Palette,
    Music,
    Users,
    Download,
    ExternalLink
} from 'lucide-vue-next';

interface Issue {
    id: number;
    issue_number: number;
    title: string;
    slug: string;
    cover_image?: string | null;
    published_at?: string | null;
    pages_count: number;
    articles_count?: number;
    pdf_file_path?: string | null;
}

interface Article {
    id: number;
    title: string;
    slug: string;
    excerpt?: string | null;
    featured_image?: string | null;
    views_count: number;
    published_at?: string | null;
    category?: { id: number; name: string; slug: string } | null;
    author?: { id: number; name: string } | null;
    issue?: { id: number; title: string; issue_number: number; slug: string } | null;
}

interface Artist {
    id: number;
    full_name: string;
    slug: string;
    art_category: string;
    biography?: string | null;
    avatar_url?: string | null;
    birth_date?: string | null;
    death_date?: string | null;
    is_featured: boolean;
    articles_count?: number;
}

const props = defineProps<{
    latestIssue: Issue | null;
    featuredArticles: Article[];
    categorizedArticles: {
        cinema: Article[];
        theatre: Article[];
        fine_arts: Article[];
        music: Article[];
    };
    spotlightArtists: Artist[];
}>();

// Active Cultural Track Tab
const activeTrack = ref<'cinema' | 'theatre' | 'fine_arts' | 'music'>('cinema');

const trackTabs = [
    { key: 'cinema', label: 'السينما والشاشة', icon: Film, count: props.categorizedArticles.cinema?.length || 0 },
    { key: 'theatre', label: 'المسرح وفنون الأداء', icon: Sparkles, count: props.categorizedArticles.theatre?.length || 0 },
    { key: 'fine_arts', label: 'الفنون التشكيلية', icon: Palette, count: props.categorizedArticles.fine_arts?.length || 0 },
    { key: 'music', label: 'الموسيقى والمألوف', icon: Music, count: props.categorizedArticles.music?.length || 0 },
] as const;

const formatDate = (dateStr?: string | null) => {
    if (!dateStr) return '';
    try {
        const d = new Date(dateStr);
        return d.toLocaleDateString('ar-LY', { year: 'numeric', month: 'long', day: 'numeric' });
    } catch {
        return dateStr;
    }
};

const getCategoryLabel = (cat: string) => {
    switch (cat) {
        case 'cinema': return 'شؤون سينمائية';
        case 'theatre': return 'دراسات مسرحية';
        case 'fine_arts':
        case 'fine-arts': return 'فنون بصرية';
        case 'music': return 'موسيقى وتراث';
        default: return cat;
    }
};
</script>

<template>
    <AppLayout>
        <SeoHead
            title="الرئيسية | مجلة الفنون والسينما"
            description="البوابة الرقمية الرسمية لمجلة الفنون والسينما الصادرة عن الهيئة العامة للسينما والمسرح والفنون في دولة ليبيا. توثيق، نقد، وأرشيف رقمي شامل."
            type="website"
        />

        <!-- Hero Section: Latest Issue 3D Feature & State Cultural Identity -->
        <section class="relative pt-10 pb-20 overflow-hidden border-b border-neutral-800/60 bg-gradient-to-b from-neutral-900/60 via-neutral-950 to-neutral-950">
            <!-- Ambient Backdrop Glows -->
            <div class="absolute top-0 right-1/4 w-[500px] h-[500px] bg-amber-600/10 rounded-full blur-[140px] pointer-events-none"></div>
            <div class="absolute top-20 left-10 w-[400px] h-[400px] bg-red-800/10 rounded-full blur-[160px] pointer-events-none"></div>

            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-12 lg:gap-8 items-center">
                    
                    <!-- Right Col: Cultural Magazine Mission & Headline (RTL First) -->
                    <div class="lg:col-span-7 space-y-6 text-right">
                        <!-- State Authority Pill -->
                        <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-amber-500/10 border border-amber-500/20 text-amber-400 text-xs font-semibold">
                            <span class="w-2 h-2 rounded-full bg-amber-500 animate-pulse"></span>
                            <span>الهيئة العامة للسينما والمسرح والفنون - دولة ليبيا</span>
                        </div>

                        <h1 class="text-3xl sm:text-4xl md:text-5xl lg:text-6xl font-heading font-black text-white leading-tight">
                            مجلة <span class="text-transparent bg-clip-text bg-gradient-to-r from-amber-400 via-amber-300 to-amber-600">الفنون والسينما</span>
                            <span class="block text-xl sm:text-2xl lg:text-3xl font-medium text-neutral-400 mt-3">
                                دورية ثقافية محكّمة توثق الذاكرة الإبداعية الوطنية
                            </span>
                        </h1>

                        <p class="text-neutral-300 text-base sm:text-lg leading-relaxed max-w-2xl font-light">
                            منبر رسمي متخصص في النقد السينمائي، الدراسات الدرامية والمسرحية، وتوثيق سير رواد الفنون التشكيلية والموسيقى في ليبيا، مدعوماً بمكتبة رقمية ومستعرض تفاعلي متقدم.
                        </p>

                        <!-- Action Buttons -->
                        <div class="pt-4 flex flex-wrap items-center gap-4">
                            <Link
                                v-if="latestIssue"
                                :href="`/issues/${latestIssue.slug}`"
                                class="inline-flex items-center gap-2.5 px-6 py-3.5 rounded-xl bg-gradient-to-r from-amber-500 to-amber-600 text-neutral-950 font-heading font-bold text-base shadow-lg shadow-amber-500/20 hover:from-amber-400 hover:to-amber-500 hover:scale-[1.02] active:scale-[0.98] transition-all"
                            >
                                <BookOpen class="w-5 h-5 text-neutral-950" />
                                <span>تصفح العدد رقم {{ latestIssue.issue_number }}</span>
                            </Link>

                            <Link
                                href="/issues"
                                class="inline-flex items-center gap-2 px-5 py-3.5 rounded-xl bg-neutral-900 border border-neutral-800 text-neutral-300 hover:text-white hover:border-neutral-700 hover:bg-neutral-850 font-medium text-sm transition-all"
                            >
                                <span>أرشيف الأعداد الكامل</span>
                                <ChevronLeft class="w-4 h-4" />
                            </Link>

                            <Link
                                href="/artists"
                                class="inline-flex items-center gap-2 px-5 py-3.5 rounded-xl bg-neutral-900/60 border border-neutral-800/80 text-neutral-400 hover:text-amber-400 hover:border-amber-500/30 font-medium text-sm transition-all"
                            >
                                <Users class="w-4 h-4 text-amber-500" />
                                <span>دليل الرواد</span>
                            </Link>
                        </div>

                        <!-- Stats Strip -->
                        <div class="pt-6 grid grid-cols-3 gap-4 border-t border-neutral-800/80 max-w-lg">
                            <div class="space-y-1">
                                <span class="font-heading font-black text-2xl text-amber-400">100%</span>
                                <p class="text-xs text-neutral-400">توثيق وطني موثوق</p>
                            </div>
                            <div class="space-y-1">
                                <span class="font-heading font-black text-2xl text-white">{{ latestIssue ? latestIssue.pages_count : '60+' }}</span>
                                <p class="text-xs text-neutral-400">صفحة عالية الدقة للعدد</p>
                            </div>
                            <div class="space-y-1">
                                <span class="font-heading font-black text-2xl text-emerald-400">مجاناً</span>
                                <p class="text-xs text-neutral-400">متاحة للقراء والباحثين</p>
                            </div>
                        </div>
                    </div>

                    <!-- Left Col: 3D Perspective Magazine Mockup Showcase -->
                    <div class="lg:col-span-5 flex justify-center lg:justify-end">
                        <div v-if="latestIssue" class="relative group max-w-sm w-full">
                            <!-- Magazine Glow Aura -->
                            <div class="absolute -inset-1 bg-gradient-to-r from-amber-600 via-amber-500 to-amber-700 rounded-2xl blur-xl opacity-30 group-hover:opacity-50 transition duration-500"></div>

                            <!-- Magazine Card with Book Styling -->
                            <div class="relative bg-neutral-900 border border-neutral-800 rounded-2xl p-4 shadow-2xl transition-transform duration-500 group-hover:-translate-y-2">
                                <div class="relative aspect-[3/4] rounded-xl overflow-hidden bg-neutral-950 border border-neutral-800/80 shadow-inner">
                                    <img
                                        v-if="latestIssue.cover_image"
                                        :src="latestIssue.cover_image.startsWith('http') ? latestIssue.cover_image : `/storage/${latestIssue.cover_image}`"
                                        :alt="latestIssue.title"
                                        class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-700"
                                    />
                                    <div v-else class="w-full h-full bg-gradient-to-br from-neutral-900 via-neutral-950 to-neutral-900 flex flex-col items-center justify-center p-6 text-center">
                                        <div class="w-16 h-16 rounded-2xl bg-amber-500/10 border border-amber-500/20 flex items-center justify-center text-amber-500 mb-4">
                                            <BookOpen class="w-8 h-8" />
                                        </div>
                                        <h3 class="font-heading font-bold text-white text-lg">{{ latestIssue.title }}</h3>
                                        <p class="text-xs text-neutral-400 mt-2">العدد رقم {{ latestIssue.issue_number }}</p>
                                    </div>

                                    <!-- Corner Number Badge -->
                                    <div class="absolute top-3 right-3 px-3 py-1 rounded-lg bg-neutral-950/90 backdrop-blur-md border border-neutral-700/80 text-xs font-heading font-bold text-amber-400 shadow">
                                        العدد #{{ latestIssue.issue_number }}
                                    </div>

                                    <!-- Spine Spine Illusion -->
                                    <div class="absolute inset-y-0 right-0 w-3 bg-gradient-to-l from-black/50 to-transparent pointer-events-none"></div>
                                </div>

                                <!-- Card Metadata -->
                                <div class="mt-4 px-2 space-y-2">
                                    <div class="flex items-center justify-between text-xs text-neutral-400">
                                        <span class="flex items-center gap-1.5">
                                            <Calendar class="w-3.5 h-3.5 text-amber-500" />
                                            {{ formatDate(latestIssue.published_at) }}
                                        </span>
                                        <span class="flex items-center gap-1.5">
                                            <Layers class="w-3.5 h-3.5 text-neutral-400" />
                                            {{ latestIssue.pages_count }} صفحة
                                        </span>
                                    </div>
                                    <h3 class="font-heading font-bold text-white text-base line-clamp-1 group-hover:text-amber-400 transition-colors">
                                        {{ latestIssue.title }}
                                    </h3>
                                    
                                    <div class="pt-2 flex items-center gap-3">
                                        <Link
                                            :href="`/issues/${latestIssue.slug}`"
                                            class="flex-1 py-2 rounded-lg bg-amber-500/10 hover:bg-amber-500/20 border border-amber-500/30 text-amber-400 hover:text-amber-300 font-heading font-semibold text-xs text-center transition-colors"
                                        >
                                            فتح المستعرض الرقمي
                                        </Link>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Fallback Placeholder when no issue -->
                        <div v-else class="max-w-sm w-full p-8 rounded-2xl bg-neutral-900 border border-neutral-800 text-center space-y-4">
                            <BookOpen class="w-12 h-12 text-amber-500 mx-auto" />
                            <h3 class="font-heading font-bold text-white text-lg">الأعداد تصدر تباعاً</h3>
                            <p class="text-xs text-neutral-400">تابع الإصدارات الجديدة عبر البوابة الثقافية</p>
                        </div>
                    </div>

                </div>
            </div>
        </section>

        <!-- Section 2: Top Featured Articles (Editorial Grid) -->
        <section v-if="featuredArticles && featuredArticles.length" class="py-16 border-b border-neutral-800/80 bg-neutral-950">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                
                <!-- Section Header -->
                <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-4 mb-10 pb-4 border-b border-neutral-800">
                    <div>
                        <span class="text-xs font-semibold text-amber-500 uppercase tracking-widest font-heading">مختارات التحرير</span>
                        <h2 class="text-2xl sm:text-3xl font-heading font-bold text-white mt-1">
                            دراسات ومقالات مميزة
                        </h2>
                    </div>
                    <p class="text-sm text-neutral-400 max-w-md font-light">
                        قراءات نقدية وبحوث موثقة بأقلام نخبة من الباحثين والنقاد في المشهد الفني والسينمائي.
                    </p>
                </div>

                <!-- Editorial Layout: 1 Hero Article + 3 Card Grid -->
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
                    
                    <!-- Lead Hero Article (7 Cols) -->
                    <div v-if="featuredArticles[0]" class="lg:col-span-7">
                        <Link
                            :href="`/articles/${featuredArticles[0].slug}`"
                            class="group block relative rounded-2xl overflow-hidden bg-neutral-900 border border-neutral-800 hover:border-amber-500/40 transition-all h-full flex flex-col"
                        >
                            <div class="relative aspect-[16/10] overflow-hidden bg-neutral-950">
                                <img
                                    v-if="featuredArticles[0].featured_image"
                                    :src="featuredArticles[0].featured_image.startsWith('http') ? featuredArticles[0].featured_image : `/storage/${featuredArticles[0].featured_image}`"
                                    :alt="featuredArticles[0].title"
                                    class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-700"
                                />
                                <div v-else class="w-full h-full bg-gradient-to-tr from-neutral-950 via-neutral-900 to-amber-950/20 flex items-center justify-center">
                                    <Film class="w-16 h-16 text-neutral-700" />
                                </div>
                                <div class="absolute inset-0 bg-gradient-to-t from-neutral-950 via-neutral-950/40 to-transparent"></div>

                                <!-- Category Badge -->
                                <span
                                    v-if="featuredArticles[0].category"
                                    class="absolute top-4 right-4 px-3 py-1 rounded-full bg-amber-500 text-neutral-950 font-heading font-bold text-xs shadow-md"
                                >
                                    {{ featuredArticles[0].category.name }}
                                </span>
                            </div>

                            <div class="p-6 sm:p-8 flex-1 flex flex-col justify-between space-y-4">
                                <div class="space-y-3">
                                    <div class="flex items-center gap-4 text-xs text-neutral-400">
                                        <span v-if="featuredArticles[0].author" class="flex items-center gap-1.5 text-neutral-300">
                                            <User class="w-3.5 h-3.5 text-amber-500" />
                                            {{ featuredArticles[0].author.name }}
                                        </span>
                                        <span class="flex items-center gap-1">
                                            <Calendar class="w-3.5 h-3.5" />
                                            {{ formatDate(featuredArticles[0].published_at) }}
                                        </span>
                                        <span class="flex items-center gap-1">
                                            <Eye class="w-3.5 h-3.5" />
                                            {{ featuredArticles[0].views_count }} قراءة
                                        </span>
                                    </div>

                                    <h3 class="text-xl sm:text-2xl font-heading font-bold text-white group-hover:text-amber-400 transition-colors leading-snug">
                                        {{ featuredArticles[0].title }}
                                    </h3>

                                    <p v-if="featuredArticles[0].excerpt" class="text-sm text-neutral-300 leading-relaxed line-clamp-3 font-light">
                                        {{ featuredArticles[0].excerpt }}
                                    </p>
                                </div>

                                <div class="pt-4 flex items-center text-sm font-heading font-semibold text-amber-400 group-hover:text-amber-300">
                                    <span>متابعة قراءة المقال كاملاً</span>
                                    <ChevronLeft class="w-4 h-4 mr-1 group-hover:-translate-x-1 transition-transform" />
                                </div>
                            </div>
                        </Link>
                    </div>

                    <!-- 3 Stacked Secondary Articles (5 Cols) -->
                    <div class="lg:col-span-5 flex flex-col gap-6">
                        <Link
                            v-for="article in featuredArticles.slice(1, 4)"
                            :key="article.id"
                            :href="`/articles/${article.slug}`"
                            class="group p-5 rounded-2xl bg-neutral-900/80 border border-neutral-800 hover:border-neutral-700 hover:bg-neutral-900 transition-all flex gap-5 items-center"
                        >
                            <!-- Thumbnail -->
                            <div class="w-24 h-24 sm:w-28 sm:h-28 rounded-xl overflow-hidden bg-neutral-950 shrink-0 border border-neutral-800 relative">
                                <img
                                    v-if="article.featured_image"
                                    :src="article.featured_image.startsWith('http') ? article.featured_image : `/storage/${article.featured_image}`"
                                    :alt="article.title"
                                    class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
                                />
                                <div v-else class="w-full h-full flex items-center justify-center bg-neutral-900 text-neutral-600">
                                    <Film class="w-8 h-8" />
                                </div>
                            </div>

                            <!-- Content Details -->
                            <div class="flex-1 space-y-2">
                                <div class="flex items-center gap-2">
                                    <span v-if="article.category" class="text-[11px] font-heading font-semibold text-amber-400">
                                        {{ article.category.name }}
                                    </span>
                                    <span class="text-neutral-600 text-xs">•</span>
                                    <span class="text-xs text-neutral-400">
                                        {{ formatDate(article.published_at) }}
                                    </span>
                                </div>

                                <h4 class="font-heading font-bold text-sm sm:text-base text-white group-hover:text-amber-400 transition-colors line-clamp-2 leading-snug">
                                    {{ article.title }}
                                </h4>

                                <div class="flex items-center gap-3 text-xs text-neutral-400">
                                    <span v-if="article.author" class="truncate max-w-[120px]">
                                        {{ article.author.name }}
                                    </span>
                                    <span class="flex items-center gap-1">
                                        <Eye class="w-3 h-3" />
                                        {{ article.views_count }}
                                    </span>
                                </div>
                            </div>
                        </Link>
                    </div>

                </div>

            </div>
        </section>

        <!-- Section 3: Cultural Tracks (السينما، المسرح، الفنون التشكيلية، الموسيقى) -->
        <section id="cultural-tracks" class="py-16 border-b border-neutral-800/80 bg-neutral-950/80 relative">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                
                <!-- Track Section Tabs Header -->
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 mb-10">
                    <div>
                        <span class="text-xs font-semibold text-amber-500 uppercase tracking-widest font-heading">المسارات المتخصصة</span>
                        <h2 class="text-2xl sm:text-3xl font-heading font-bold text-white mt-1">
                            الأقسام الثقافية والفنية
                        </h2>
                    </div>

                    <!-- Filter Tabs -->
                    <div class="flex flex-wrap items-center gap-2 p-1.5 rounded-2xl bg-neutral-900 border border-neutral-800">
                        <button
                            v-for="tab in trackTabs"
                            :key="tab.key"
                            type="button"
                            @click="activeTrack = tab.key"
                            class="flex items-center gap-2 px-4 py-2 rounded-xl text-xs sm:text-sm font-heading font-semibold transition-all duration-200"
                            :class="[
                                activeTrack === tab.key
                                    ? 'bg-amber-500 text-neutral-950 shadow-md'
                                    : 'text-neutral-400 hover:text-white hover:bg-neutral-800/60'
                            ]"
                        >
                            <component :is="tab.icon" class="w-4 h-4" />
                            <span>{{ tab.label }}</span>
                        </button>
                    </div>
                </div>

                <!-- Articles Grid for Selected Track -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                    <template v-if="categorizedArticles[activeTrack] && categorizedArticles[activeTrack].length">
                        <Link
                            v-for="article in categorizedArticles[activeTrack]"
                            :key="article.id"
                            :href="`/articles/${article.slug}`"
                            class="group flex flex-col rounded-2xl bg-neutral-900/90 border border-neutral-800 hover:border-amber-500/30 overflow-hidden hover:-translate-y-1 transition-all"
                        >
                            <div class="aspect-[16/10] overflow-hidden bg-neutral-950 relative">
                                <img
                                    v-if="article.featured_image"
                                    :src="article.featured_image.startsWith('http') ? article.featured_image : `/storage/${article.featured_image}`"
                                    :alt="article.title"
                                    class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
                                />
                                <div v-else class="w-full h-full flex items-center justify-center bg-neutral-900 text-neutral-600">
                                    <BookOpen class="w-8 h-8" />
                                </div>
                                <span class="absolute top-2.5 right-2.5 px-2.5 py-0.5 rounded-md bg-neutral-950/80 backdrop-blur-sm text-[11px] font-heading text-amber-400 border border-neutral-800">
                                    {{ getCategoryLabel(activeTrack) }}
                                </span>
                            </div>

                            <div class="p-5 flex-1 flex flex-col justify-between space-y-3">
                                <div class="space-y-2">
                                    <div class="flex items-center gap-2 text-xs text-neutral-400">
                                        <span>{{ formatDate(article.published_at) }}</span>
                                    </div>
                                    <h4 class="font-heading font-bold text-base text-white group-hover:text-amber-400 transition-colors line-clamp-2 leading-snug">
                                        {{ article.title }}
                                    </h4>
                                    <p v-if="article.excerpt" class="text-xs text-neutral-400 line-clamp-2 font-light">
                                        {{ article.excerpt }}
                                    </p>
                                </div>

                                <div class="pt-2 flex items-center justify-between text-xs text-neutral-400 border-t border-neutral-800/60">
                                    <span v-if="article.author" class="truncate max-w-[120px]">
                                        {{ article.author.name }}
                                    </span>
                                    <span class="flex items-center gap-1 text-amber-500 font-medium">
                                        قراءة
                                        <ChevronLeft class="w-3.5 h-3.5" />
                                    </span>
                                </div>
                            </div>
                        </Link>
                    </template>

                    <!-- Empty State for track -->
                    <div
                        v-else
                        class="col-span-full py-16 text-center rounded-2xl bg-neutral-900/40 border border-neutral-800/80 space-y-3"
                    >
                        <BookOpen class="w-10 h-10 text-neutral-600 mx-auto" />
                        <h4 class="font-heading font-bold text-neutral-300">جاري إعداد محتوى هذا المسار</h4>
                        <p class="text-xs text-neutral-500">سيتم نشر الدراسات المعتمدة في الأعداد القادمة</p>
                    </div>
                </div>

            </div>
        </section>

        <!-- Section 4: Spotlight Artists (دليل الرواد والفنانين) -->
        <section v-if="spotlightArtists && spotlightArtists.length" class="py-16 border-b border-neutral-800/80 bg-neutral-950 relative overflow-hidden">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                
                <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-4 mb-10 pb-4 border-b border-neutral-800">
                    <div>
                        <span class="text-xs font-semibold text-amber-500 uppercase tracking-widest font-heading">ذاكرة الإبداع الليبي</span>
                        <h2 class="text-2xl sm:text-3xl font-heading font-bold text-white mt-1">
                            رواد المسرح والسينما والفنون
                        </h2>
                    </div>
                    <Link
                        href="/artists"
                        class="inline-flex items-center gap-1.5 text-sm font-heading font-semibold text-amber-400 hover:text-amber-300 transition-colors"
                    >
                        <span>استعراض دليل الرواد كاملاً</span>
                        <ChevronLeft class="w-4 h-4" />
                    </Link>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-6">
                    <Link
                        v-for="artist in spotlightArtists"
                        :key="artist.id"
                        :href="`/artists/${artist.slug}`"
                        class="group p-5 rounded-2xl bg-neutral-900/70 border border-neutral-800 hover:border-amber-500/40 hover:bg-neutral-900 text-center flex flex-col items-center space-y-3.5 transition-all hover:-translate-y-1"
                    >
                        <!-- Artist Portrait Avatar -->
                        <div class="relative w-24 h-24 rounded-full overflow-hidden bg-neutral-950 border-2 border-neutral-700 group-hover:border-amber-500 transition-colors shadow-lg">
                            <img
                                v-if="artist.avatar_url"
                                :src="artist.avatar_url.startsWith('http') ? artist.avatar_url : `/storage/${artist.avatar_url}`"
                                :alt="artist.full_name"
                                class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500"
                            />
                            <div v-else class="w-full h-full flex items-center justify-center bg-gradient-to-br from-amber-600/20 to-neutral-900 text-amber-400 font-heading font-bold text-xl">
                                {{ artist.full_name.charAt(0) }}
                            </div>
                        </div>

                        <!-- Info -->
                        <div class="space-y-1 w-full">
                            <h4 class="font-heading font-bold text-sm text-white group-hover:text-amber-400 transition-colors line-clamp-1">
                                {{ artist.full_name }}
                            </h4>
                            <span class="inline-block px-2 py-0.5 rounded text-[11px] font-heading bg-neutral-800 text-amber-400">
                                {{ artist.art_category }}
                            </span>
                            <p v-if="artist.articles_count" class="text-[11px] text-neutral-400 pt-1">
                                {{ artist.articles_count }} دراسات موثقة
                            </p>
                        </div>
                    </Link>
                </div>

            </div>
        </section>

        <!-- Section 5: Digital Archive & Gallery Invitation Banner -->
        <section class="py-16 bg-gradient-to-r from-amber-950/30 via-neutral-900 to-neutral-950 border-b border-neutral-800">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="rounded-3xl p-8 sm:p-12 bg-gradient-to-br from-neutral-900 via-neutral-900/90 to-amber-950/20 border border-neutral-800 relative overflow-hidden flex flex-col lg:flex-row items-center justify-between gap-8">
                    <div class="space-y-4 max-w-2xl text-right">
                        <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-amber-500/10 text-amber-400 text-xs font-semibold">
                            <span>المعرض الرقمي والأرشيف الوثائقي</span>
                        </div>
                        <h3 class="text-2xl sm:text-3xl font-heading font-black text-white">
                            استكشف ذاكرة الصور والملصقات السينمائية النادرة
                        </h3>
                        <p class="text-neutral-300 text-sm sm:text-base leading-relaxed font-light">
                            يتضمن المعرض الرقمي لقطات من أرشيف العروض المسرحية، ملصقات المهرجانات السينمائية الليبية، وتسجيلات صوتية نادرة لمألوف التراث الوطني.
                        </p>
                    </div>

                    <div class="flex flex-wrap items-center gap-4 shrink-0">
                        <Link
                            href="/gallery"
                            class="px-6 py-3.5 rounded-xl bg-amber-500 text-neutral-950 font-heading font-bold text-sm hover:bg-amber-400 transition-all shadow-lg shadow-amber-500/10"
                        >
                            دخول المعرض الرقمي
                        </Link>
                        <Link
                            href="/issues"
                            class="px-6 py-3.5 rounded-xl bg-neutral-800 hover:bg-neutral-700 text-white font-heading font-semibold text-sm transition-all"
                        >
                            تصفح كافة الأعداد
                        </Link>
                    </div>
                </div>
            </div>
        </section>

    </AppLayout>
</template>
